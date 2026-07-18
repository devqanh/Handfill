<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Http\Requests\Settings\WalletSettingRequest;
use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Services\GiftCardCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class UnifiedDiscountTest extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableGiftCards();
    }

    protected function enableGiftCards(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
        setting()->forceSet('e_wallet_gift_cards_enabled', true)->save();
    }

    protected function createGiftCard(array $attributes = []): GiftCard
    {
        return GiftCard::query()->create(array_merge([
            'code' => 'GC-TEST-' . strtoupper(uniqid()),
            'initial_value' => 1000000,
            'balance' => 1000000,
            'currency_code' => 'USD',
            'status' => GiftCardStatusEnum::ACTIVE,
        ], $attributes));
    }

    public function test_unified_discount_field_enabled_returns_true_by_default(): void
    {
        $this->assertTrue(unified_discount_field_enabled());
    }

    public function test_unified_discount_field_enabled_returns_false_when_disabled(): void
    {
        setting()->forceSet('e_wallet_unified_discount_field', false)->save();

        $this->assertFalse(unified_discount_field_enabled());
    }

    public function test_unified_discount_field_enabled_returns_false_when_gift_cards_disabled(): void
    {
        setting()->forceSet('e_wallet_gift_cards_enabled', false)->save();

        $this->assertFalse(unified_discount_field_enabled());
    }

    public function test_unified_discount_field_enabled_returns_true_when_explicitly_enabled(): void
    {
        setting()->forceSet('e_wallet_unified_discount_field', true)->save();

        $this->assertTrue(unified_discount_field_enabled());
    }

    public function test_unified_apply_rejects_empty_code(): void
    {
        $response = $this->postJson(route('public.gift-card.unified-discount.apply'), [
            'discount_code' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('discount_code');
    }

    public function test_unified_apply_with_valid_gift_card_code_when_coupon_already_applied(): void
    {
        $giftCard = $this->createGiftCard([
            'code' => 'GC-UNIFY-TEST',
            'initial_value' => 500000,
            'balance' => 500000,
        ]);

        Session::put('applied_coupon_code', 'EXISTING-COUPON');

        $response = $this->postJson(route('public.gift-card.unified-discount.apply'), [
            'discount_code' => 'GC-UNIFY-TEST',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.type', 'gift_card');
    }

    public function test_unified_apply_routes_to_coupon_first(): void
    {
        $response = $this->postJson(route('public.gift-card.unified-discount.apply'), [
            'discount_code' => 'SOME-CODE',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.type', 'coupon');
    }

    public function test_unified_apply_with_expired_gift_card_when_coupon_applied(): void
    {
        $this->createGiftCard([
            'code' => 'GC-EXPIRED-TEST',
            'expires_at' => now()->subDay(),
            'status' => GiftCardStatusEnum::EXPIRED,
        ]);

        Session::put('applied_coupon_code', 'EXISTING-COUPON');

        $response = $this->postJson(route('public.gift-card.unified-discount.apply'), [
            'discount_code' => 'GC-EXPIRED-TEST',
        ]);

        $response->assertJson(['error' => true]);
    }

    public function test_unified_apply_invalid_code_when_coupon_applied(): void
    {
        Session::put('applied_coupon_code', 'EXISTING-COUPON');

        $response = $this->postJson(route('public.gift-card.unified-discount.apply'), [
            'discount_code' => 'TOTALLY-INVALID-12345',
        ]);

        $response->assertJson(['error' => true]);
    }

    public function test_unified_remove_coupon(): void
    {
        Session::put('applied_coupon_code', 'TEST-COUPON');

        $response = $this->postJson(route('public.gift-card.unified-discount.remove-coupon'));

        $response->assertSuccessful();
    }

    public function test_unified_remove_gift_card(): void
    {
        $this->createGiftCard(['code' => 'GC-REMOVE-TEST']);

        $service = app(GiftCardCheckoutService::class);
        $service->apply('GC-REMOVE-TEST', 500000);

        $this->assertNotNull($service->getApplied());

        $response = $this->postJson(route('public.gift-card.unified-discount.remove-gift-card'));

        $response->assertSuccessful();
        $this->assertNull($service->getApplied());
    }

    public function test_unified_apply_skips_gift_card_when_already_applied(): void
    {
        $this->createGiftCard(['code' => 'GC-FIRST']);

        $service = app(GiftCardCheckoutService::class);
        $service->apply('GC-FIRST', 500000);

        Session::put('applied_coupon_code', 'EXISTING-COUPON');

        $response = $this->postJson(route('public.gift-card.unified-discount.apply'), [
            'discount_code' => 'NONEXISTENT-COUPON',
        ]);

        $response->assertJson(['error' => true]);
    }

    public function test_unified_apply_returns_type_in_response(): void
    {
        $this->createGiftCard([
            'code' => 'GC-TYPE-TEST',
            'initial_value' => 500000,
            'balance' => 500000,
        ]);

        Session::put('applied_coupon_code', 'EXISTING-COUPON');

        $response = $this->postJson(route('public.gift-card.unified-discount.apply'), [
            'discount_code' => 'GC-TYPE-TEST',
        ]);

        $response->assertSuccessful();
        $this->assertContains($response->json('data.type'), ['coupon', 'gift_card']);
    }

    public function test_setting_validation_accepts_valid_values(): void
    {
        $rules = (new WalletSettingRequest())->rules();

        $validator = Validator::make(
            ['e_wallet_unified_discount_field' => '1'],
            ['e_wallet_unified_discount_field' => $rules['e_wallet_unified_discount_field']]
        );

        $this->assertTrue($validator->passes());

        $validator = Validator::make(
            ['e_wallet_unified_discount_field' => '0'],
            ['e_wallet_unified_discount_field' => $rules['e_wallet_unified_discount_field']]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_setting_validation_rejects_invalid_values(): void
    {
        $rules = (new WalletSettingRequest())->rules();

        $validator = Validator::make(
            ['e_wallet_unified_discount_field' => 'yes'],
            ['e_wallet_unified_discount_field' => $rules['e_wallet_unified_discount_field']]
        );

        $this->assertFalse($validator->passes());
    }
}
