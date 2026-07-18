<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\EWallet\Helpers\WalletHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletHelperTest extends BaseTestCase
{
    use RefreshDatabase;

    protected WalletHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = app(WalletHelper::class);
    }

    public function test_is_enabled_returns_true_when_enabled(): void
    {
        setting()->forceSet('e_wallet_enable_e_wallet', true)->save();

        $this->assertTrue($this->helper->isEnabled());
    }

    public function test_is_enabled_returns_false_when_disabled(): void
    {
        setting()->forceSet('e_wallet_enable_e_wallet', false)->save();

        $this->assertFalse($this->helper->isEnabled());
    }

    public function test_is_enabled_defaults_to_true(): void
    {
        $this->assertTrue($this->helper->isEnabled());
    }

    public function test_allow_negative_balance_returns_true_when_enabled(): void
    {
        setting()->forceSet('e_wallet_allow_negative_balance', true)->save();

        $this->assertTrue($this->helper->allowNegativeBalance());
    }

    public function test_allow_negative_balance_returns_false_when_disabled(): void
    {
        setting()->forceSet('e_wallet_allow_negative_balance', false)->save();

        $this->assertFalse($this->helper->allowNegativeBalance());
    }

    public function test_allow_negative_balance_defaults_to_false(): void
    {
        $this->assertFalse($this->helper->allowNegativeBalance());
    }

    public function test_is_top_up_enabled_returns_true_when_enabled(): void
    {
        setting()->forceSet('e_wallet_enable_top_up', true)->save();

        $this->assertTrue($this->helper->isTopUpEnabled());
    }

    public function test_is_top_up_enabled_returns_false_when_disabled(): void
    {
        setting()->forceSet('e_wallet_enable_top_up', false)->save();

        $this->assertFalse($this->helper->isTopUpEnabled());
    }

    public function test_is_top_up_enabled_defaults_to_true(): void
    {
        $this->assertTrue($this->helper->isTopUpEnabled());
    }

    public function test_get_min_top_up_returns_configured_value(): void
    {
        setting()->forceSet('e_wallet_min_top_up', 500)->save();

        $this->assertEquals(500, $this->helper->getMinTopUp());
    }

    public function test_get_min_top_up_returns_default(): void
    {
        $this->assertEquals(10, $this->helper->getMinTopUp());
    }

    public function test_get_max_top_up_returns_configured_value(): void
    {
        setting()->forceSet('e_wallet_max_top_up', 50000)->save();

        $this->assertEquals(50000, $this->helper->getMaxTopUp());
    }

    public function test_get_max_top_up_returns_default(): void
    {
        $this->assertEquals(100000000, $this->helper->getMaxTopUp());
    }

    public function test_get_default_currency_returns_string(): void
    {
        $currency = $this->helper->getDefaultCurrency();

        $this->assertIsString($currency);
        $this->assertNotEmpty($currency);
    }

    public function test_view_path_returns_plugin_view_path(): void
    {
        $viewPath = $this->helper->viewPath('wallet');

        $this->assertStringContainsString('plugins/e-wallet::', $viewPath);
    }

    public function test_view_path_returns_correct_view_name(): void
    {
        $viewPath = $this->helper->viewPath('topup.form');

        $this->assertStringContainsString('topup.form', $viewPath);
    }

    public function test_min_top_up_can_be_zero(): void
    {
        setting()->forceSet('e_wallet_min_top_up', 0)->save();

        $this->assertEquals(0, $this->helper->getMinTopUp());
    }

    public function test_settings_are_integers(): void
    {
        setting()->forceSet('e_wallet_min_top_up', '2500')->save();
        setting()->forceSet('e_wallet_max_top_up', '75000')->save();

        $this->assertIsInt($this->helper->getMinTopUp());
        $this->assertIsInt($this->helper->getMaxTopUp());
        $this->assertEquals(2500, $this->helper->getMinTopUp());
        $this->assertEquals(75000, $this->helper->getMaxTopUp());
    }

    public function test_settings_are_boolean(): void
    {
        setting()->forceSet('e_wallet_enable_e_wallet', '1')->save();
        setting()->forceSet('e_wallet_allow_negative_balance', '0')->save();
        setting()->forceSet('e_wallet_enable_top_up', true)->save();

        $this->assertIsBool($this->helper->isEnabled());
        $this->assertIsBool($this->helper->allowNegativeBalance());
        $this->assertIsBool($this->helper->isTopUpEnabled());
    }
}
