<?php

namespace Botble\EWallet\Http\Requests;

use Botble\Support\Http\Requests\Request;

class GiftCardPurchaseRequest extends Request
{
    public function rules(): array
    {
        $minValue = get_gift_card_min_value() / 100;
        $maxValue = get_gift_card_max_value() / 100;

        return [
            'value' => ['required', 'numeric', "min:{$minValue}", "max:{$maxValue}"],
            'recipient_name' => ['nullable', 'string', 'max:191'],
            'recipient_email' => ['nullable', 'email', 'max:191'],
            'gift_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'value' => trans('plugins/e-wallet::gift-card.purchase.value'),
            'recipient_name' => trans('plugins/e-wallet::gift-card.purchase.recipient_name'),
            'recipient_email' => trans('plugins/e-wallet::gift-card.purchase.recipient_email'),
            'gift_message' => trans('plugins/e-wallet::gift-card.purchase.gift_message'),
        ];
    }
}
