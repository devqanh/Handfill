<?php

namespace Botble\HandmadeWorkflow\Http\Requests;

use Botble\Support\Http\Requests\Request;

class SaveQuoteRequest extends Request
{
    public function rules(): array
    {
        return [
            // Per-line unit prices. product_cost is derived from these, never typed directly.
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'shipping_cost' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'fulfill_fee' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'packing_fee' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'expected_delivery_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
