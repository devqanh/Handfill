<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Exceptions\GiftCardAlreadyRedeemedException;
use Botble\EWallet\Exceptions\GiftCardExpiredException;
use Botble\EWallet\Exceptions\InvalidGiftCardCodeException;
use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Services\GiftCardCheckoutService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class GiftCardCheckoutServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    protected GiftCardCheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GiftCardCheckoutService::class);
        $this->enableGiftCards();
    }

    protected function enableGiftCards(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
        setting()->forceSet('e_wallet_gift_cards_enabled', true)->save();
    }

    protected function createCustomer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function createGiftCard(array $attributes = []): GiftCard
    {
        return GiftCard::query()->create(array_merge([
            'code' => 'TEST-' . strtoupper(uniqid()),
            'initial_value' => 10000,
            'balance' => 10000,
            'currency_code' => 'USD',
            'status' => GiftCardStatusEnum::ACTIVE,
        ], $attributes));
    }

    protected function createOrder(array $attributes = []): Order
    {
        return Order::query()->create(array_merge([
            'token' => uniqid(),
            'amount' => 100.00,
            'sub_total' => 100.00,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'status' => 'pending',
            'is_finished' => false,
        ], $attributes));
    }

    public function test_can_apply_valid_gift_card(): void
    {
        $giftCard = $this->createGiftCard([
            'code' => 'VALID-CODE-1234',
            'balance' => 5000,
        ]);

        $result = $this->service->apply('VALID-CODE-1234', 10000);

        $this->assertIsArray($result);
        $this->assertEquals($giftCard->id, $result['id']);
        $this->assertEquals('VALID-CODE-1234', $result['code']);
        $this->assertEquals(5000, $result['balance']);
        $this->assertEquals(5000, $result['discount_amount']);
    }

    public function test_apply_normalizes_code_to_uppercase(): void
    {
        $this->createGiftCard(['code' => 'UPPERCASE-CODE']);

        $result = $this->service->apply('uppercase-code', 10000);

        $this->assertEquals('UPPERCASE-CODE', $result['code']);
    }

    public function test_apply_trims_whitespace_from_code(): void
    {
        $this->createGiftCard(['code' => 'TRIMMED-CODE']);

        $result = $this->service->apply('  TRIMMED-CODE  ', 10000);

        $this->assertEquals('TRIMMED-CODE', $result['code']);
    }

    public function test_apply_throws_exception_for_invalid_code(): void
    {
        $this->expectException(InvalidGiftCardCodeException::class);

        $this->service->apply('INVALID-CODE', 10000);
    }

    public function test_apply_throws_exception_for_already_redeemed_card(): void
    {
        $this->createGiftCard([
            'code' => 'REDEEMED-CARD',
            'status' => GiftCardStatusEnum::REDEEMED,
        ]);

        $this->expectException(GiftCardAlreadyRedeemedException::class);

        $this->service->apply('REDEEMED-CARD', 10000);
    }

    public function test_apply_throws_exception_for_expired_card(): void
    {
        $this->createGiftCard([
            'code' => 'EXPIRED-CARD',
            'status' => GiftCardStatusEnum::ACTIVE,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->expectException(GiftCardExpiredException::class);

        $this->service->apply('EXPIRED-CARD', 10000);
    }

    public function test_apply_throws_exception_for_cancelled_card(): void
    {
        $this->createGiftCard([
            'code' => 'CANCELLED-CARD',
            'status' => GiftCardStatusEnum::CANCELLED,
        ]);

        $this->expectException(InvalidGiftCardCodeException::class);

        $this->service->apply('CANCELLED-CARD', 10000);
    }

    public function test_apply_throws_exception_for_zero_balance_card(): void
    {
        $this->createGiftCard([
            'code' => 'ZERO-BALANCE',
            'status' => GiftCardStatusEnum::ACTIVE,
            'balance' => 0,
        ]);

        $this->expectException(InvalidGiftCardCodeException::class);

        $this->service->apply('ZERO-BALANCE', 10000);
    }

    public function test_discount_amount_is_limited_to_gift_card_balance(): void
    {
        $this->createGiftCard([
            'code' => 'SMALL-BALANCE',
            'balance' => 3000,
        ]);

        $result = $this->service->apply('SMALL-BALANCE', 10000);

        $this->assertEquals(3000, $result['discount_amount']);
    }

    public function test_discount_amount_is_limited_to_order_total(): void
    {
        $this->createGiftCard([
            'code' => 'LARGE-BALANCE',
            'balance' => 10000,
        ]);

        $result = $this->service->apply('LARGE-BALANCE', 5000);

        $this->assertEquals(5000, $result['discount_amount']);
    }

    public function test_apply_stores_data_in_session(): void
    {
        $this->createGiftCard(['code' => 'SESSION-TEST']);

        $this->service->apply('SESSION-TEST', 10000);

        $this->assertNotNull(Session::get('applied_gift_card'));
        $this->assertNotNull(Session::get('gift_card_discount'));
    }

    public function test_can_get_applied_gift_card(): void
    {
        $giftCard = $this->createGiftCard(['code' => 'GET-APPLIED']);

        $this->service->apply('GET-APPLIED', 10000);

        $applied = $this->service->getApplied();

        $this->assertIsArray($applied);
        $this->assertEquals($giftCard->id, $applied['id']);
    }

    public function test_get_applied_returns_null_when_no_card_applied(): void
    {
        $applied = $this->service->getApplied();

        $this->assertNull($applied);
    }

    public function test_can_remove_applied_gift_card(): void
    {
        $this->createGiftCard(['code' => 'REMOVE-TEST']);
        $this->service->apply('REMOVE-TEST', 10000);

        $this->service->remove();

        $this->assertNull($this->service->getApplied());
        $this->assertNull(Session::get('gift_card_discount'));
    }

    public function test_can_recalculate_discount_for_changed_order_total(): void
    {
        $this->createGiftCard([
            'code' => 'RECALC-TEST',
            'balance' => 5000,
        ]);

        $this->service->apply('RECALC-TEST', 10000);
        $this->assertEquals(5000, $this->service->getApplied()['discount_amount']);

        $result = $this->service->recalculate(3000);

        $this->assertEquals(3000, $result['discount_amount']);
    }

    public function test_recalculate_returns_null_when_no_card_applied(): void
    {
        $result = $this->service->recalculate(10000);

        $this->assertNull($result);
    }

    public function test_recalculate_removes_card_if_no_longer_redeemable(): void
    {
        $giftCard = $this->createGiftCard(['code' => 'RECALC-INVALID']);
        $this->service->apply('RECALC-INVALID', 10000);

        $giftCard->status = GiftCardStatusEnum::CANCELLED;
        $giftCard->save();

        $result = $this->service->recalculate(10000);

        $this->assertNull($result);
        $this->assertNull($this->service->getApplied());
    }

    public function test_can_deduct_for_order(): void
    {
        $customer = $this->createCustomer();
        $giftCard = $this->createGiftCard([
            'code' => 'DEDUCT-TEST',
            'balance' => 5000,
        ]);
        $order = $this->createOrder([
            'user_id' => $customer->id,
            'amount' => 100.00,
        ]);

        $this->service->apply('DEDUCT-TEST', 10000);

        $result = $this->service->deductForOrder($order);

        $this->assertInstanceOf(GiftCard::class, $result);

        $giftCard->refresh();
        $this->assertEquals(0, $giftCard->balance);

        $order->refresh();
        $this->assertEquals(50.00, $order->amount);
        $this->assertEquals(5000, $order->getOrderMetadata('gift_card_discount'));
    }

    public function test_deduct_for_order_fully_redeems_card_when_balance_depleted(): void
    {
        $customer = $this->createCustomer();
        $giftCard = $this->createGiftCard([
            'code' => 'FULL-REDEEM',
            'balance' => 5000,
        ]);
        $order = $this->createOrder([
            'user_id' => $customer->id,
            'amount' => 100.00,
        ]);

        $this->service->apply('FULL-REDEEM', 5000);

        $this->service->deductForOrder($order);

        $giftCard->refresh();
        $this->assertEquals(0, $giftCard->balance);
        $this->assertEquals(GiftCardStatusEnum::REDEEMED, $giftCard->status->getValue());
        $this->assertNotNull($giftCard->redeemed_at);
        $this->assertEquals($customer->id, $giftCard->redeemed_by_customer_id);
    }

    public function test_deduct_for_order_updates_order_amount(): void
    {
        $customer = $this->createCustomer();
        $this->createGiftCard([
            'code' => 'ORDER-AMOUNT',
            'balance' => 10000,
        ]);
        $order = $this->createOrder([
            'user_id' => $customer->id,
            'amount' => 50.00,
        ]);

        $this->service->apply('ORDER-AMOUNT', 5000);

        $this->service->deductForOrder($order);

        $order->refresh();
        $this->assertEquals(0, $order->amount);
    }

    public function test_deduct_for_order_stores_metadata(): void
    {
        $customer = $this->createCustomer();
        $giftCard = $this->createGiftCard(['code' => 'METADATA-TEST']);
        $order = $this->createOrder(['user_id' => $customer->id]);

        $this->service->apply('METADATA-TEST', 10000);

        $this->service->deductForOrder($order);

        $this->assertEquals($giftCard->id, $order->getOrderMetadata('gift_card_id'));
        $this->assertNotNull($order->getOrderMetadata('gift_card_code'));
        $this->assertNotNull($order->getOrderMetadata('gift_card_discount'));
    }

    public function test_deduct_for_order_returns_null_when_no_card_applied(): void
    {
        $order = $this->createOrder();

        $result = $this->service->deductForOrder($order);

        $this->assertNull($result);
    }

    public function test_deduct_for_order_clears_session(): void
    {
        $customer = $this->createCustomer();
        $this->createGiftCard(['code' => 'CLEAR-SESSION']);
        $order = $this->createOrder(['user_id' => $customer->id]);

        $this->service->apply('CLEAR-SESSION', 10000);
        $this->assertNotNull($this->service->getApplied());

        $this->service->deductForOrder($order);

        $this->assertNull($this->service->getApplied());
    }

    public function test_get_discount_amount(): void
    {
        $this->createGiftCard([
            'code' => 'DISCOUNT-AMOUNT',
            'balance' => 7500,
        ]);

        $this->service->apply('DISCOUNT-AMOUNT', 10000);

        $this->assertEquals(7500, $this->service->getDiscountAmount());
    }

    public function test_get_discount_amount_returns_zero_when_no_card_applied(): void
    {
        $this->assertEquals(0, $this->service->getDiscountAmount());
    }

    public function test_partial_redemption_keeps_card_active(): void
    {
        $customer = $this->createCustomer();
        $giftCard = $this->createGiftCard([
            'code' => 'PARTIAL-REDEEM',
            'initial_value' => 10000,
            'balance' => 10000,
        ]);
        $order = $this->createOrder([
            'user_id' => $customer->id,
            'amount' => 30.00,
        ]);

        $this->service->apply('PARTIAL-REDEEM', 3000);

        $this->service->deductForOrder($order);

        $giftCard->refresh();
        $this->assertEquals(7000, $giftCard->balance);
        $this->assertEquals(GiftCardStatusEnum::ACTIVE, $giftCard->status->getValue());
        $this->assertNull($giftCard->redeemed_at);
    }

    public function test_concurrent_deduction_protection(): void
    {
        $customer = $this->createCustomer();
        $giftCard = $this->createGiftCard([
            'code' => 'CONCURRENT-TEST',
            'balance' => 5000,
        ]);

        $this->service->apply('CONCURRENT-TEST', 5000);

        $order1 = $this->createOrder(['user_id' => $customer->id, 'amount' => 50.00]);
        $this->service->deductForOrder($order1);

        Session::put('applied_gift_card', [
            'id' => $giftCard->id,
            'code' => $giftCard->code,
            'balance' => 5000,
            'discount_amount' => 5000,
        ]);

        $order2 = $this->createOrder(['user_id' => $customer->id, 'amount' => 50.00]);
        $result = $this->service->deductForOrder($order2);

        $this->assertNull($result);

        $giftCard->refresh();
        $this->assertEquals(0, $giftCard->balance);
    }
}
