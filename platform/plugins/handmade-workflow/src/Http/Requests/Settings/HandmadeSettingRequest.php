<?php

namespace Botble\HandmadeWorkflow\Http\Requests\Settings;

use Botble\Support\Http\Requests\Request;

class HandmadeSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'handmade_default_fulfill_fee' => ['nullable', 'numeric', 'min:0'],
            'handmade_default_packing_fee_per_unit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
