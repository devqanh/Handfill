<?php

use Botble\Base\Facades\AdminHelper;
use Botble\HandmadeWorkflow\Http\Controllers\Fronts\CustomerQuoteController;
use Botble\HandmadeWorkflow\Http\Controllers\Fronts\CustomOrderController;
use Botble\HandmadeWorkflow\Http\Controllers\ProductionStatusController;
use Botble\HandmadeWorkflow\Http\Controllers\QuoteController;
use Botble\HandmadeWorkflow\Http\Controllers\Settings\HandmadeSettingController;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

// Front-end routes MUST go through Theme::registerRoutes — that is what applies the
// 'web' middleware group (sessions, cookies, CSRF). A bare Route:: group here has no
// session, so the customer guard would always see a guest and bounce to /login.
Theme::registerRoutes(function (): void {
    Route::middleware('customer')->group(function (): void {
        // Request a made-to-order (custom) item.
        Route::prefix('customer/custom-orders')
            ->name('customer.custom-orders.')
            ->group(function (): void {
                Route::get('create', [CustomOrderController::class, 'create'])->name('create');
                Route::post('/', [CustomOrderController::class, 'store'])->name('store');
            });

        // The two approval steps that trigger the wallet milestones.
        Route::prefix('customer/handmade-orders')
            ->name('customer.handmade-orders.')
            ->group(function (): void {
                Route::post('{order}/accept-quote', [CustomerQuoteController::class, 'acceptQuote'])
                    ->where('order', '[0-9]+')
                    ->name('accept-quote');

                Route::post('{order}/confirm-product', [CustomerQuoteController::class, 'confirmProduct'])
                    ->where('order', '[0-9]+')
                    ->name('confirm-product');
            });
    });
});

AdminHelper::registerRoutes(function (): void {
    Route::prefix('handmade-workflow')
        ->name('handmade-workflow.')
        ->group(function (): void {
            Route::post('orders/{order}/production-status', [ProductionStatusController::class, 'update'])
                ->where('order', '[0-9]+')
                ->middleware('permission:handmade-workflow.update-status')
                ->name('update-status');

            Route::post('orders/{order}/quote', [QuoteController::class, 'store'])
                ->where('order', '[0-9]+')
                ->middleware('permission:handmade-workflow.quote')
                ->name('quote');

            Route::group(['prefix' => 'settings', 'permission' => 'handmade-workflow.index'], function (): void {
                Route::get('/', [HandmadeSettingController::class, 'edit'])->name('settings');
                Route::put('/', [HandmadeSettingController::class, 'update'])->name('settings.update');
            });
        });
});
