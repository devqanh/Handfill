<?php

namespace Botble\EWallet\Http\Controllers\Fronts;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\EWallet\Exceptions\GiftCardAlreadyRedeemedException;
use Botble\EWallet\Exceptions\GiftCardExpiredException;
use Botble\EWallet\Exceptions\InvalidGiftCardCodeException;
use Botble\EWallet\Http\Requests\GiftCardCheckRequest;
use Botble\EWallet\Http\Requests\GiftCardRedeemRequest;
use Botble\EWallet\Services\GiftCardService;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class GiftCardRedemptionController extends BaseFrontController
{
    public function __construct(protected GiftCardService $giftCardService)
    {
        parent::__construct();
    }

    public function checkForm()
    {
        if (! gift_cards_enabled()) {
            abort(404);
        }

        if (! gift_card_public_balance_check_enabled()) {
            return redirect()->route('customer.login');
        }

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(trans('plugins/e-wallet::gift-card.check_balance'));

        return Theme::scope(
            'e-wallet::gift-card.check',
            [],
            'plugins/e-wallet::themes.gift-card.check'
        )->render();
    }

    public function checkBalance(GiftCardCheckRequest $request, BaseHttpResponse $response)
    {
        $key = 'gift-card-check:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::gift-card.rate_limit_exceeded', ['seconds' => $seconds]));
        }

        RateLimiter::hit($key, 60);

        $code = $request->input('code');
        $result = $this->giftCardService->checkBalance($code);

        if (! $result['valid']) {
            return $response
                ->setError()
                ->setMessage($result['message']);
        }

        RateLimiter::clear($key);

        return $response
            ->setData($result)
            ->setMessage(trans('plugins/e-wallet::gift-card.balance_check_success'));
    }

    public function redeemForm(Request $request)
    {
        if (! gift_cards_enabled()) {
            abort(404);
        }

        $code = $request->query('code');
        $balance = null;

        if ($code) {
            $result = $this->giftCardService->checkBalance($code);

            if ($result['valid']) {
                $balance = $result;
            }
        }

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(trans('plugins/e-wallet::e-wallet.customer.my_wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::gift-card.redeem'));

        return Theme::scope(
            'e-wallet::gift-card.redeem',
            compact('code', 'balance'),
            'plugins/e-wallet::themes.gift-card.redeem'
        )->render();
    }

    public function redeem(GiftCardRedeemRequest $request, BaseHttpResponse $response)
    {
        $customer = auth('customer')->user();
        $code = $request->input('code');

        try {
            $transaction = $this->giftCardService->redeem($code, $customer->id);

            return $response
                ->setNextUrl(route('customer.e-wallet.gift-card.success', ['transaction' => $transaction->id]))
                ->setMessage(trans('plugins/e-wallet::gift-card.redeemed_success'));
        } catch (InvalidGiftCardCodeException) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::gift-card.errors.invalid_code'));
        } catch (GiftCardExpiredException) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::gift-card.errors.expired'));
        } catch (GiftCardAlreadyRedeemedException) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::gift-card.errors.already_redeemed'));
        } catch (\Exception) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::gift-card.errors.redemption_failed'));
        }
    }

    public function success(Request $request)
    {
        $transactionId = $request->query('transaction');
        $transaction = null;

        if ($transactionId) {
            $customer = auth('customer')->user();
            $transaction = $customer->walletTransactions()
                ->where('id', $transactionId)
                ->first();
        }

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(trans('plugins/e-wallet::e-wallet.customer.my_wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::gift-card.success_title'));

        return Theme::scope(
            'e-wallet::gift-card.success',
            compact('transaction'),
            'plugins/e-wallet::themes.gift-card.success'
        )->render();
    }
}
