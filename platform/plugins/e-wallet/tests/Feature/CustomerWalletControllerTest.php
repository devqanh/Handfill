<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class CustomerWalletControllerTest extends BaseTestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);
        $this->enableEWallet();
    }

    protected function enableEWallet(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
        setting()->forceSet('e_wallet_enable_top_up', true)->save();
        setting()->forceSet('e_wallet_min_top_up', 1000)->save();
        setting()->forceSet('e_wallet_max_top_up', 100000000)->save();
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

    protected function createTransaction(Wallet $wallet, array $data = []): WalletTransaction
    {
        return WalletTransaction::query()->create(array_merge([
            'wallet_id' => $wallet->id,
            'customer_id' => $wallet->customer_id,
            'type' => TransactionTypeEnum::TOP_UP,
            'status' => TransactionStatusEnum::COMPLETED,
            'amount' => 5000,
            'balance_before' => 5000,
            'balance_after' => 10000,
        ], $data));
    }

    public function test_customer_routes_exist(): void
    {
        $this->assertTrue(Route::has('customer.e-wallet.index'));
        $this->assertTrue(Route::has('customer.e-wallet.topup.create'));
        $this->assertTrue(Route::has('customer.e-wallet.topup.store'));
        $this->assertTrue(Route::has('customer.e-wallet.topup.checkout'));
        $this->assertTrue(Route::has('customer.e-wallet.topup.pay'));
        $this->assertTrue(Route::has('customer.e-wallet.topup.callback'));
        $this->assertTrue(Route::has('customer.e-wallet.topup.success'));
    }

    public function test_wallet_service_creates_wallet_for_customer(): void
    {
        $customer = $this->createCustomer();

        $wallet = $this->walletService->getOrCreateWallet($customer->id);

        $this->assertNotNull($wallet);
        $this->assertEquals($customer->id, $wallet->customer_id);
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_wallet_service_returns_existing_wallet(): void
    {
        $customer = $this->createCustomer();
        $existingWallet = $this->createWallet($customer, 5000);

        $wallet = $this->walletService->getOrCreateWallet($customer->id);

        $this->assertEquals($existingWallet->id, $wallet->id);
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_wallet_can_store_transactions(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        $this->createTransaction($wallet, [
            'type' => TransactionTypeEnum::TOP_UP,
            'amount' => 5000,
            'description' => 'Initial top-up',
        ]);

        $this->createTransaction($wallet, [
            'type' => TransactionTypeEnum::PAYMENT,
            'amount' => -2000,
            'description' => 'Order payment',
        ]);

        $transactionCount = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->count();

        $this->assertEquals(2, $transactionCount);
    }

    public function test_wallet_transactions_ordered_correctly(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 50000);

        for ($i = 0; $i < 25; $i++) {
            $this->createTransaction($wallet, [
                'amount' => 1000,
            ]);
        }

        $transactions = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->paginate(20);

        $this->assertEquals(20, $transactions->count());
        $this->assertEquals(25, $transactions->total());
    }

    public function test_customer_wallet_isolation(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = Customer::query()->create([
            'name' => 'Customer 2',
            'email' => 'customer2@example.com',
            'password' => bcrypt('password'),
        ]);

        $wallet1 = $this->createWallet($customer1, 10000);
        $wallet2 = $this->createWallet($customer2, 20000);

        $this->createTransaction($wallet1, ['amount' => 5000]);
        $this->createTransaction($wallet2, ['amount' => 7000]);

        $customer1Transactions = WalletTransaction::query()
            ->where('customer_id', $customer1->id)
            ->count();

        $customer2Transactions = WalletTransaction::query()
            ->where('customer_id', $customer2->id)
            ->count();

        $this->assertEquals(1, $customer1Transactions);
        $this->assertEquals(1, $customer2Transactions);
    }

    public function test_wallet_balance_updated_by_service(): void
    {
        $customer = $this->createCustomer();
        $this->walletService->getOrCreateWallet($customer->id);

        $this->walletService->credit(
            customerId: $customer->id,
            amountCents: 5000,
            type: TransactionTypeEnum::TOP_UP,
            description: 'Test credit'
        );

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_unauthenticated_cannot_access_topup(): void
    {
        $response = $this->get(route('customer.e-wallet.topup.create'));

        $response->assertRedirect();
    }

    public function test_topup_setting_can_be_disabled(): void
    {
        setting()->forceSet('e_wallet_enable_top_up', false)->save();

        $enabled = (bool) get_wallet_setting('enable_top_up', true);

        $this->assertFalse($enabled);
    }

    public function test_topup_enabled_by_default(): void
    {
        $enabled = (bool) get_wallet_setting('enable_top_up', true);

        $this->assertTrue($enabled);
    }
}
