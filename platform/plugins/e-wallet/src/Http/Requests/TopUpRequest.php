<?php

namespace Botble\EWallet\Http\Requests;

use Botble\EWallet\Helpers\WalletHelper;
use Botble\Support\Http\Requests\Request;

class TopUpRequest extends Request
{
    public function rules(): array
    {
        $helper = app(WalletHelper::class);
        $minAmount = $helper->getMinTopUp() / 100;
        $maxAmount = $helper->getMaxTopUp() / 100;

        return [
            'amount' => ['required', 'numeric', "min:{$minAmount}", "max:{$maxAmount}"],
        ];
    }

    public function messages(): array
    {
        $helper = app(WalletHelper::class);

        return [
            'amount.required' => trans('plugins/e-wallet::e-wallet.errors.amount_required'),
            'amount.numeric' => trans('plugins/e-wallet::e-wallet.errors.amount_invalid'),
            'amount.min' => trans('plugins/e-wallet::e-wallet.errors.amount_below_minimum', [
                'min' => format_price($helper->getMinTopUp() / 100),
            ]),
            'amount.max' => trans('plugins/e-wallet::e-wallet.errors.amount_above_maximum', [
                'max' => format_price($helper->getMaxTopUp() / 100),
            ]),
        ];
    }
}
