<?php

use Botble\EWallet\Http\Controllers\Fronts\GiftCardCheckoutController;
use Botble\EWallet\Http\Controllers\Fronts\GiftCardPurchaseController;
use Botble\EWallet\Http\Controllers\Fronts\GiftCardRedemptionController;
use Botble\EWallet\Http\Controllers\Fronts\TopUpController;
use Botble\EWallet\Http\Controllers\Fronts\UnifiedDiscountController;
use Botble\EWallet\Http\Controllers\Fronts\WalletController;
use Botble\EWallet\Http\Controllers\Fronts\WithdrawalController;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

Theme::registerRoutes(function (): void {
    Route::group([
        'middleware' => ['web', 'customer'],
        'prefix' => 'customer/e-wallet',
        'as' => 'customer.e-wallet.',
    ], function (): void {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('transactions', [WalletController::class, 'transactions'])->name('transactions');

        Route::group(['prefix' => 'topup', 'as' => 'topup.'], function (): void {
            Route::get('/', [TopUpController::class, 'create'])->name('create');
            Route::post('/', [TopUpController::class, 'store'])->name('store');
            Route::get('{code}/checkout', [TopUpController::class, 'checkout'])->name('checkout');
            Route::post('{code}/pay', [TopUpController::class, 'processPayment'])->name('pay');
            Route::get('{code}/callback', [TopUpController::class, 'callback'])->name('callback');
            Route::get('{code}/success', [TopUpController::class, 'success'])->name('success');
        });

        Route::group(['prefix' => 'withdrawals', 'as' => 'withdrawals.'], function (): void {
            Route::get('/', [WithdrawalController::class, 'index'])->name('index');
            Route::post('/', [WithdrawalController::class, 'store'])->name('store');
        });

        Route::group(['prefix' => 'gift-card', 'as' => 'gift-card.'], function (): void {
            Route::get('redeem', [GiftCardRedemptionController::class, 'redeemForm'])->name('redeem');
            Route::post('redeem', [GiftCardRedemptionController::class, 'redeem'])->name('redeem.submit');
            Route::get('success', [GiftCardRedemptionController::class, 'success'])->name('success');

            Route::get('purchase', [GiftCardPurchaseController::class, 'index'])->name('purchase');
            Route::post('purchase', [GiftCardPurchaseController::class, 'store'])->name('purchase.store');
            Route::get('purchase/{id}/success', [GiftCardPurchaseController::class, 'success'])->name('purchase.success');
            Route::get('my-cards', [GiftCardPurchaseController::class, 'myGiftCards'])->name('my-cards');
            Route::post('my-cards/{id}/resend', [GiftCardPurchaseController::class, 'resendEmail'])->name('my-cards.resend');
        });
    });

    Route::group([
        'middleware' => ['web'],
        'prefix' => 'gift-card',
        'as' => 'public.gift-card.',
    ], function (): void {
        Route::get('check', [GiftCardRedemptionController::class, 'checkForm'])->name('check');
        Route::post('check', [GiftCardRedemptionController::class, 'checkBalance'])->name('check.submit');

        Route::post('checkout/apply', [GiftCardCheckoutController::class, 'apply'])->name('checkout.apply');
        Route::post('checkout/remove', [GiftCardCheckoutController::class, 'remove'])->name('checkout.remove');

        Route::post('unified-discount/apply', [UnifiedDiscountController::class, 'apply'])->name('unified-discount.apply');
        Route::post('unified-discount/remove-coupon', [UnifiedDiscountController::class, 'removeCoupon'])->name('unified-discount.remove-coupon');
        Route::post('unified-discount/remove-gift-card', [UnifiedDiscountController::class, 'removeGiftCard'])->name('unified-discount.remove-gift-card');
    });
});
