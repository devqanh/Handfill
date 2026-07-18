<?php

namespace Botble\EWallet\Http\Requests;

use Botble\Support\Http\Requests\Request;

class GiftCardBulkRequest extends Request
{
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => trans('plugins/e-wallet::gift-card.errors.quantity_required'),
            'quantity.max' => trans('plugins/e-wallet::gift-card.errors.quantity_max', ['max' => 100]),
            'value.required' => trans('plugins/e-wallet::gift-card.errors.value_required'),
            'value.min' => trans('plugins/e-wallet::gift-card.errors.value_min', ['min' => 0.01]),
        ];
    }
}
