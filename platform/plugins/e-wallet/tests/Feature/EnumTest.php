<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnumTest extends BaseTestCase
{
    use RefreshDatabase;

    public function test_transaction_type_enum_has_all_values(): void
    {
        $values = TransactionTypeEnum::toArray();

        $this->assertArrayHasKey('TOP_UP', $values);
        $this->assertArrayHasKey('PAYMENT', $values);
        $this->assertArrayHasKey('REFUND', $values);
        $this->assertArrayHasKey('ADMIN_ADJUSTMENT', $values);
        $this->assertArrayHasKey('VENDOR_PAYOUT', $values);
    }

    public function test_transaction_type_enum_constants(): void
    {
        $this->assertEquals('top_up', TransactionTypeEnum::TOP_UP);
        $this->assertEquals('payment', TransactionTypeEnum::PAYMENT);
        $this->assertEquals('refund', TransactionTypeEnum::REFUND);
        $this->assertEquals('admin_adjustment', TransactionTypeEnum::ADMIN_ADJUSTMENT);
        $this->assertEquals('vendor_payout', TransactionTypeEnum::VENDOR_PAYOUT);
    }

    public function test_transaction_type_enum_labels(): void
    {
        $topUp = (new TransactionTypeEnum())->make(TransactionTypeEnum::TOP_UP);
        $payment = (new TransactionTypeEnum())->make(TransactionTypeEnum::PAYMENT);
        $refund = (new TransactionTypeEnum())->make(TransactionTypeEnum::REFUND);
        $adjustment = (new TransactionTypeEnum())->make(TransactionTypeEnum::ADMIN_ADJUSTMENT);
        $payout = (new TransactionTypeEnum())->make(TransactionTypeEnum::VENDOR_PAYOUT);

        $this->assertNotEmpty($topUp->label());
        $this->assertNotEmpty($payment->label());
        $this->assertNotEmpty($refund->label());
        $this->assertNotEmpty($adjustment->label());
        $this->assertNotEmpty($payout->label());
    }

    public function test_transaction_type_enum_colors(): void
    {
        $topUp = (new TransactionTypeEnum())->make(TransactionTypeEnum::TOP_UP);
        $payment = (new TransactionTypeEnum())->make(TransactionTypeEnum::PAYMENT);
        $refund = (new TransactionTypeEnum())->make(TransactionTypeEnum::REFUND);
        $adjustment = (new TransactionTypeEnum())->make(TransactionTypeEnum::ADMIN_ADJUSTMENT);
        $payout = (new TransactionTypeEnum())->make(TransactionTypeEnum::VENDOR_PAYOUT);

        $this->assertEquals('success', $topUp->color());
        $this->assertEquals('primary', $payment->color());
        $this->assertEquals('info', $refund->color());
        $this->assertEquals('warning', $adjustment->color());
        $this->assertEquals('secondary', $payout->color());
    }

    public function test_transaction_type_enum_badge(): void
    {
        foreach (TransactionTypeEnum::values() as $enum) {
            $badge = $enum->badge();
            $this->assertStringContainsString('badge', $badge);
            $this->assertStringContainsString($enum->color(), $badge);
        }
    }

    public function test_transaction_status_enum_has_all_values(): void
    {
        $values = TransactionStatusEnum::toArray();

        $this->assertArrayHasKey('PENDING', $values);
        $this->assertArrayHasKey('COMPLETED', $values);
        $this->assertArrayHasKey('FAILED', $values);
        $this->assertArrayHasKey('CANCELLED', $values);
    }

    public function test_transaction_status_enum_constants(): void
    {
        $this->assertEquals('pending', TransactionStatusEnum::PENDING);
        $this->assertEquals('completed', TransactionStatusEnum::COMPLETED);
        $this->assertEquals('failed', TransactionStatusEnum::FAILED);
        $this->assertEquals('cancelled', TransactionStatusEnum::CANCELLED);
    }

    public function test_transaction_status_enum_labels(): void
    {
        $pending = (new TransactionStatusEnum())->make(TransactionStatusEnum::PENDING);
        $completed = (new TransactionStatusEnum())->make(TransactionStatusEnum::COMPLETED);
        $failed = (new TransactionStatusEnum())->make(TransactionStatusEnum::FAILED);
        $cancelled = (new TransactionStatusEnum())->make(TransactionStatusEnum::CANCELLED);

        $this->assertNotEmpty($pending->label());
        $this->assertNotEmpty($completed->label());
        $this->assertNotEmpty($failed->label());
        $this->assertNotEmpty($cancelled->label());
    }

    public function test_transaction_status_enum_colors(): void
    {
        $pending = (new TransactionStatusEnum())->make(TransactionStatusEnum::PENDING);
        $completed = (new TransactionStatusEnum())->make(TransactionStatusEnum::COMPLETED);
        $failed = (new TransactionStatusEnum())->make(TransactionStatusEnum::FAILED);
        $cancelled = (new TransactionStatusEnum())->make(TransactionStatusEnum::CANCELLED);

        $this->assertEquals('warning', $pending->color());
        $this->assertEquals('success', $completed->color());
        $this->assertEquals('danger', $failed->color());
        $this->assertEquals('secondary', $cancelled->color());
    }

    public function test_transaction_status_enum_badge(): void
    {
        foreach (TransactionStatusEnum::values() as $enum) {
            $badge = $enum->badge();
            $this->assertStringContainsString('badge', $badge);
            $this->assertStringContainsString($enum->color(), $badge);
        }
    }

    public function test_transaction_status_enum_to_html(): void
    {
        foreach (TransactionStatusEnum::values() as $enum) {
            $html = $enum->toHtml();
            $this->assertStringContainsString('badge', $html);
        }
    }

    public function test_topup_status_enum_has_all_values(): void
    {
        $values = TopUpStatusEnum::toArray();

        $this->assertArrayHasKey('PENDING', $values);
        $this->assertArrayHasKey('PROCESSING', $values);
        $this->assertArrayHasKey('COMPLETED', $values);
        $this->assertArrayHasKey('FAILED', $values);
        $this->assertArrayHasKey('CANCELLED', $values);
    }

    public function test_topup_status_enum_constants(): void
    {
        $this->assertEquals('pending', TopUpStatusEnum::PENDING);
        $this->assertEquals('processing', TopUpStatusEnum::PROCESSING);
        $this->assertEquals('completed', TopUpStatusEnum::COMPLETED);
        $this->assertEquals('failed', TopUpStatusEnum::FAILED);
        $this->assertEquals('cancelled', TopUpStatusEnum::CANCELLED);
    }

    public function test_topup_status_enum_labels(): void
    {
        $pending = (new TopUpStatusEnum())->make(TopUpStatusEnum::PENDING);
        $processing = (new TopUpStatusEnum())->make(TopUpStatusEnum::PROCESSING);
        $completed = (new TopUpStatusEnum())->make(TopUpStatusEnum::COMPLETED);
        $failed = (new TopUpStatusEnum())->make(TopUpStatusEnum::FAILED);
        $cancelled = (new TopUpStatusEnum())->make(TopUpStatusEnum::CANCELLED);

        $this->assertNotEmpty($pending->label());
        $this->assertNotEmpty($processing->label());
        $this->assertNotEmpty($completed->label());
        $this->assertNotEmpty($failed->label());
        $this->assertNotEmpty($cancelled->label());
    }

    public function test_topup_status_enum_colors(): void
    {
        $pending = (new TopUpStatusEnum())->make(TopUpStatusEnum::PENDING);
        $processing = (new TopUpStatusEnum())->make(TopUpStatusEnum::PROCESSING);
        $completed = (new TopUpStatusEnum())->make(TopUpStatusEnum::COMPLETED);
        $failed = (new TopUpStatusEnum())->make(TopUpStatusEnum::FAILED);
        $cancelled = (new TopUpStatusEnum())->make(TopUpStatusEnum::CANCELLED);

        $this->assertEquals('warning', $pending->color());
        $this->assertEquals('info', $processing->color());
        $this->assertEquals('success', $completed->color());
        $this->assertEquals('danger', $failed->color());
        $this->assertEquals('secondary', $cancelled->color());
    }

    public function test_topup_status_enum_to_html(): void
    {
        foreach (TopUpStatusEnum::values() as $enum) {
            $html = $enum->toHtml();
            $this->assertStringContainsString('badge', $html);
            $this->assertStringContainsString($enum->color(), $html);
        }
    }

    public function test_all_enums_have_label_method(): void
    {
        foreach (TransactionTypeEnum::values() as $enum) {
            $this->assertIsString($enum->label());
        }

        foreach (TransactionStatusEnum::values() as $enum) {
            $this->assertIsString($enum->label());
        }

        foreach (TopUpStatusEnum::values() as $enum) {
            $this->assertIsString($enum->label());
        }
    }

    public function test_enum_validation(): void
    {
        $this->assertTrue(TransactionTypeEnum::isValid('top_up'));
        $this->assertTrue(TransactionTypeEnum::isValid('payment'));
        $this->assertFalse(TransactionTypeEnum::isValid('invalid_type'));

        $this->assertTrue(TransactionStatusEnum::isValid('pending'));
        $this->assertTrue(TransactionStatusEnum::isValid('completed'));
        $this->assertFalse(TransactionStatusEnum::isValid('invalid_status'));

        $this->assertTrue(TopUpStatusEnum::isValid('pending'));
        $this->assertTrue(TopUpStatusEnum::isValid('processing'));
        $this->assertFalse(TopUpStatusEnum::isValid('invalid_status'));
    }

    public function test_enum_keys(): void
    {
        $transactionTypeKeys = TransactionTypeEnum::keys();
        $this->assertContains('TOP_UP', $transactionTypeKeys);
        $this->assertContains('PAYMENT', $transactionTypeKeys);

        $transactionStatusKeys = TransactionStatusEnum::keys();
        $this->assertContains('PENDING', $transactionStatusKeys);
        $this->assertContains('COMPLETED', $transactionStatusKeys);

        $topUpStatusKeys = TopUpStatusEnum::keys();
        $this->assertContains('PENDING', $topUpStatusKeys);
        $this->assertContains('PROCESSING', $topUpStatusKeys);
    }
}
