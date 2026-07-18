<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\WalletPaymentService;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletPaymentServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    protected WalletPaymentService $paymentService;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(WalletPaymentService::class);
        $this->walletService = app(WalletService::class);
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

    protected function createWallet(Customer $customer, int $balance = 10000): Wallet
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

    public function test_can_process_order_payment(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 10000);
        $order = $this->createOrder($customer, 50.00);

        $transaction = $this->paymentService->processOrderPayment($order, 5000);

        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals(-5000, $transaction->amount);
        $this->assertEquals(TransactionTypeEnum::PAYMENT, $transaction->type->getValue());
        $this->assertEquals(Order::class, $transaction->reference_type);
        $this->assertEquals($order->id, $transaction->reference_id);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_order_payment_uses_idempotency_key(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 10000);
        $order = $this->createOrder($customer, 30.00);

        $transaction1 = $this->paymentService->processOrderPayment($order, 3000);
        $transaction2 = $this->paymentService->processOrderPayment($order, 3000);

        $this->assertEquals($transaction1->id, $transaction2->id);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(7000, $wallet->balance);
    }

    public function test_order_payment_stores_order_metadata(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 10000);
        $order = $this->createOrder($customer, 25.00);

        $transaction = $this->paymentService->processOrderPayment($order, 2500);

        $this->assertArrayHasKey('order_code', $transaction->metadata);
        $this->assertEquals($order->code, $transaction->metadata['order_code']);
    }

    public function test_insufficient_balance_throws_exception(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 1000);
        $order = $this->createOrder($customer, 50.00);

        $this->expectException(InsufficientBalanceException::class);

        $this->paymentService->processOrderPayment($order, 5000);
    }

    public function test_can_pay_with_wallet_returns_true_when_sufficient(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 10000);

        $this->assertTrue($this->paymentService->canPayWithWallet($customer->id, 5000));
        $this->assertTrue($this->paymentService->canPayWithWallet($customer->id, 10000));
    }

    public function test_can_pay_with_wallet_returns_false_when_insufficient(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 5000);

        $this->assertFalse($this->paymentService->canPayWithWallet($customer->id, 10000));
    }

    public function test_can_pay_with_wallet_returns_false_for_empty_wallet(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 0);

        $this->assertFalse($this->paymentService->canPayWithWallet($customer->id, 100));
    }

    public function test_can_pay_with_wallet_returns_true_for_zero_amount(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 0);

        $this->assertTrue($this->paymentService->canPayWithWallet($customer->id, 0));
    }

    public function test_get_max_payable_amount_returns_balance(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 7500);

        $maxAmount = $this->paymentService->getMaxPayableAmount($customer->id);

        $this->assertEquals(7500, $maxAmount);
    }

    public function test_get_max_payable_amount_for_customer_without_wallet(): void
    {
        $customer = $this->createCustomer();

        $maxAmount = $this->paymentService->getMaxPayableAmount($customer->id);

        $this->assertEquals(0, $maxAmount);
    }

    public function test_multiple_orders_payment(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 20000);

        $order1 = $this->createOrder($customer, 50.00);
        $order2 = $this->createOrder($customer, 30.00);
        $order3 = $this->createOrder($customer, 20.00);

        $this->paymentService->processOrderPayment($order1, 5000);
        $this->paymentService->processOrderPayment($order2, 3000);
        $this->paymentService->processOrderPayment($order3, 2000);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(10000, $wallet->balance);

        $transactions = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', TransactionTypeEnum::PAYMENT)
            ->get();
        $this->assertCount(3, $transactions);
    }

    public function test_partial_payment_until_balance_exhausted(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 5000);

        $order1 = $this->createOrder($customer, 30.00);
        $order2 = $this->createOrder($customer, 30.00);

        $this->paymentService->processOrderPayment($order1, 3000);

        $this->assertTrue($this->paymentService->canPayWithWallet($customer->id, 2000));
        $this->assertFalse($this->paymentService->canPayWithWallet($customer->id, 3000));
    }
}
