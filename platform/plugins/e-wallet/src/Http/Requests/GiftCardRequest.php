<?php

namespace Botble\EWallet\Http\Requests;

use Botble\Support\Http\Requests\Request;

class GiftCardRequest extends Request
{
    public function rules(): array
    {
        $rules = [
            'customer_id' => ['nullable', 'exists:ec_customers,id'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->isMethod('POST')) {
            $rules['value'] = ['required', 'numeric', 'min:0.01'];
            $rules['custom_code'] = ['nullable', 'string', 'max:50', 'unique:ec_gift_cards,code'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'value.required' => trans('plugins/e-wallet::gift-card.errors.value_required'),
            'value.min' => trans('plugins/e-wallet::gift-card.errors.value_min', ['min' => 0.01]),
            'custom_code.unique' => trans('plugins/e-wallet::gift-card.errors.code_exists'),
            'expires_at.after' => trans('plugins/e-wallet::gift-card.errors.expires_future'),
        ];
    }
}
