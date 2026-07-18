<?php

namespace Botble\EWallet\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Services\HandleApplyCouponService;
use Botble\Ecommerce\Services\HandleRemoveCouponService;
use Botble\EWallet\Exceptions\GiftCardAlreadyRedeemedException;
use Botble\EWallet\Exceptions\GiftCardExpiredException;
use Botble\EWallet\Exceptions\GiftCardInsufficientBalanceException;
use Botble\EWallet\Exceptions\InvalidGiftCardCodeException;
use Botble\EWallet\Services\GiftCardCheckoutService;
use Illuminate\Http\Request;

class UnifiedDiscountController extends BaseController
{
    public function __construct(
        protected GiftCardCheckoutService $giftCardService,
        protected HandleApplyCouponService $applyCouponService,
        protected HandleRemoveCouponService $removeCouponService,
    ) {
    }

    public function apply(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'discount_code' => ['required', 'string', 'max:255'],
        ]);

        $code = trim($request->input('discount_code'));
        $hasCouponApplied = session()->has('applied_coupon_code');
        $hasGiftCardApplied = (bool) $this->giftCardService->getApplied();

        if (! $hasCouponApplied) {
            $couponResult = $this->tryCoupon($code);

            if ($couponResult && ! $couponResult['error']) {
                return $response
                    ->setData(['type' => 'coupon'])
                    ->setMessage(trans('plugins/e-wallet::gift-card.checkout.applied_as_coupon', ['code' => $code]));
            }
        }

        if (! $hasGiftCardApplied && gift_cards_enabled()) {
            $giftCardResult = $this->tryGiftCard($code);

            if ($giftCardResult) {
                return $response
                    ->setData(['type' => 'gift_card'])
                    ->setMessage(trans('plugins/e-wallet::gift-card.checkout.applied_as_gift_card', [
                        'amount' => $giftCardResult['formatted_discount'],
                    ]));
            }
        }

        $errorMessage = $this->buildErrorMessage($hasCouponApplied, $hasGiftCardApplied, $couponResult ?? null);

        return $response
            ->setError()
            ->setMessage($errorMessage);
    }

    public function removeCoupon(BaseHttpResponse $response)
    {
        if (is_plugin_active('marketplace')) {
            $products = Cart::instance('cart')->products();
            $result = apply_filters(HANDLE_POST_REMOVE_COUPON_CODE_ECOMMERCE, $products, request());
        } else {
            $result = $this->removeCouponService->execute();
        }

        if (! empty($result['error'])) {
            return $response
                ->setError()
                ->setMessage($result['message'] ?? '');
        }

        return $response
            ->setMessage(__('Coupon removed successfully!'));
    }

    public function removeGiftCard(BaseHttpResponse $response)
    {
        $this->giftCardService->remove();

        return $response
            ->setMessage(trans('plugins/e-wallet::gift-card.checkout.removed_success'));
    }

    protected function tryCoupon(string $code): ?array
    {
        try {
            if (is_plugin_active('marketplace')) {
                $request = request();
                $request->merge(['coupon_code' => $code]);
                $result = apply_filters(HANDLE_POST_APPLY_COUPON_CODE_ECOMMERCE, [
                    'error' => false,
                    'message' => '',
                ], $request);

                return $result;
            }

            return $this->applyCouponService->execute($code);
        } catch (\Exception) {
            return ['error' => true, 'message' => ''];
        }
    }

    protected function tryGiftCard(string $code): ?array
    {
        try {
            $orderTotal = $this->giftCardService->getCurrentOrderTotal();

            return $this->giftCardService->apply($code, $orderTotal);
        } catch (InvalidGiftCardCodeException|GiftCardExpiredException|GiftCardAlreadyRedeemedException|GiftCardInsufficientBalanceException) {
            return null;
        }
    }

    protected function buildErrorMessage(bool $hasCouponApplied, bool $hasGiftCardApplied, ?array $couponResult): string
    {
        if ($hasCouponApplied && $hasGiftCardApplied) {
            return trans('plugins/e-wallet::gift-card.checkout.invalid_code');
        }

        if (! empty($couponResult['message']) && ! $hasCouponApplied) {
            return $couponResult['message'];
        }

        return trans('plugins/e-wallet::gift-card.checkout.invalid_code');
    }
}
