<?php

use Botble\Base\Facades\AdminHelper;
use Botble\EWallet\Http\Controllers\EWalletController;
use Botble\EWallet\Http\Controllers\GiftCardController;
use Botble\EWallet\Http\Controllers\LicenseController;
use Botble\EWallet\Http\Controllers\Settings\WalletSettingController;
use Botble\EWallet\Http\Controllers\TopUpManagementController;
use Botble\EWallet\Http\Controllers\WalletAdjustmentController;
use Botble\EWallet\Http\Controllers\WalletController;
use Botble\EWallet\Http\Controllers\WalletTransactionController;
use Botble\EWallet\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group(['prefix' => 'e-wallet', 'as' => 'e-wallet.'], function (): void {
        Route::get('dashboard', [EWalletController::class, 'index'])->name('index');

        Route::group([
            'prefix' => 'wallets',
            'as' => 'wallets.',
            'permission' => 'e-wallet.wallets.index',
        ], function (): void {
            Route::match(['GET', 'POST'], '/', [WalletController::class, 'index'])->name('index');
            Route::get('create', [WalletController::class, 'create'])
                ->name('create')
                ->permission('e-wallet.wallets.create');
            Route::post('create', [WalletController::class, 'store'])
                ->name('store')
                ->permission('e-wallet.wallets.create');
            Route::post('adjust', [WalletAdjustmentController::class, 'store'])
                ->name('adjust.store')
                ->permission('e-wallet.wallets.adjust');
            Route::match(['GET', 'POST'], '{id}', [WalletController::class, 'show'])->name('show')->whereNumber('id');
        });

        Route::group([
            'prefix' => 'transactions',
            'as' => 'transactions.',
            'permission' => 'e-wallet.transactions.index',
        ], function (): void {
            Route::match(['GET', 'POST'], '/', [WalletTransactionController::class, 'index'])->name('index');
            Route::get('{id}', [WalletTransactionController::class, 'show'])->name('show')->whereNumber('id');
        });

        Route::group([
            'prefix' => 'topups',
            'as' => 'topups.',
            'permission' => 'e-wallet.topups.index',
        ], function (): void {
            Route::match(['GET', 'POST'], '/', [TopUpManagementController::class, 'index'])->name('index');
            Route::get('{id}', [TopUpManagementController::class, 'show'])->name('show')->whereNumber('id');
            Route::post('{id}/complete', [TopUpManagementController::class, 'complete'])
                ->name('complete')
                ->whereNumber('id')
                ->permission('e-wallet.topups.complete');
            Route::post('{id}/cancel', [TopUpManagementController::class, 'cancel'])
                ->name('cancel')
                ->whereNumber('id')
                ->permission('e-wallet.topups.cancel');
        });

        Route::group([
            'prefix' => 'withdrawals',
            'as' => 'withdrawals.',
            'permission' => 'e-wallet.withdrawals.index',
        ], function (): void {
            Route::match(['GET', 'POST'], '/', [WithdrawalController::class, 'index'])->name('index');
            Route::get('{id}', [WithdrawalController::class, 'show'])->name('show')->whereNumber('id');
            Route::post('{id}/approve', [WithdrawalController::class, 'approve'])
                ->name('approve')
                ->whereNumber('id')
                ->permission('e-wallet.withdrawals.approve');
            Route::post('{id}/reject', [WithdrawalController::class, 'reject'])
                ->name('reject')
                ->whereNumber('id')
                ->permission('e-wallet.withdrawals.reject');
        });

        Route::group([
            'prefix' => 'gift-cards',
            'as' => 'gift-cards.',
            'permission' => 'e-wallet.gift-cards.index',
        ], function (): void {
            Route::match(['GET', 'POST'], '/', [GiftCardController::class, 'index'])->name('index');
            Route::get('bulk/create', [GiftCardController::class, 'bulkCreate'])
                ->name('bulk.create')
                ->permission('e-wallet.gift-cards.create');
            Route::post('bulk/store', [GiftCardController::class, 'bulkStore'])
                ->name('bulk.store')
                ->permission('e-wallet.gift-cards.create');
            Route::get('export', [GiftCardController::class, 'export'])
                ->name('export')
                ->permission('e-wallet.gift-cards.export');
            Route::get('create', [GiftCardController::class, 'create'])
                ->name('create')
                ->permission('e-wallet.gift-cards.create');
            Route::post('create', [GiftCardController::class, 'store'])
                ->name('store')
                ->permission('e-wallet.gift-cards.create');
            Route::get('{id}/edit', [GiftCardController::class, 'edit'])
                ->name('edit')
                ->permission('e-wallet.gift-cards.edit');
            Route::put('{id}', [GiftCardController::class, 'update'])
                ->name('update')
                ->permission('e-wallet.gift-cards.edit');
            Route::delete('{id}', [GiftCardController::class, 'destroy'])
                ->name('destroy')
                ->permission('e-wallet.gift-cards.destroy');
            Route::post('{id}/cancel', [GiftCardController::class, 'cancel'])
                ->name('cancel')
                ->permission('e-wallet.gift-cards.cancel');
            Route::get('{id}', [GiftCardController::class, 'show'])->name('show');
        });

        Route::group([
            'prefix' => 'settings',
            'as' => 'settings.',
            'permission' => 'e-wallet.settings',
        ], function (): void {
            Route::get('/', [WalletSettingController::class, 'edit'])->name('index');
            Route::put('/', [WalletSettingController::class, 'update'])
                ->name('update')
                ->middleware('preventDemo');
            Route::post('webhook/test', [WalletSettingController::class, 'testWebhook'])
                ->name('webhook.test')
                ->middleware('preventDemo');
        });

        Route::group([
            'prefix' => 'license',
            'as' => 'license.',
            'permission' => 'e-wallet.license',
        ], function (): void {
            Route::get('/', [LicenseController::class, 'index'])->name('index');
            Route::post('activate', [LicenseController::class, 'activate'])
                ->name('activate')
                ->middleware('preventDemo');
            Route::post('deactivate', [LicenseController::class, 'deactivate'])
                ->name('deactivate')
                ->middleware('preventDemo');
        });
    });
});
