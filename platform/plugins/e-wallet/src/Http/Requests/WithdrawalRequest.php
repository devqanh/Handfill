<?php

namespace Botble\EWallet\Http\Requests;

use Botble\EWallet\Enums\PayoutPaymentMethodsEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class WithdrawalRequest extends Request
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_channel' => ['required', 'string', Rule::in(PayoutPaymentMethodsEnum::values())],
            'bank_info' => ['nullable', 'string', 'max:500'],
            'paypal_id' => ['nullable', 'string', 'max:120'],
            'payment_details' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => trans('plugins/e-wallet::withdrawal.amount'),
            'payment_channel' => trans('plugins/e-wallet::withdrawal.payment_method'),
            'bank_info' => trans('plugins/e-wallet::withdrawal.bank_information'),
            'paypal_id' => trans('plugins/e-wallet::withdrawal.paypal_id'),
            'payment_details' => trans('plugins/e-wallet::withdrawal.payment_details'),
        ];
    }
}
