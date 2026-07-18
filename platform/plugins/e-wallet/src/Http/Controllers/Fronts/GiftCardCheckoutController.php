<?php

namespace Botble\EWallet\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\EWallet\Exceptions\GiftCardAlreadyRedeemedException;
use Botble\EWallet\Exceptions\GiftCardExpiredException;
use Botble\EWallet\Exceptions\GiftCardInsufficientBalanceException;
use Botble\EWallet\Exceptions\InvalidGiftCardCodeException;
use Botble\EWallet\Services\GiftCardCheckoutService;
use Illuminate\Http\Request;

class GiftCardCheckoutController extends BaseController
{
    public function __construct(protected GiftCardCheckoutService $service)
    {
    }

    public function apply(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'gift_card_code' => ['required', 'string', 'max:50'],
        ]);

        if (! gift_cards_enabled()) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::gift-card.feature_disabled'));
        }

        try {
            $orderTotal = $this->service->getCurrentOrderTotal();

            $applied = $this->service->apply(
                $request->input('gift_card_code'),
                $orderTotal
            );

            return $response
                ->setData($applied)
                ->setMessage(trans('plugins/e-wallet::gift-card.checkout.applied_success', [
                    'amount' => $applied['formatted_discount'],
                ]));
        } catch (InvalidGiftCardCodeException $e) {
            return $response
                ->setError()
                ->setMessage($e->getMessage() ?: trans('plugins/e-wallet::gift-card.errors.invalid_code'));
        } catch (GiftCardExpiredException $e) {
            return $response
                ->setError()
                ->setMessage($e->getMessage() ?: trans('plugins/e-wallet::gift-card.errors.expired'));
        } catch (GiftCardAlreadyRedeemedException $e) {
            return $response
                ->setError()
                ->setMessage($e->getMessage() ?: trans('plugins/e-wallet::gift-card.errors.already_redeemed'));
        } catch (GiftCardInsufficientBalanceException $e) {
            return $response
                ->setError()
                ->setMessage($e->getMessage() ?: trans('plugins/e-wallet::gift-card.errors.insufficient_balance'));
        }
    }

    public function remove(BaseHttpResponse $response)
    {
        $this->service->remove();

        return $response
            ->setMessage(trans('plugins/e-wallet::gift-card.checkout.removed_success'));
    }
}
