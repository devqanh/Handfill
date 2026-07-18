<?php

namespace Botble\HandmadeWorkflow\Http\Requests;

use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateCustomOrderRequest extends Request
{
    public const MAX_ITEMS = 10;

    public const MAX_IMAGES_PER_ITEM = 6;

    public function rules(): array
    {
        return [
            'customer_group' => ['required', 'string', Rule::in(CustomerGroupEnum::values())],
            // Scoped to the logged-in customer so nobody can ship to someone else's address.
            'address_id' => [
                'required',
                Rule::exists('ec_customer_addresses', 'id')
                    ->where('customer_id', Auth::guard('customer')->id()),
            ],
            'expected_date' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1', 'max:' . self::MAX_ITEMS],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.images' => ['required', 'array', 'min:1', 'max:' . self::MAX_IMAGES_PER_ITEM],
            'items.*.images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_group' => trans('plugins/handmade-workflow::handmade-workflow.customer_group'),
            'expected_date' => trans('plugins/handmade-workflow::handmade-workflow.expected_date'),
            'items' => trans('plugins/handmade-workflow::handmade-workflow.items'),
            'items.*.name' => trans('plugins/handmade-workflow::handmade-workflow.item_name'),
            'items.*.qty' => trans('plugins/handmade-workflow::handmade-workflow.item_qty'),
            'items.*.images' => trans('plugins/handmade-workflow::handmade-workflow.item_images'),
        ];
    }
}
