<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\ACL\Models\User;
use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTopUp;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class AdminControllerTest extends BaseTestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'super_user' => true,
        ]);

        $this->service = app(WalletService::class);
        $this->enableEWallet();
    }

    protected function enableEWallet(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
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

    public function test_admin_routes_exist(): void
    {
        $this->assertTrue(Route::has('e-wallet.index'));
        $this->assertTrue(Route::has('e-wallet.wallets.index'));
        $this->assertTrue(Route::has('e-wallet.wallets.show'));
        $this->assertTrue(Route::has('e-wallet.wallets.adjust'));
        $this->assertTrue(Route::has('e-wallet.wallets.adjust.store'));
        $this->assertTrue(Route::has('e-wallet.transactions.index'));
        $this->assertTrue(Route::has('e-wallet.transactions.show'));
        $this->assertTrue(Route::has('e-wallet.topups.index'));
        $this->assertTrue(Route::has('e-wallet.topups.show'));
        $this->assertTrue(Route::has('e-wallet.topups.complete'));
        $this->assertTrue(Route::has('e-wallet.topups.cancel'));
        $this->assertTrue(Route::has('e-wallet.settings.index'));
        $this->assertTrue(Route::has('e-wallet.settings.update'));
    }

    public function test_unauthenticated_cannot_access_admin(): void
    {
        $response = $this->get(route('e-wallet.index'));

        $response->assertRedirect();
    }

    public function test_wallet_creation(): void
    {
        $customer = $this->createCustomer();

        $wallet = $this->createWallet($customer, 5000);

        $this->assertDatabaseHas('ec_wallets', [
            'customer_id' => $customer->id,
            'balance' => 5000,
        ]);
    }

    public function test_can_adjust_balance_credit(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 5000);

        $this->service->adjustBalance($customer->id, 2500, 'Admin bonus');

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(7500, $wallet->balance);
    }

    public function test_can_adjust_balance_debit(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 10000);

        $this->service->adjustBalance($customer->id, -3000, 'Admin deduction');

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(7000, $wallet->balance);
    }

    public function test_adjust_balance_creates_transaction(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 5000);

        $this->service->adjustBalance($customer->id, 1000, 'Test adjustment');

        $this->assertDatabaseHas('ec_wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 'admin_adjustment',
            'amount' => 1000,
        ]);
    }

    public function test_wallet_transaction_creation(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'type' => 'top_up',
            'status' => 'completed',
            'amount' => 5000,
            'balance_before' => 5000,
            'balance_after' => 10000,
            'description' => 'Test top-up',
        ]);

        $this->assertDatabaseHas('ec_wallet_transactions', [
            'id' => $transaction->id,
            'customer_id' => $customer->id,
            'amount' => 5000,
        ]);
    }

    public function test_wallet_topup_creation(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $topup = WalletTopUp::query()->create([
            'customer_id' => $customer->id,
            'wallet_id' => $wallet->id,
            'code' => 'TU-TEST1234',
            'amount' => 10000,
            'currency_code' => 'USD',
            'converted_amount' => 10000,
            'wallet_currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'status' => TopUpStatusEnum::PENDING,
        ]);

        $this->assertDatabaseHas('ec_wallet_topups', [
            'id' => $topup->id,
            'customer_id' => $customer->id,
            'code' => 'TU-TEST1234',
            'status' => 'pending',
        ]);
    }

    public function test_wallet_topup_update(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $topup = WalletTopUp::query()->create([
            'customer_id' => $customer->id,
            'wallet_id' => $wallet->id,
            'code' => 'TU-TEST1234',
            'amount' => 10000,
            'currency_code' => 'USD',
            'converted_amount' => 10000,
            'wallet_currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'status' => TopUpStatusEnum::PENDING,
        ]);

        $topup->update([
            'status' => TopUpStatusEnum::COMPLETED,
            'payment_id' => 'payment_123',
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertDatabaseHas('ec_wallet_topups', [
            'id' => $topup->id,
            'status' => 'completed',
            'payment_id' => 'payment_123',
        ]);
    }

    public function test_wallet_topup_cancel(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $topup = WalletTopUp::query()->create([
            'customer_id' => $customer->id,
            'wallet_id' => $wallet->id,
            'code' => 'TU-TEST1234',
            'amount' => 10000,
            'currency_code' => 'USD',
            'converted_amount' => 10000,
            'wallet_currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'status' => TopUpStatusEnum::PENDING,
        ]);

        $topup->update(['status' => TopUpStatusEnum::CANCELLED]);

        $this->assertDatabaseHas('ec_wallet_topups', [
            'id' => $topup->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_wallet_delete(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);
        $walletId = $wallet->id;

        $wallet->delete();

        $this->assertDatabaseMissing('ec_wallets', [
            'id' => $walletId,
        ]);
    }

    public function test_transaction_delete(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'type' => 'top_up',
            'status' => 'completed',
            'amount' => 5000,
            'balance_before' => 5000,
            'balance_after' => 10000,
        ]);

        $transactionId = $transaction->id;
        $transaction->delete();

        $this->assertDatabaseMissing('ec_wallet_transactions', [
            'id' => $transactionId,
        ]);
    }

    public function test_multiple_wallets_for_different_customers(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = Customer::query()->create([
            'name' => 'Customer 2',
            'email' => 'customer2@example.com',
            'password' => bcrypt('password'),
        ]);

        $wallet1 = $this->createWallet($customer1, 5000);
        $wallet2 = $this->createWallet($customer2, 7500);

        $this->assertNotEquals($wallet1->id, $wallet2->id);
        $this->assertEquals(5000, $wallet1->balance);
        $this->assertEquals(7500, $wallet2->balance);
    }

    public function test_wallet_transaction_count(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        for ($i = 0; $i < 5; $i++) {
            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customer->id,
                'type' => 'top_up',
                'status' => 'completed',
                'amount' => 1000,
                'balance_before' => $i * 1000,
                'balance_after' => ($i + 1) * 1000,
            ]);
        }

        $count = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->count();

        $this->assertEquals(5, $count);
    }
}
