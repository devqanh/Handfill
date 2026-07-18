<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTopUp;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\TopUpService;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class TopUpServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    protected TopUpService $topUpService;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->topUpService = app(TopUpService::class);
        $this->walletService = app(WalletService::class);
        $this->enableEWallet();
    }

    protected function enableEWallet(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
        setting()->forceSet('e_wallet_min_top_up', 100)->save();
        setting()->forceSet('e_wallet_max_top_up', 100000000)->save();
    }

    protected function createCustomer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_can_create_topup(): void
    {
        $customer = $this->createCustomer();

        $topup = $this->topUpService->createTopUp(
            customerId: $customer->id,
            amountCents: 10000
        );

        $this->assertInstanceOf(WalletTopUp::class, $topup);
        $this->assertEquals($customer->id, $topup->customer_id);
        $this->assertEquals(10000, $topup->amount);
        $this->assertEquals(TopUpStatusEnum::PENDING, $topup->status->getValue());
        $this->assertStringStartsWith('TU-', $topup->code);
    }

    public function test_topup_creates_wallet_if_not_exists(): void
    {
        $customer = $this->createCustomer();

        $this->assertNull(Wallet::query()->where('customer_id', $customer->id)->first());

        $topup = $this->topUpService->createTopUp(
            customerId: $customer->id,
            amountCents: 5000
        );

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals($topup->wallet_id, $wallet->id);
    }

    public function test_topup_code_is_unique(): void
    {
        $customer = $this->createCustomer();

        $topup1 = $this->topUpService->createTopUp($customer->id, 5000);
        $topup2 = $this->topUpService->createTopUp($customer->id, 6000);
        $topup3 = $this->topUpService->createTopUp($customer->id, 7000);

        $this->assertNotEquals($topup1->code, $topup2->code);
        $this->assertNotEquals($topup2->code, $topup3->code);
        $this->assertNotEquals($topup1->code, $topup3->code);
    }

    public function test_can_complete_pending_topup(): void
    {
        $customer = $this->createCustomer();
        $this->walletService->getOrCreateWallet($customer->id);

        $topup = $this->topUpService->createTopUp($customer->id, 10000);

        $completedTopup = $this->topUpService->completeTopUp(
            topup: $topup,
            paymentId: 'payment_123',
            paymentMethod: 'bank_transfer'
        );

        $this->assertEquals(TopUpStatusEnum::COMPLETED, $completedTopup->status->getValue());
        $this->assertEquals('payment_123', $completedTopup->payment_id);
        $this->assertEquals('bank_transfer', $completedTopup->payment_method);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(10000, $wallet->balance);
    }

    public function test_can_complete_processing_topup(): void
    {
        $customer = $this->createCustomer();
        $this->walletService->getOrCreateWallet($customer->id);

        $topup = WalletTopUp::query()->create([
            'customer_id' => $customer->id,
            'wallet_id' => Wallet::query()->where('customer_id', $customer->id)->first()->id,
            'code' => 'TU-TEST1234',
            'amount' => 5000,
            'currency_code' => 'USD',
            'converted_amount' => 5000,
            'wallet_currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'status' => TopUpStatusEnum::PROCESSING,
        ]);

        $completedTopup = $this->topUpService->completeTopUp(
            topup: $topup,
            paymentId: 'payment_456',
            paymentMethod: 'credit_card'
        );

        $this->assertEquals(TopUpStatusEnum::COMPLETED, $completedTopup->status->getValue());
    }

    public function test_complete_topup_creates_transaction(): void
    {
        $customer = $this->createCustomer();
        $this->walletService->getOrCreateWallet($customer->id);

        $topup = $this->topUpService->createTopUp($customer->id, 8000);
        $this->topUpService->completeTopUp($topup, 'pay_001', 'sepay');

        $transaction = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('reference_type', WalletTopUp::class)
            ->where('reference_id', $topup->id)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(8000, $transaction->amount);
        $this->assertEquals(TransactionTypeEnum::TOP_UP, $transaction->type->getValue());
    }

    public function test_complete_topup_uses_idempotency_key(): void
    {
        $customer = $this->createCustomer();
        $this->walletService->getOrCreateWallet($customer->id);

        $topup = $this->topUpService->createTopUp($customer->id, 10000);

        $this->topUpService->completeTopUp($topup, 'pay_001', 'sepay');

        $transactions = WalletTransaction::query()
            ->where('idempotency_key', 'topup_' . $topup->id)
            ->get();

        $this->assertCount(1, $transactions);
    }

    public function test_completed_topup_cannot_be_completed_again(): void
    {
        $customer = $this->createCustomer();
        $this->walletService->getOrCreateWallet($customer->id);

        $topup = $this->topUpService->createTopUp($customer->id, 10000);
        $this->topUpService->completeTopUp($topup, 'pay_001', 'sepay');

        $topup->refresh();
        $result = $this->topUpService->completeTopUp($topup, 'pay_002', 'sepay');

        $this->assertEquals(TopUpStatusEnum::COMPLETED, $result->status->getValue());

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(10000, $wallet->balance);
    }

    public function test_can_fail_topup(): void
    {
        $customer = $this->createCustomer();

        $topup = $this->topUpService->createTopUp($customer->id, 5000);

        $failedTopup = $this->topUpService->failTopUp($topup, 'Payment declined');

        $this->assertEquals(TopUpStatusEnum::FAILED, $failedTopup->status->getValue());
        $this->assertEquals('Payment declined', $failedTopup->metadata['failure_reason']);
    }

    public function test_topup_with_currency_conversion(): void
    {
        $customer = $this->createCustomer();

        $topup = $this->topUpService->createTopUp(
            customerId: $customer->id,
            amountCents: 10000,
            currencyCode: 'EUR',
            exchangeRate: 1.1
        );

        $this->assertEquals(10000, $topup->amount);
        $this->assertEquals('EUR', $topup->currency_code);
        $this->assertEquals(1.1, (float) $topup->exchange_rate);
        $this->assertEquals(9091, $topup->converted_amount);
    }

    public function test_topup_amount_below_minimum_throws_exception(): void
    {
        $customer = $this->createCustomer();

        setting()->forceSet('e_wallet_min_top_up', 1000)->save();

        $this->expectException(InvalidArgumentException::class);

        $this->topUpService->createTopUp($customer->id, 500);
    }

    public function test_topup_amount_above_maximum_throws_exception(): void
    {
        $customer = $this->createCustomer();

        setting()->forceSet('e_wallet_max_top_up', 50000)->save();

        $this->expectException(InvalidArgumentException::class);

        $this->topUpService->createTopUp($customer->id, 100000);
    }

    public function test_topup_model_relationships(): void
    {
        $customer = $this->createCustomer();
        $topup = $this->topUpService->createTopUp($customer->id, 5000);

        $this->assertNotNull($topup->customer);
        $this->assertEquals($customer->id, $topup->customer->id);

        $this->assertNotNull($topup->wallet);
        $this->assertEquals($customer->id, $topup->wallet->customer_id);
    }

    public function test_topup_is_pending_method(): void
    {
        $customer = $this->createCustomer();
        $topup = $this->topUpService->createTopUp($customer->id, 5000);

        $this->assertTrue($topup->isPending());
        $this->assertFalse($topup->isCompleted());
    }

    public function test_topup_is_completed_method(): void
    {
        $customer = $this->createCustomer();
        $this->walletService->getOrCreateWallet($customer->id);

        $topup = $this->topUpService->createTopUp($customer->id, 5000);
        $this->topUpService->completeTopUp($topup, 'pay_001', 'sepay');

        $topup->refresh();
        $this->assertFalse($topup->isPending());
        $this->assertTrue($topup->isCompleted());
    }

    public function test_topup_formatted_amount_attribute(): void
    {
        $customer = $this->createCustomer();
        $topup = $this->topUpService->createTopUp($customer->id, 12345);

        $this->assertNotEmpty($topup->formatted_amount);
    }
}
