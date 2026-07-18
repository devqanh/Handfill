<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletTransactionTest extends BaseTestCase
{
    use RefreshDatabase;

    protected function createCustomer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function createWallet(Customer $customer, int $balance = 10000): Wallet
    {
        return Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => $balance,
            'currency_code' => 'USD',
        ]);
    }

    protected function createTransaction(Wallet $wallet, array $data = []): WalletTransaction
    {
        return WalletTransaction::query()->create(array_merge([
            'wallet_id' => $wallet->id,
            'customer_id' => $wallet->customer_id,
            'type' => TransactionTypeEnum::TOP_UP,
            'status' => TransactionStatusEnum::COMPLETED,
            'amount' => 5000,
            'balance_before' => 5000,
            'balance_after' => 10000,
        ], $data));
    }

    public function test_can_create_transaction(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $transaction = $this->createTransaction($wallet);

        $this->assertDatabaseHas('ec_wallet_transactions', [
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'amount' => 5000,
        ]);
    }

    public function test_transaction_belongs_to_wallet(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet);

        $this->assertNotNull($transaction->wallet);
        $this->assertEquals($wallet->id, $transaction->wallet->id);
    }

    public function test_transaction_belongs_to_customer(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet);

        $this->assertNotNull($transaction->customer);
        $this->assertEquals($customer->id, $transaction->customer->id);
    }

    public function test_transaction_type_is_enum(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, ['type' => TransactionTypeEnum::PAYMENT]);

        $this->assertInstanceOf(TransactionTypeEnum::class, $transaction->type);
        $this->assertEquals(TransactionTypeEnum::PAYMENT, $transaction->type->getValue());
    }

    public function test_transaction_status_is_enum(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, ['status' => TransactionStatusEnum::COMPLETED]);

        $this->assertInstanceOf(TransactionStatusEnum::class, $transaction->status);
        $this->assertEquals(TransactionStatusEnum::COMPLETED, $transaction->status->getValue());
    }

    public function test_transaction_positive_amount_for_credit(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, [
            'type' => TransactionTypeEnum::TOP_UP,
            'amount' => 5000,
        ]);

        $this->assertEquals(5000, $transaction->amount);
        $this->assertTrue($transaction->amount > 0);
    }

    public function test_transaction_negative_amount_for_debit(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, [
            'type' => TransactionTypeEnum::PAYMENT,
            'amount' => -3000,
            'balance_before' => 10000,
            'balance_after' => 7000,
        ]);

        $this->assertEquals(-3000, $transaction->amount);
        $this->assertTrue($transaction->amount < 0);
    }

    public function test_transaction_stores_balance_before_and_after(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, [
            'balance_before' => 5000,
            'balance_after' => 10000,
        ]);

        $this->assertEquals(5000, $transaction->balance_before);
        $this->assertEquals(10000, $transaction->balance_after);
    }

    public function test_transaction_with_reference(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, [
            'reference_type' => 'Botble\Ecommerce\Models\Order',
            'reference_id' => 123,
        ]);

        $this->assertEquals('Botble\Ecommerce\Models\Order', $transaction->reference_type);
        $this->assertEquals(123, $transaction->reference_id);
    }

    public function test_transaction_with_idempotency_key(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, [
            'idempotency_key' => 'unique_key_abc123',
        ]);

        $this->assertEquals('unique_key_abc123', $transaction->idempotency_key);
    }

    public function test_transaction_with_description(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, [
            'description' => 'Payment for order #123',
        ]);

        $this->assertEquals('Payment for order #123', $transaction->description);
    }

    public function test_transaction_with_metadata(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $metadata = [
            'order_code' => '#ORD-001',
            'items' => ['Product A', 'Product B'],
        ];

        $transaction = $this->createTransaction($wallet, [
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $transaction->metadata);
        $this->assertEquals('#ORD-001', $transaction->metadata['order_code']);
    }

    public function test_can_filter_transactions_by_type(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::TOP_UP]);
        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::TOP_UP]);
        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::PAYMENT, 'amount' => -1000]);
        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::REFUND]);

        $topUpTransactions = WalletTransaction::query()
            ->where('type', TransactionTypeEnum::TOP_UP)
            ->get();

        $paymentTransactions = WalletTransaction::query()
            ->where('type', TransactionTypeEnum::PAYMENT)
            ->get();

        $this->assertCount(2, $topUpTransactions);
        $this->assertCount(1, $paymentTransactions);
    }

    public function test_can_filter_transactions_by_customer(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = Customer::query()->create([
            'name' => 'Customer 2',
            'email' => 'customer2@example.com',
            'password' => bcrypt('password'),
        ]);

        $wallet1 = $this->createWallet($customer1);
        $wallet2 = $this->createWallet($customer2);

        $this->createTransaction($wallet1);
        $this->createTransaction($wallet1);
        $this->createTransaction($wallet2);

        $customer1Transactions = WalletTransaction::query()
            ->where('customer_id', $customer1->id)
            ->get();

        $customer2Transactions = WalletTransaction::query()
            ->where('customer_id', $customer2->id)
            ->get();

        $this->assertCount(2, $customer1Transactions);
        $this->assertCount(1, $customer2Transactions);
    }

    public function test_transactions_ordered_by_id_desc(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $transaction1 = $this->createTransaction($wallet, ['amount' => 1000]);
        $transaction2 = $this->createTransaction($wallet, ['amount' => 2000]);
        $transaction3 = $this->createTransaction($wallet, ['amount' => 3000]);

        $transactions = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->get();

        $this->assertEquals($transaction3->id, $transactions->first()->id);
        $this->assertEquals($transaction1->id, $transactions->last()->id);
        $this->assertCount(3, $transactions);
    }

    public function test_sum_total_credits_for_customer(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $this->createTransaction($wallet, ['amount' => 5000, 'type' => TransactionTypeEnum::TOP_UP]);
        $this->createTransaction($wallet, ['amount' => 3000, 'type' => TransactionTypeEnum::TOP_UP]);
        $this->createTransaction($wallet, ['amount' => -2000, 'type' => TransactionTypeEnum::PAYMENT]);

        $totalCredits = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('amount', '>', 0)
            ->sum('amount');

        $this->assertEquals(8000, $totalCredits);
    }

    public function test_sum_net_balance_for_customer(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $this->createTransaction($wallet, ['amount' => 10000, 'type' => TransactionTypeEnum::TOP_UP]);
        $this->createTransaction($wallet, ['amount' => 5000, 'type' => TransactionTypeEnum::REFUND]);
        $this->createTransaction($wallet, ['amount' => -3000, 'type' => TransactionTypeEnum::PAYMENT]);
        $this->createTransaction($wallet, ['amount' => -2000, 'type' => TransactionTypeEnum::PAYMENT]);

        $netBalance = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->sum('amount');

        $this->assertEquals(10000, $netBalance);
    }

    public function test_transaction_formatted_amount_attribute(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, ['amount' => 12345]);

        $this->assertNotEmpty($transaction->formatted_amount);
    }

    public function test_transaction_type_html(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, ['type' => TransactionTypeEnum::TOP_UP]);

        $html = $transaction->type->toHtml();

        $this->assertNotEmpty($html);
    }

    public function test_transaction_status_html(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $transaction = $this->createTransaction($wallet, ['status' => TransactionStatusEnum::COMPLETED]);

        $html = $transaction->status->toHtml();

        $this->assertNotEmpty($html);
    }
}
