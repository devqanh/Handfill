<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Events\OrderReturnedEvent;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderReturn;
use Botble\Ecommerce\Models\OrderReturnItem;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Listeners\ProcessOrderRefund;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcessOrderRefundTest extends BaseTestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;

    protected ProcessOrderRefund $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);
        $this->listener = app(ProcessOrderRefund::class);
        $this->enableEWallet();
    }

    protected function enableEWallet(): void
    {
        setting()->forceSet('e_wallet_enable_e_wallet', true)->save();
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

    protected function createWallet(Customer $customer, int $balance = 0): Wallet
    {
        return Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => $balance,
            'currency_code' => 'USD',
        ]);
    }

    protected function createOrder(Customer $customer, float $amount = 100.00): Order
    {
        return Order::query()->create([
            'user_id' => $customer->id,
            'amount' => $amount,
            'sub_total' => $amount,
            'status' => 'completed',
            'is_confirmed' => true,
            'is_finished' => true,
        ]);
    }

    protected function createOrderReturn(Order $order, Customer $customer): OrderReturn
    {
        return OrderReturn::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'order_status' => $order->status,
            'return_status' => 'pending',
        ]);
    }

    protected function createOrderReturnItem(OrderReturn $orderReturn, float $price, int $qty = 1): OrderReturnItem
    {
        return OrderReturnItem::query()->create([
            'order_return_id' => $orderReturn->id,
            'order_product_id' => 0,
            'product_id' => 0,
            'product_name' => 'Test Product',
            'qty' => $qty,
            'price' => $price,
        ]);
    }

    public function test_refund_credits_wallet_when_order_returned(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 50.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_refund_calculates_total_from_multiple_items(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 200.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 30.00, 2);
        $this->createOrderReturnItem($orderReturn, 40.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(10000, $wallet->balance);
    }

    public function test_refund_creates_transaction_with_correct_type(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 25.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $transaction = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', TransactionTypeEnum::REFUND)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(2500, $transaction->amount);
        $this->assertEquals(OrderReturn::class, $transaction->reference_type);
        $this->assertEquals($orderReturn->id, $transaction->reference_id);
    }

    public function test_refund_stores_order_metadata(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 50.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $transaction = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', TransactionTypeEnum::REFUND)
            ->first();

        $this->assertArrayHasKey('order_code', $transaction->metadata);
        $this->assertArrayHasKey('order_id', $transaction->metadata);
        $this->assertArrayHasKey('return_code', $transaction->metadata);
        $this->assertArrayHasKey('original_amount', $transaction->metadata);
        $this->assertArrayHasKey('refund_amount', $transaction->metadata);
    }

    public function test_refund_is_idempotent(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 50.00, 1);

        $event = new OrderReturnedEvent($orderReturn);

        $this->listener->handle($event);
        $this->listener->handle($event);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);

        $transactionCount = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', TransactionTypeEnum::REFUND)
            ->count();
        $this->assertEquals(1, $transactionCount);
    }

    public function test_refund_skipped_when_wallet_disabled(): void
    {
        setting()->forceSet('e_wallet_enable_e_wallet', false)->save();
        setting()->forceSet('e_wallet_enabled', false)->save();

        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 50.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_refund_skipped_when_order_has_no_user(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);

        $order = Order::query()->create([
            'user_id' => 0,
            'amount' => 100.00,
            'sub_total' => 100.00,
            'status' => 'completed',
            'is_confirmed' => true,
            'is_finished' => true,
        ]);

        $orderReturn = OrderReturn::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'order_status' => $order->status,
            'return_status' => 'pending',
        ]);

        $this->createOrderReturnItem($orderReturn, 50.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_refund_skipped_when_no_items_returned(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_refund_caps_at_order_amount(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 50.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 100.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_refund_adds_to_existing_balance(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 5000);
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 30.00, 1);

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet->refresh();
        $this->assertEquals(8000, $wallet->balance);
    }

    public function test_multiple_returns_for_same_order(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $order = $this->createOrder($customer, 100.00);

        $orderReturn1 = $this->createOrderReturn($order, $customer);
        $this->createOrderReturnItem($orderReturn1, 30.00, 1);

        $orderReturn2 = $this->createOrderReturn($order, $customer);
        $this->createOrderReturnItem($orderReturn2, 20.00, 1);

        $event1 = new OrderReturnedEvent($orderReturn1);
        $event2 = new OrderReturnedEvent($orderReturn2);

        $this->listener->handle($event1);
        $this->listener->handle($event2);

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);

        $transactionCount = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', TransactionTypeEnum::REFUND)
            ->count();
        $this->assertEquals(2, $transactionCount);
    }

    public function test_refund_creates_wallet_if_not_exists(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, 100.00);
        $orderReturn = $this->createOrderReturn($order, $customer);

        $this->createOrderReturnItem($orderReturn, 50.00, 1);

        $this->assertNull(Wallet::query()->where('customer_id', $customer->id)->first());

        $event = new OrderReturnedEvent($orderReturn);
        $this->listener->handle($event);

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(5000, $wallet->balance);
    }
}
