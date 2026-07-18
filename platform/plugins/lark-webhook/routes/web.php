<?php

use Botble\Base\Facades\AdminHelper;
use Botble\LarkWebhook\Http\Controllers\LarkWebhookController;
use Botble\LarkWebhook\Http\Controllers\LarkWebhookReceiverController;
use Botble\LarkWebhook\Http\Controllers\Settings\LarkWebhookSettingController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::prefix('lark/webhook')
    ->name('lark-webhook.')
    ->group(function (): void {
        Route::post('{token}', [LarkWebhookReceiverController::class, 'receive'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('receive');

        Route::get('{token}', [LarkWebhookReceiverController::class, 'ping'])
            ->name('ping');
    });

AdminHelper::registerRoutes(function (): void {
    Route::prefix('lark-webhook')
        ->name('lark-webhook.')
        ->group(function (): void {
            // DataTables loads its data with a POST to the list URL, so index must accept both.
            Route::match(['GET', 'POST'], '/', [LarkWebhookController::class, 'index'])->name('index');

            Route::delete('empty', [LarkWebhookController::class, 'empty'])->name('empty');

            Route::post('regenerate-token', [LarkWebhookController::class, 'regenerateToken'])
                ->name('regenerate-token');

            Route::group(['prefix' => 'settings', 'permission' => 'lark-webhook.settings'], function (): void {
                Route::get('/', [LarkWebhookSettingController::class, 'edit'])->name('settings');
                Route::put('/', [LarkWebhookSettingController::class, 'update'])->name('settings.update');
                Route::post('test-push', [LarkWebhookSettingController::class, 'testPush'])->name('test-push');
            });

            // Keep the wildcards last so they don't swallow `settings` / `empty`.
            Route::get('{event}', [LarkWebhookController::class, 'show'])
                ->where('event', '[0-9]+')
                ->name('show');

            Route::delete('{event}', [LarkWebhookController::class, 'destroy'])
                ->where('event', '[0-9]+')
                ->name('destroy');
        });
});
