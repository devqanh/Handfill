<?php

namespace Botble\EWallet\Http\Controllers\Fronts;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Http\Requests\GiftCardPurchaseRequest;
use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Services\GiftCardService;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class GiftCardPurchaseController extends BaseFrontController
{
    public function __construct(protected GiftCardService $giftCardService)
    {
        parent::__construct();
    }

    public function index()
    {
        if (! gift_card_purchase_enabled()) {
            abort(404);
        }

        $customer = auth('customer')->user();
        $wallet = Wallet::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            ['balance' => 0]
        );

        $predefinedValues = get_gift_card_predefined_values();
        $minValue = get_gift_card_min_value();
        $maxValue = get_gift_card_max_value();

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(__('Wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::gift-card.purchase.title'));

        return Theme::scope(
            'e-wallet::gift-card.purchase',
            compact('predefinedValues', 'minValue', 'maxValue', 'wallet'),
            'plugins/e-wallet::themes.gift-card.purchase'
        )->render();
    }

    public function store(GiftCardPurchaseRequest $request, BaseHttpResponse $response)
    {
        $customer = auth('customer')->user();
        $valueCents = (int) ($request->input('value') * 100);

        $wallet = Wallet::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            ['balance' => 0]
        );

        if ($wallet->balance < $valueCents) {
            $shortfall = $valueCents - $wallet->balance;

            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::gift-card.purchase.insufficient_balance', [
                    'balance' => $wallet->formatted_balance,
                    'required' => format_price($valueCents / 100),
                    'shortfall' => format_price($shortfall / 100),
                ]));
        }

        try {
            $giftCard = $this->giftCardService->purchaseWithWallet(
                valueCents: $valueCents,
                purchasedByCustomerId: $customer->id,
                recipientName: $request->input('recipient_name'),
                recipientEmail: $request->input('recipient_email'),
                giftMessage: $request->input('gift_message'),
                expiresAt: get_gift_card_default_expiry_days()
                    ? now()->addDays(get_gift_card_default_expiry_days())
                    : null
            );

            return $response
                ->setNextUrl(route('customer.e-wallet.gift-card.purchase.success', $giftCard->id))
                ->setMessage(trans('plugins/e-wallet::gift-card.purchase.success_message'));
        } catch (InsufficientBalanceException $e) {
            return $response
                ->setError()
                ->setMessage($e->getMessage());
        }
    }

    public function success(int|string $id)
    {
        $customer = auth('customer')->user();
        $giftCard = GiftCard::query()
            ->where('id', $id)
            ->where('purchased_by_customer_id', $customer->id)
            ->where('status', GiftCardStatusEnum::ACTIVE)
            ->firstOrFail();

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(__('Wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::gift-card.purchase.success_title'));

        return Theme::scope(
            'e-wallet::gift-card.purchase-success',
            compact('giftCard'),
            'plugins/e-wallet::themes.gift-card.purchase-success'
        )->render();
    }

    public function myGiftCards(Request $request)
    {
        if (! gift_cards_enabled()) {
            abort(404);
        }

        $customer = auth('customer')->user();
        $showRedeemed = $request->boolean('show_redeemed', true);

        $query = GiftCard::query()
            ->where('purchased_by_customer_id', $customer->id);

        if ($showRedeemed) {
            $query->whereIn('status', [GiftCardStatusEnum::ACTIVE, GiftCardStatusEnum::REDEEMED])
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [GiftCardStatusEnum::ACTIVE]);
        } else {
            $query->where('status', GiftCardStatusEnum::ACTIVE);
        }

        $purchasedCards = $query->orderByDesc('created_at')->paginate(10);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(__('Wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::gift-card.my_cards.title'));

        return Theme::scope(
            'e-wallet::gift-card.my-cards',
            compact('purchasedCards', 'showRedeemed'),
            'plugins/e-wallet::themes.gift-card.my-cards'
        )->render();
    }

    public function resendEmail(int|string $id, BaseHttpResponse $response)
    {
        $customer = auth('customer')->user();

        $giftCard = GiftCard::query()
            ->where('id', $id)
            ->where('purchased_by_customer_id', $customer->id)
            ->where('status', GiftCardStatusEnum::ACTIVE)
            ->whereNotNull('recipient_email')
            ->firstOrFail();

        $this->giftCardService->resendGiftCardEmail($giftCard);

        return $response
            ->setMessage(trans('plugins/e-wallet::gift-card.email.resent_success', [
                'email' => $giftCard->recipient_email,
            ]));
    }
}
