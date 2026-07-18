<?php

namespace Botble\EWallet\Providers;

use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\GiftCardCodeGenerator;
use Botble\EWallet\Services\GiftCardService;
use Botble\EWallet\Services\WalletService;
use Illuminate\Support\ServiceProvider;

class EWalletServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->setNamespace('plugins/e-wallet')->loadHelpers();

        $this->app->singleton(WalletHelper::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(GiftCardCodeGenerator::class);
        $this->app->singleton(GiftCardService::class);
    }

    public function boot(): void
    {
        $this
            ->loadAndPublishConfigurations(['permissions', 'email'])
            ->loadAndPublishTranslations()
            ->loadRoutes(['base', 'customer'])
            ->loadAndPublishViews()
            ->loadMigrations()
            ->publishAssets();

        $this->app->register(EventServiceProvider::class);
        $this->app->register(HookServiceProvider::class);

        $this->app->booted(function (): void {
            EmailHandler::addTemplateSettings(
                \E_WALLET_MODULE_SCREEN_NAME,
                config('plugins.e-wallet.email', [])
            );
        });

        $this->registerDashboardMenus();
        $this->registerCustomerRelationships();
        $this->registerCleanupHandlers();
    }

    protected function registerDashboardMenus(): void
    {
        DashboardMenu::default()->beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet',
                    'priority' => 850,
                    'parent_id' => null,
                    'name' => 'plugins/e-wallet::e-wallet.name',
                    'icon' => 'ti ti-wallet',
                    'permissions' => ['e-wallet.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-dashboard',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::e-wallet.menu.dashboard',
                    'icon' => 'ti ti-chart-pie',
                    'url' => route('e-wallet.index'),
                    'permissions' => ['e-wallet.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-wallets',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::e-wallet.menu.wallets',
                    'icon' => 'ti ti-users',
                    'url' => route('e-wallet.wallets.index'),
                    'permissions' => ['e-wallet.wallets.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-transactions',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::e-wallet.menu.transactions',
                    'icon' => 'ti ti-arrows-exchange',
                    'url' => route('e-wallet.transactions.index'),
                    'permissions' => ['e-wallet.transactions.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-topups',
                    'priority' => 4,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::e-wallet.menu.topups',
                    'icon' => 'ti ti-cash',
                    'url' => route('e-wallet.topups.index'),
                    'permissions' => ['e-wallet.topups.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-withdrawals',
                    'priority' => 5,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::e-wallet.menu.withdrawals',
                    'icon' => 'ti ti-arrow-down-right',
                    'url' => route('e-wallet.withdrawals.index'),
                    'permissions' => ['e-wallet.withdrawals.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-gift-cards',
                    'priority' => 6,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::gift-card.menu.gift_cards',
                    'icon' => 'ti ti-gift',
                    'url' => fn () => route('e-wallet.gift-cards.index'),
                    'permissions' => ['e-wallet.gift-cards.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-settings',
                    'priority' => 99,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::e-wallet.menu.settings',
                    'icon' => 'ti ti-settings',
                    'url' => route('e-wallet.settings.index'),
                    'permissions' => ['e-wallet.settings'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-e-wallet-license',
                    'priority' => 100,
                    'parent_id' => 'cms-plugins-e-wallet',
                    'name' => 'plugins/e-wallet::e-wallet.license.title',
                    'icon' => 'ti ti-key',
                    'url' => fn () => route('e-wallet.license.index'),
                    'permissions' => ['e-wallet.license'],
                ]);
        });

        DashboardMenu::for('customer')->beforeRetrieving(function (): void {
            $helper = app(WalletHelper::class);

            if (! $helper->isEnabled()) {
                return;
            }

            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-customer-e-wallet',
                    'priority' => 90,
                    'name' => trans('plugins/e-wallet::e-wallet.customer.my_wallet'),
                    'url' => fn () => route('customer.e-wallet.index'),
                    'icon' => 'ti ti-wallet',
                ]);
        });
    }

    protected function registerCustomerRelationships(): void
    {
        Customer::resolveRelationUsing('wallet', function ($model) {
            return $model->hasOne(Wallet::class, 'customer_id');
        });

        Customer::resolveRelationUsing('walletTransactions', function ($model) {
            return $model->hasMany(WalletTransaction::class, 'customer_id');
        });
    }

    protected function registerCleanupHandlers(): void
    {
        Customer::deleted(function ($customer): void {
            Wallet::query()->where('customer_id', $customer->id)->delete();
            WalletTransaction::query()->where('customer_id', $customer->id)->delete();
            GiftCard::query()->where('customer_id', $customer->id)->update(['customer_id' => null]);
            GiftCard::query()->where('redeemed_by_customer_id', $customer->id)->update(['redeemed_by_customer_id' => null]);
        });

        Order::deleted(function ($order): void {
            WalletTransaction::query()->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->delete();
        });
    }
}
