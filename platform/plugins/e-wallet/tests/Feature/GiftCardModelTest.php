<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GiftCardModelTest extends BaseTestCase
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

    public function test_can_create_gift_card(): void
    {
        $giftCard = $this->createGiftCard([
            'code' => 'GIFT-1234-5678',
            'initial_value' => 5000,
            'balance' => 5000,
        ]);

        $this->assertInstanceOf(GiftCard::class, $giftCard);
        $this->assertEquals('GIFT-1234-5678', $giftCard->code);
        $this->assertEquals(5000, $giftCard->initial_value);
        $this->assertEquals(5000, $giftCard->balance);
        $this->assertEquals(GiftCardStatusEnum::ACTIVE, $giftCard->status->getValue());
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $giftCard = $this->createGiftCard(['status' => GiftCardStatusEnum::ACTIVE]);

        $this->assertTrue($giftCard->isActive());
    }

    public function test_is_active_returns_false_for_non_active_status(): void
    {
        $redeemed = $this->createGiftCard(['status' => GiftCardStatusEnum::REDEEMED]);
        $cancelled = $this->createGiftCard(['status' => GiftCardStatusEnum::CANCELLED]);

        $this->assertFalse($redeemed->isActive());
        $this->assertFalse($cancelled->isActive());
    }

    public function test_is_expired_returns_true_when_expires_at_is_past(): void
    {
        $giftCard = $this->createGiftCard([
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue($giftCard->isExpired());
    }

    public function test_is_expired_returns_false_when_expires_at_is_future(): void
    {
        $giftCard = $this->createGiftCard([
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertFalse($giftCard->isExpired());
    }

    public function test_is_expired_returns_false_when_no_expiry_date(): void
    {
        $giftCard = $this->createGiftCard(['expires_at' => null]);

        $this->assertFalse($giftCard->isExpired());
    }

    public function test_is_redeemable_returns_true_for_valid_gift_card(): void
    {
        $giftCard = $this->createGiftCard([
            'status' => GiftCardStatusEnum::ACTIVE,
            'balance' => 5000,
            'expires_at' => Carbon::now()->addMonth(),
        ]);

        $this->assertTrue($giftCard->isRedeemable());
    }

    public function test_is_redeemable_returns_false_when_not_active(): void
    {
        $giftCard = $this->createGiftCard([
            'status' => GiftCardStatusEnum::REDEEMED,
            'balance' => 5000,
        ]);

        $this->assertFalse($giftCard->isRedeemable());
    }

    public function test_is_redeemable_returns_false_when_expired(): void
    {
        $giftCard = $this->createGiftCard([
            'status' => GiftCardStatusEnum::ACTIVE,
            'balance' => 5000,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->assertFalse($giftCard->isRedeemable());
    }

    public function test_is_redeemable_returns_false_when_zero_balance(): void
    {
        $giftCard = $this->createGiftCard([
            'status' => GiftCardStatusEnum::ACTIVE,
            'balance' => 0,
        ]);

        $this->assertFalse($giftCard->isRedeemable());
    }

    public function test_formatted_balance_attribute(): void
    {
        $giftCard = $this->createGiftCard(['balance' => 10000]);

        $this->assertIsString($giftCard->formatted_balance);
        $this->assertStringContainsString('100', $giftCard->formatted_balance);
    }

    public function test_formatted_initial_value_attribute(): void
    {
        $giftCard = $this->createGiftCard(['initial_value' => 5000]);

        $this->assertIsString($giftCard->formatted_initial_value);
        $this->assertStringContainsString('50', $giftCard->formatted_initial_value);
    }

    public function test_masked_code_attribute_for_long_code(): void
    {
        $giftCard = $this->createGiftCard(['code' => 'GIFT-1234-5678-9012']);

        $maskedCode = $giftCard->masked_code;

        $this->assertStringStartsWith('GIFT', $maskedCode);
        $this->assertStringEndsWith('9012', $maskedCode);
        $this->assertStringContainsString('*', $maskedCode);
    }

    public function test_masked_code_attribute_for_short_code(): void
    {
        $giftCard = $this->createGiftCard(['code' => 'GIFT1234']);

        $maskedCode = $giftCard->masked_code;

        $this->assertEquals('GIFT1234', $maskedCode);
    }

    public function test_customer_relationship(): void
    {
        $customer = $this->createCustomer();
        $giftCard = $this->createGiftCard(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $giftCard->customer);
        $this->assertEquals($customer->id, $giftCard->customer->id);
    }

    public function test_redeemed_by_relationship(): void
    {
        $customer = $this->createCustomer();
        $giftCard = $this->createGiftCard(['redeemed_by_customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $giftCard->redeemedBy);
        $this->assertEquals($customer->id, $giftCard->redeemedBy->id);
    }

    public function test_gift_card_status_transitions(): void
    {
        $giftCard = $this->createGiftCard(['status' => GiftCardStatusEnum::ACTIVE]);

        $this->assertTrue($giftCard->isActive());

        $giftCard->status = GiftCardStatusEnum::REDEEMED;
        $giftCard->save();

        $this->assertFalse($giftCard->isActive());
        $this->assertEquals(GiftCardStatusEnum::REDEEMED, $giftCard->status->getValue());
    }

    public function test_gift_card_balance_can_be_reduced(): void
    {
        $giftCard = $this->createGiftCard(['balance' => 10000]);

        $giftCard->balance -= 3000;
        $giftCard->save();

        $this->assertEquals(7000, $giftCard->fresh()->balance);
    }

    public function test_gift_card_with_metadata(): void
    {
        $metadata = ['source' => 'promotion', 'campaign_id' => 123];
        $giftCard = $this->createGiftCard(['metadata' => $metadata]);

        $this->assertEquals($metadata, $giftCard->metadata);
        $this->assertEquals('promotion', $giftCard->metadata['source']);
    }
}
