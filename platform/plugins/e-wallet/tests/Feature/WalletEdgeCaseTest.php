<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\WalletPaymentService;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletEdgeCaseTest extends BaseTestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;

    protected WalletPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);
        $this->paymentService = app(WalletPaymentService::class);
        $this->enableEWallet();
    }

    protected function enableEWallet(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
        setting()->forceSet('e_wallet_enable_e_wallet', true)->save();
        setting()->forceSet('e_wallet_allow_negative_balance', false)->save();
    }

    protected function createCustomer(string $email = 'customer@example.com'): Customer
    {
        return Customer::query()->create([
            'name' => 'Test Customer',
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }

    protected function createWallet(Customer $customer, int $balance = 0): Wallet
    {
        return Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => $balance,
            'currency_code' => 'USD',
        ]);
    }

    protected function createOrder(Customer $customer, float $amount = 50.00): Order
    {
        return Order::query()->create([
            'user_id' => $customer->id,
            'amount' => $amount,
            'sub_total' => $amount,
            'status' => 'pending',
            'is_confirmed' => true,
            'is_finished' => false,
        ]);
    }

    public function test_get_transactions_by_order(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 20000);
        $order = $this->createOrder($customer, 100.00);

        $this->paymentService->processOrderPayment($order, 5000);

        $transactions = $this->walletService->getTransactionsByOrder($order);

        $this->assertCount(1, $transactions);
        foreach ($transactions as $transaction) {
            $this->assertEquals(Order::class, $transaction->reference_type);
            $this->assertEquals($order->id, $transaction->reference_id);
        }
    }

    public function test_get_transactions_by_order_returns_empty_when_no_transactions(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 10000);
        $order = $this->createOrder($customer, 50.00);

        $transactions = $this->walletService->getTransactionsByOrder($order);

        $this->assertCount(0, $transactions);
    }

    public function test_zero_amount_credit(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 5000);

        $transaction = $this->walletService->credit(
            customerId: $customer->id,
            amountCents: 0,
            type: TransactionTypeEnum::TOP_UP,
            description: 'Zero credit'
        );

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);
        $this->assertEquals(0, $transaction->amount);
    }

    public function test_zero_amount_debit(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 5000);

        $transaction = $this->walletService->debit(
            customerId: $customer->id,
            amountCents: 0,
            type: TransactionTypeEnum::PAYMENT,
            description: 'Zero debit'
        );

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);
        $this->assertEquals(0, $transaction->amount);
    }

    public function test_debit_exact_balance(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 5000);

        $transaction = $this->walletService->debit(
            customerId: $customer->id,
            amountCents: 5000,
            type: TransactionTypeEnum::PAYMENT,
            description: 'Exact balance debit'
        );

        $wallet->refresh();
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals(-5000, $transaction->amount);
    }

    public function test_debit_one_cent_over_balance_fails(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 5000);

        $this->expectException(InsufficientBalanceException::class);

        $this->walletService->debit(
            customerId: $customer->id,
            amountCents: 5001,
            type: TransactionTypeEnum::PAYMENT,
            description: 'One cent over'
        );
    }

    public function test_large_amount_credit(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);

        $largeAmount = 999999999;

        $transaction = $this->walletService->credit(
            customerId: $customer->id,
            amountCents: $largeAmount,
            type: TransactionTypeEnum::TOP_UP,
            description: 'Large credit'
        );

        $wallet->refresh();
        $this->assertEquals($largeAmount, $wallet->balance);
        $this->assertEquals($largeAmount, $transaction->amount);
    }

    public function test_sequential_operations_maintain_balance_integrity(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        for ($i = 0; $i < 10; $i++) {
            $this->walletService->credit($customer->id, 100, TransactionTypeEnum::TOP_UP);
            $this->walletService->debit($customer->id, 50, TransactionTypeEnum::PAYMENT);
        }

        $wallet->refresh();
        $expectedBalance = 10000 + (100 * 10) - (50 * 10);
        $this->assertEquals($expectedBalance, $wallet->balance);
    }

    public function test_transaction_balance_before_and_after_are_correct(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 1000);

        $transaction1 = $this->walletService->credit($customer->id, 500, TransactionTypeEnum::TOP_UP);
        $this->assertEquals(1000, $transaction1->balance_before);
        $this->assertEquals(1500, $transaction1->balance_after);

        $transaction2 = $this->walletService->debit($customer->id, 300, TransactionTypeEnum::PAYMENT);
        $this->assertEquals(1500, $transaction2->balance_before);
        $this->assertEquals(1200, $transaction2->balance_after);

        $transaction3 = $this->walletService->credit($customer->id, 800, TransactionTypeEnum::REFUND);
        $this->assertEquals(1200, $transaction3->balance_before);
        $this->assertEquals(2000, $transaction3->balance_after);

        $wallet->refresh();
        $this->assertEquals(2000, $wallet->balance);
    }

    public function test_wallet_isolation_between_customers(): void
    {
        $customer1 = $this->createCustomer('customer1@example.com');
        $customer2 = $this->createCustomer('customer2@example.com');

        $wallet1 = $this->createWallet($customer1, 5000);
        $wallet2 = $this->createWallet($customer2, 10000);

        $this->walletService->credit($customer1->id, 1000, TransactionTypeEnum::TOP_UP);
        $this->walletService->debit($customer2->id, 2000, TransactionTypeEnum::PAYMENT);

        $wallet1->refresh();
        $wallet2->refresh();

        $this->assertEquals(6000, $wallet1->balance);
        $this->assertEquals(8000, $wallet2->balance);
    }

    public function test_multiple_wallets_independent(): void
    {
        $customer1 = $this->createCustomer('c1@example.com');
        $customer2 = $this->createCustomer('c2@example.com');
        $customer3 = $this->createCustomer('c3@example.com');

        $this->createWallet($customer1, 1000);
        $this->createWallet($customer2, 2000);
        $this->createWallet($customer3, 3000);

        $this->walletService->credit($customer1->id, 500, TransactionTypeEnum::TOP_UP);

        $balance1 = $this->walletService->getBalance($customer1->id);
        $balance2 = $this->walletService->getBalance($customer2->id);
        $balance3 = $this->walletService->getBalance($customer3->id);

        $this->assertEquals(1500, $balance1);
        $this->assertEquals(2000, $balance2);
        $this->assertEquals(3000, $balance3);
    }

    public function test_wallet_default_currency(): void
    {
        $customer = $this->createCustomer();

        $wallet = $this->walletService->getOrCreateWallet($customer->id);

        $this->assertNotEmpty($wallet->currency_code);
    }

    public function test_transaction_all_types_create_successfully(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 100000);

        $types = [
            TransactionTypeEnum::TOP_UP,
            TransactionTypeEnum::PAYMENT,
            TransactionTypeEnum::REFUND,
            TransactionTypeEnum::ADMIN_ADJUSTMENT,
            TransactionTypeEnum::VENDOR_PAYOUT,
        ];

        foreach ($types as $type) {
            if (in_array($type, ['payment', 'vendor_payout'])) {
                $transaction = $this->walletService->debit(
                    $customer->id,
                    1000,
                    $type
                );
                $this->assertEquals($type, $transaction->type->getValue());
            } else {
                $transaction = $this->walletService->credit(
                    $customer->id,
                    1000,
                    $type
                );
                $this->assertEquals($type, $transaction->type->getValue());
            }
        }
    }

    public function test_transaction_status_is_completed(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 10000);

        $creditTransaction = $this->walletService->credit(
            $customer->id,
            1000,
            TransactionTypeEnum::TOP_UP
        );

        $debitTransaction = $this->walletService->debit(
            $customer->id,
            500,
            TransactionTypeEnum::PAYMENT
        );

        $this->assertEquals(TransactionStatusEnum::COMPLETED, $creditTransaction->status->getValue());
        $this->assertEquals(TransactionStatusEnum::COMPLETED, $debitTransaction->status->getValue());
    }

    public function test_get_balance_returns_zero_for_nonexistent_wallet(): void
    {
        $customer = $this->createCustomer();

        $balance = $this->walletService->getBalance($customer->id);

        $this->assertEquals(0, $balance);
    }

    public function test_credit_creates_wallet_if_not_exists(): void
    {
        $customer = $this->createCustomer();

        $this->assertNull(Wallet::query()->where('customer_id', $customer->id)->first());

        $this->walletService->credit($customer->id, 5000, TransactionTypeEnum::TOP_UP);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_adjust_balance_with_created_by(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 0);

        $adminId = 999;

        $transaction = $this->walletService->adjustBalance(
            customerId: $customer->id,
            amountCents: 5000,
            description: 'Admin adjustment',
            createdBy: $adminId
        );

        $this->assertArrayHasKey('created_by', $transaction->metadata);
        $this->assertEquals($adminId, $transaction->metadata['created_by']);
    }

    public function test_adjust_balance_debit_respects_balance(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 3000);

        $this->expectException(InsufficientBalanceException::class);

        $this->walletService->adjustBalance(
            customerId: $customer->id,
            amountCents: -5000,
            description: 'Over deduction'
        );
    }

    public function test_idempotency_key_with_special_characters(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 0);

        $specialKey = 'key-with_special.chars:123/456';

        $transaction = $this->walletService->credit(
            customerId: $customer->id,
            amountCents: 1000,
            type: TransactionTypeEnum::TOP_UP,
            idempotencyKey: $specialKey
        );

        $found = $this->walletService->findTransactionByIdempotencyKey($specialKey);

        $this->assertNotNull($found);
        $this->assertEquals($transaction->id, $found->id);
    }

    public function test_transaction_pagination(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 0);

        for ($i = 0; $i < 25; $i++) {
            $this->walletService->credit($customer->id, 100, TransactionTypeEnum::TOP_UP);
        }

        $transactions = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->paginate(10);

        $this->assertEquals(10, $transactions->count());
        $this->assertEquals(25, $transactions->total());
        $this->assertEquals(3, $transactions->lastPage());
    }

    public function test_wallet_relationship_with_transactions(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        $this->walletService->credit($customer->id, 500, TransactionTypeEnum::TOP_UP);
        $this->walletService->debit($customer->id, 200, TransactionTypeEnum::PAYMENT);

        $wallet->refresh();
        $this->assertCount(2, $wallet->transactions);
    }

    public function test_transaction_ordering(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        $first = $this->walletService->credit($customer->id, 100, TransactionTypeEnum::TOP_UP);
        $second = $this->walletService->credit($customer->id, 200, TransactionTypeEnum::TOP_UP);
        $third = $this->walletService->credit($customer->id, 300, TransactionTypeEnum::TOP_UP);

        $transactions = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('id')
            ->get();

        $this->assertEquals($third->id, $transactions->first()->id);
        $this->assertEquals($first->id, $transactions->last()->id);
    }
}
