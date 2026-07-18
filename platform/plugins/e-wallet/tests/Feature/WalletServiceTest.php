<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Exceptions\DuplicateTransactionException;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    protected WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WalletService::class);
        $this->enableEWallet();
    }

    protected function enableEWallet(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
        setting()->forceSet('e_wallet_allow_negative_balance', false)->save();
    }

    protected function createCustomer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_can_get_or_create_wallet(): void
    {
        $customer = $this->createCustomer();

        $wallet = $this->service->getOrCreateWallet($customer->id);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals($customer->id, $wallet->customer_id);
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_get_or_create_wallet_returns_existing_wallet(): void
    {
        $customer = $this->createCustomer();

        $wallet1 = $this->service->getOrCreateWallet($customer->id);
        $wallet2 = $this->service->getOrCreateWallet($customer->id);

        $this->assertEquals($wallet1->id, $wallet2->id);
    }

    public function test_can_get_balance(): void
    {
        $customer = $this->createCustomer();

        $balance = $this->service->getBalance($customer->id);
        $this->assertEquals(0, $balance);

        Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'currency_code' => 'USD',
        ]);

        $balance = $this->service->getBalance($customer->id);
        $this->assertEquals(10000, $balance);
    }

    public function test_can_credit_wallet(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $transaction = $this->service->credit(
            customerId: $customer->id,
            amountCents: 5000,
            type: TransactionTypeEnum::TOP_UP,
            description: 'Test credit'
        );

        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals(5000, $transaction->amount);
        $this->assertEquals(0, $transaction->balance_before);
        $this->assertEquals(5000, $transaction->balance_after);
        $this->assertEquals(TransactionStatusEnum::COMPLETED, $transaction->status->getValue());

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_can_debit_wallet(): void
    {
        $customer = $this->createCustomer();

        Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'currency_code' => 'USD',
        ]);

        $transaction = $this->service->debit(
            customerId: $customer->id,
            amountCents: 3000,
            type: TransactionTypeEnum::PAYMENT,
            description: 'Test debit'
        );

        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals(-3000, $transaction->amount);
        $this->assertEquals(10000, $transaction->balance_before);
        $this->assertEquals(7000, $transaction->balance_after);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(7000, $wallet->balance);
    }

    public function test_debit_throws_insufficient_balance_exception(): void
    {
        $customer = $this->createCustomer();

        Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => 1000,
            'currency_code' => 'USD',
        ]);

        $this->expectException(InsufficientBalanceException::class);

        $this->service->debit(
            customerId: $customer->id,
            amountCents: 5000,
            type: TransactionTypeEnum::PAYMENT,
            description: 'Test debit'
        );
    }

    public function test_duplicate_idempotency_key_throws_exception(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $this->service->credit(
            customerId: $customer->id,
            amountCents: 1000,
            type: TransactionTypeEnum::TOP_UP,
            idempotencyKey: 'unique_key_123'
        );

        $this->expectException(DuplicateTransactionException::class);

        $this->service->credit(
            customerId: $customer->id,
            amountCents: 1000,
            type: TransactionTypeEnum::TOP_UP,
            idempotencyKey: 'unique_key_123'
        );
    }

    public function test_can_adjust_balance_credit(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $transaction = $this->service->adjustBalance(
            customerId: $customer->id,
            amountCents: 2500,
            description: 'Admin bonus'
        );

        $this->assertEquals(TransactionTypeEnum::ADMIN_ADJUSTMENT, $transaction->type->getValue());
        $this->assertEquals(2500, $transaction->amount);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(2500, $wallet->balance);
    }

    public function test_can_adjust_balance_debit(): void
    {
        $customer = $this->createCustomer();

        Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => 5000,
            'currency_code' => 'USD',
        ]);

        $transaction = $this->service->adjustBalance(
            customerId: $customer->id,
            amountCents: -1500,
            description: 'Admin deduction'
        );

        $this->assertEquals(TransactionTypeEnum::ADMIN_ADJUSTMENT, $transaction->type->getValue());
        $this->assertEquals(-1500, $transaction->amount);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(3500, $wallet->balance);
    }

    public function test_can_find_transaction_by_idempotency_key(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $transaction = $this->service->credit(
            customerId: $customer->id,
            amountCents: 1000,
            type: TransactionTypeEnum::TOP_UP,
            idempotencyKey: 'find_me_key'
        );

        $found = $this->service->findTransactionByIdempotencyKey('find_me_key');

        $this->assertNotNull($found);
        $this->assertEquals($transaction->id, $found->id);
    }

    public function test_find_transaction_by_idempotency_key_returns_null_if_not_found(): void
    {
        $found = $this->service->findTransactionByIdempotencyKey('nonexistent_key');

        $this->assertNull($found);
    }

    public function test_multiple_credits_accumulate_balance(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $this->service->credit($customer->id, 1000, TransactionTypeEnum::TOP_UP);
        $this->service->credit($customer->id, 2000, TransactionTypeEnum::TOP_UP);
        $this->service->credit($customer->id, 3000, TransactionTypeEnum::REFUND);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(6000, $wallet->balance);
    }

    public function test_credit_and_debit_sequence(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $this->service->credit($customer->id, 10000, TransactionTypeEnum::TOP_UP);
        $this->service->debit($customer->id, 3000, TransactionTypeEnum::PAYMENT);
        $this->service->credit($customer->id, 2000, TransactionTypeEnum::REFUND);
        $this->service->debit($customer->id, 4000, TransactionTypeEnum::PAYMENT);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(5000, $wallet->balance);

        $transactions = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->get();
        $this->assertCount(4, $transactions);
    }

    public function test_transaction_stores_reference(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $transaction = $this->service->credit(
            customerId: $customer->id,
            amountCents: 1000,
            type: TransactionTypeEnum::REFUND,
            referenceType: 'Botble\Ecommerce\Models\Order',
            referenceId: 123
        );

        $this->assertEquals('Botble\Ecommerce\Models\Order', $transaction->reference_type);
        $this->assertEquals(123, $transaction->reference_id);
    }

    public function test_transaction_stores_metadata(): void
    {
        $customer = $this->createCustomer();
        $this->service->getOrCreateWallet($customer->id);

        $metadata = ['order_code' => '#ORD-001', 'reason' => 'Cancelled order'];

        $transaction = $this->service->credit(
            customerId: $customer->id,
            amountCents: 1000,
            type: TransactionTypeEnum::REFUND,
            metadata: $metadata
        );

        $this->assertEquals($metadata, $transaction->metadata);
    }
}
