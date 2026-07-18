<?php

namespace Botble\HandmadeWorkflow\Http\Requests;

use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class UpdateProductionStatusRequest extends Request
{
    public function rules(): array
    {
        return [
            'production_status' => ['required', 'string', Rule::in(ProductionStatusEnum::values())],
            'note' => ['nullable', 'string', 'max:400'],
        ];
    }
}
