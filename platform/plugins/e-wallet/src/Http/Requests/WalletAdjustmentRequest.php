<?php

namespace Botble\EWallet\Http\Requests;

use Botble\Support\Http\Requests\Request;

class WalletAdjustmentRequest extends Request
{
    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'exists:ec_wallets,id'],
            'adjustment_type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => trans('plugins/e-wallet::e-wallet.errors.wallet_required'),
            'wallet_id.exists' => trans('plugins/e-wallet::e-wallet.errors.wallet_not_found'),
            'adjustment_type.required' => trans('plugins/e-wallet::e-wallet.errors.type_required'),
            'adjustment_type.in' => trans('plugins/e-wallet::e-wallet.errors.type_invalid'),
            'amount.required' => trans('plugins/e-wallet::e-wallet.errors.amount_required'),
            'amount.numeric' => trans('plugins/e-wallet::e-wallet.errors.amount_invalid'),
            'amount.min' => trans('plugins/e-wallet::e-wallet.errors.amount_min'),
            'reason.required' => trans('plugins/e-wallet::e-wallet.errors.reason_required'),
            'reason.max' => trans('plugins/e-wallet::e-wallet.errors.reason_max'),
        ];
    }
}
