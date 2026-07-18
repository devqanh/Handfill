<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GiftCardHttpTest extends BaseTestCase
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

    public function test_can_apply_gift_card_at_checkout(): void
    {
        $this->createGiftCard(['code' => 'CHECKOUT-APPLY']);

        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'CHECKOUT-APPLY',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => false]);
        $response->assertJsonStructure([
            'error',
            'message',
            'data' => [
                'id',
                'code',
                'balance',
                'discount_amount',
                'formatted_discount',
            ],
        ]);
    }

    public function test_apply_gift_card_fails_with_invalid_code(): void
    {
        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'INVALID-CODE',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => true]);
    }

    public function test_apply_gift_card_fails_when_disabled(): void
    {
        setting()->forceSet('e_wallet_gift_cards_enabled', false)->save();

        $this->createGiftCard(['code' => 'DISABLED-TEST']);

        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'DISABLED-TEST',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => true]);
    }

    public function test_apply_gift_card_fails_with_expired_card(): void
    {
        $this->createGiftCard([
            'code' => 'EXPIRED-CODE',
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'EXPIRED-CODE',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => true]);
    }

    public function test_apply_gift_card_fails_with_redeemed_card(): void
    {
        $this->createGiftCard([
            'code' => 'REDEEMED-CODE',
            'status' => GiftCardStatusEnum::REDEEMED,
        ]);

        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'REDEEMED-CODE',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => true]);
    }

    public function test_apply_gift_card_fails_with_zero_balance(): void
    {
        $this->createGiftCard([
            'code' => 'ZERO-BALANCE',
            'balance' => 0,
        ]);

        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'ZERO-BALANCE',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => true]);
    }

    public function test_can_remove_gift_card_from_checkout(): void
    {
        $this->createGiftCard(['code' => 'REMOVE-TEST']);

        $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'REMOVE-TEST',
        ]);

        $response = $this->postJson(route('public.gift-card.checkout.remove'));

        $response->assertOk();
        $response->assertJson(['error' => false]);
    }

    public function test_gift_card_code_is_case_insensitive(): void
    {
        $this->createGiftCard(['code' => 'UPPERCASE-CODE']);

        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => 'uppercase-code',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => false]);
    }

    public function test_gift_card_code_is_trimmed(): void
    {
        $this->createGiftCard(['code' => 'TRIMMED-CODE']);

        $response = $this->postJson(route('public.gift-card.checkout.apply'), [
            'gift_card_code' => '  TRIMMED-CODE  ',
        ]);

        $response->assertOk();
        $response->assertJson(['error' => false]);
    }

    public function test_apply_requires_gift_card_code(): void
    {
        $response = $this->postJson(route('public.gift-card.checkout.apply'), []);

        $response->assertStatus(422);
    }

    public function test_check_gift_card_balance_endpoint(): void
    {
        $giftCard = $this->createGiftCard([
            'code' => 'CHECK-BALANCE',
            'balance' => 7500,
        ]);

        $response = $this->postJson(route('public.gift-card.check'), [
            'code' => 'CHECK-BALANCE',
        ]);

        $response->assertOk();
        $response->assertJson([
            'error' => false,
            'data' => [
                'valid' => true,
                'balance' => 7500,
            ],
        ]);
    }

    public function test_check_invalid_gift_card_returns_error(): void
    {
        $response = $this->postJson(route('public.gift-card.check'), [
            'code' => 'INVALID-CHECK',
        ]);

        $response->assertOk();
        $response->assertJson([
            'error' => true,
        ]);
    }

}
