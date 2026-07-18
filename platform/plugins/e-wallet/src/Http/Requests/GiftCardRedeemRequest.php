<?php

namespace Botble\EWallet\Http\Requests;

use Botble\Support\Http\Requests\Request;

class GiftCardRedeemRequest extends Request
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => trans('plugins/e-wallet::gift-card.errors.code_required'),
        ];
    }
}
