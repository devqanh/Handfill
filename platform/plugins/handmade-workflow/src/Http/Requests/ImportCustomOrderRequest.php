<?php

namespace Botble\HandmadeWorkflow\Http\Requests;

use Botble\Support\Http\Requests\Request;

class ImportCustomOrderRequest extends Request
{
    public function rules(): array
    {
        return [
            // Matched on extension, not MIME: browsers report .xlsx as anything from
            // application/octet-stream to a zip type, and .csv usually as text/plain.
            // The legacy binary .xls is left out — the reader cannot open it.
            'file' => ['required', 'file', 'max:10240', 'extensions:xlsx,ods,csv,txt'],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => trans('plugins/handmade-workflow::handmade-workflow.import.file'),
        ];
    }
}
