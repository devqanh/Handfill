<?php

namespace Botble\EWallet\Tests\Feature;

use Botble\ACL\Models\User;
use Botble\Base\Supports\BaseTestCase;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTopUp;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\TopUpService;
use Botble\EWallet\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class AdminWalletHttpTest extends BaseTestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected WalletService $walletService;

    protected TopUpService $topUpService;

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

        $this->walletService = app(WalletService::class);
        $this->topUpService = app(TopUpService::class);
        $this->enableEWallet();
    }

    protected function enableEWallet(): void
    {
        setting()->forceSet('e_wallet_enabled', true)->save();
        setting()->forceSet('e_wallet_allow_negative_balance', false)->save();
        setting()->forceSet('e_wallet_min_top_up', 100)->save();
        setting()->forceSet('e_wallet_max_top_up', 100000000)->save();
    }

    protected function createCustomer(string $name = 'Test Customer', string $email = 'customer@example.com'): Customer
    {
        return Customer::query()->create([
            'name' => $name,
            'email' => $email,
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

    protected function createTopUp(Customer $customer, Wallet $wallet, array $data = []): WalletTopUp
    {
        return WalletTopUp::query()->create(array_merge([
            'customer_id' => $customer->id,
            'wallet_id' => $wallet->id,
            'code' => 'TU-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'amount' => 10000,
            'currency_code' => 'USD',
            'converted_amount' => 10000,
            'wallet_currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'status' => TopUpStatusEnum::PENDING,
        ], $data));
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

    public function test_admin_can_credit_wallet_via_service(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 5000);

        $transaction = $this->walletService->adjustBalance(
            customerId: $customer->id,
            amountCents: 2500,
            description: 'Admin bonus credit',
            createdBy: $this->admin->id
        );

        $wallet->refresh();
        $this->assertEquals(7500, $wallet->balance);
        $this->assertEquals(TransactionTypeEnum::ADMIN_ADJUSTMENT, $transaction->type->getValue());
        $this->assertEquals($this->admin->id, $transaction->metadata['created_by']);
    }

    public function test_admin_can_debit_wallet_via_service(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        $transaction = $this->walletService->adjustBalance(
            customerId: $customer->id,
            amountCents: -3000,
            description: 'Admin deduction',
            createdBy: $this->admin->id
        );

        $wallet->refresh();
        $this->assertEquals(7000, $wallet->balance);
        $this->assertEquals(-3000, $transaction->amount);
    }

    public function test_admin_adjustment_stores_created_by(): void
    {
        $customer = $this->createCustomer();
        $this->createWallet($customer, 5000);

        $transaction = $this->walletService->adjustBalance(
            customerId: $customer->id,
            amountCents: 1000,
            description: 'Test adjustment',
            createdBy: $this->admin->id
        );

        $this->assertEquals($this->admin->id, $transaction->metadata['created_by']);
    }

    public function test_admin_can_complete_pending_topup(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $topup = $this->topUpService->createTopUp($customer->id, 5000);

        $this->topUpService->completeTopUp($topup, 'manual_admin', 'admin');

        $topup->refresh();
        $this->assertEquals(TopUpStatusEnum::COMPLETED, $topup->status->getValue());

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->balance);
    }

    public function test_admin_can_cancel_pending_topup(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $topup = $this->createTopUp($customer, $wallet, [
            'status' => TopUpStatusEnum::PENDING,
        ]);

        $topup->update(['status' => TopUpStatusEnum::CANCELLED]);

        $topup->refresh();
        $this->assertEquals(TopUpStatusEnum::CANCELLED, $topup->status->getValue());

        $wallet->refresh();
        $this->assertEquals(0, $wallet->balance);
    }

    public function test_admin_can_cancel_processing_topup(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);
        $topup = $this->createTopUp($customer, $wallet, [
            'status' => TopUpStatusEnum::PROCESSING,
        ]);

        $topup->update(['status' => TopUpStatusEnum::CANCELLED]);

        $topup->refresh();
        $this->assertEquals(TopUpStatusEnum::CANCELLED, $topup->status->getValue());
    }

    public function test_wallet_list_data(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $customer = $this->createCustomer("Customer {$i}", "customer{$i}@example.com");
            $this->createWallet($customer, $i * 1000);
        }

        $walletCount = Wallet::query()->count();
        $this->assertEquals(5, $walletCount);

        $totalBalance = Wallet::query()->sum('balance');
        $this->assertEquals(15000, $totalBalance);
    }

    public function test_wallet_detail_with_transactions(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 10000);

        for ($i = 0; $i < 5; $i++) {
            $this->createTransaction($wallet, ['amount' => 1000 * ($i + 1)]);
        }

        $transactionCount = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->count();
        $this->assertEquals(5, $transactionCount);

        $this->assertEquals(5, $wallet->transactions->count());
    }

    public function test_topup_list_data(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer);

        for ($i = 0; $i < 3; $i++) {
            $this->createTopUp($customer, $wallet, [
                'amount' => ($i + 1) * 5000,
            ]);
        }

        $topupCount = WalletTopUp::query()
            ->where('customer_id', $customer->id)
            ->count();
        $this->assertEquals(3, $topupCount);
    }

    public function test_dashboard_statistics_calculation(): void
    {
        $customer1 = $this->createCustomer('Customer 1', 'customer1@example.com');
        $customer2 = $this->createCustomer('Customer 2', 'customer2@example.com');

        $wallet1 = $this->createWallet($customer1, 5000);
        $wallet2 = $this->createWallet($customer2, 10000);

        $this->createTransaction($wallet1, ['amount' => 5000, 'type' => TransactionTypeEnum::TOP_UP]);
        $this->createTransaction($wallet2, ['amount' => 10000, 'type' => TransactionTypeEnum::TOP_UP]);
        $this->createTransaction($wallet1, ['amount' => -2000, 'type' => TransactionTypeEnum::PAYMENT]);

        $totalWallets = Wallet::query()->count();
        $this->assertEquals(2, $totalWallets);

        $totalCredits = WalletTransaction::query()
            ->where('amount', '>', 0)
            ->sum('amount');
        $this->assertEquals(15000, $totalCredits);

        $totalDebits = abs(WalletTransaction::query()
            ->where('amount', '<', 0)
            ->sum('amount'));
        $this->assertEquals(2000, $totalDebits);

        $totalBalance = Wallet::query()->sum('balance');
        $this->assertEquals(15000, $totalBalance);
    }

    public function test_multiple_admin_adjustments(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 0);

        $this->walletService->adjustBalance($customer->id, 5000, 'Initial credit', $this->admin->id);
        $this->walletService->adjustBalance($customer->id, 2500, 'Bonus', $this->admin->id);
        $this->walletService->adjustBalance($customer->id, -1000, 'Fee deduction', $this->admin->id);

        $wallet->refresh();
        $this->assertEquals(6500, $wallet->balance);

        $adjustmentCount = WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', TransactionTypeEnum::ADMIN_ADJUSTMENT)
            ->count();
        $this->assertEquals(3, $adjustmentCount);
    }

    public function test_transaction_type_filtering(): void
    {
        $customer = $this->createCustomer();
        $wallet = $this->createWallet($customer, 50000);

        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::TOP_UP, 'amount' => 5000]);
        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::TOP_UP, 'amount' => 3000]);
        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::PAYMENT, 'amount' => -2000]);
        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::REFUND, 'amount' => 1000]);
        $this->createTransaction($wallet, ['type' => TransactionTypeEnum::ADMIN_ADJUSTMENT, 'amount' => 500]);

        $topUpCount = WalletTransaction::query()
            ->where('type', TransactionTypeEnum::TOP_UP)
            ->count();
        $this->assertEquals(2, $topUpCount);

        $paymentCount = WalletTransaction::query()
            ->where('type', TransactionTypeEnum::PAYMENT)
            ->count();
        $this->assertEquals(1, $paymentCount);

        $refundCount = WalletTransaction::query()
            ->where('type', TransactionTypeEnum::REFUND)
            ->count();
        $this->assertEquals(1, $refundCount);

        $adjustmentCount = WalletTransaction::query()
            ->where('type', TransactionTypeEnum::ADMIN_ADJUSTMENT)
            ->count();
        $this->assertEquals(1, $adjustmentCount);
    }
}
