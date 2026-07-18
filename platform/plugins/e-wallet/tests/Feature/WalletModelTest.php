<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletModelTest extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_can_create_wallet(): void
    {
        $customer = $this->createCustomer();

        $wallet = $this->createWallet($customer, 10000);

        $this->assertDatabaseHas('ec_wallets', [
            'customer_id' => $customer->id,
            'balance' => 10000,
            'currency_code' => 'USD',
        ]);
    }

    public function test_wallet_belongs_to_customer(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $this->assertNotNull($wallet->customer);
        $this->assertEquals($customer->id, $wallet->customer->id);
        $this->assertEquals('Test Customer', $wallet->customer->name);
    }

    public function test_wallet_has_many_transactions(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'type' => 'top_up',
            'status' => 'completed',
            'amount' => 5000,
            'balance_before' => 5000,
            'balance_after' => 10000,
        ]);

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'type' => 'payment',
            'status' => 'completed',
            'amount' => -2000,
            'balance_before' => 10000,
            'balance_after' => 8000,
        ]);

        $this->assertCount(2, $wallet->transactions);
    }

    public function test_wallet_formatted_balance_attribute(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 12345);

        $this->assertNotEmpty($wallet->formatted_balance);
    }

    public function test_wallet_has_sufficient_balance(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        $this->assertTrue($wallet->hasSufficientBalance(5000));
        $this->assertTrue($wallet->hasSufficientBalance(10000));
        $this->assertFalse($wallet->hasSufficientBalance(15000));
    }

    public function test_wallet_has_sufficient_balance_with_zero(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);

        $this->assertTrue($wallet->hasSufficientBalance(0));
        $this->assertFalse($wallet->hasSufficientBalance(1));
    }

    public function test_customer_can_have_only_one_wallet(): void
    {
        $customer = $this->createCustomer();

        Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => 5000,
            'currency_code' => 'USD',
        ]);

        $wallets = Wallet::query()->where('customer_id', $customer->id)->get();
        $this->assertCount(1, $wallets);
    }

    public function test_wallet_balance_can_be_zero(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);

        $this->assertEquals(0, $wallet->balance);
    }

    public function test_wallet_balance_can_be_negative_if_allowed(): void
    {
        $customer = $this->createCustomer();

        $wallet = Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => -5000,
            'currency_code' => 'USD',
        ]);

        $this->assertEquals(-5000, $wallet->balance);
    }

    public function test_wallet_stores_currency_code(): void
    {
        $customer = $this->createCustomer();

        $wallet = Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'currency_code' => 'EUR',
        ]);

        $this->assertEquals('EUR', $wallet->currency_code);
    }

    public function test_wallet_update_balance(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 5000);

        $wallet->update(['balance' => 7500]);

        $this->assertDatabaseHas('ec_wallets', [
            'id' => $wallet->id,
            'balance' => 7500,
        ]);
    }

    public function test_wallet_delete(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 5000);
        $walletId = $wallet->id;

        $wallet->delete();

        $this->assertDatabaseMissing('ec_wallets', [
            'id' => $walletId,
        ]);
    }
}
