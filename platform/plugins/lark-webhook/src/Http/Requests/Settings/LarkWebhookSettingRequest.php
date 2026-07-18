<?php

namespace Botble\LarkWebhook\Http\Requests\Settings;

use Botble\Base\Rules\OnOffRule;
use Botble\Support\Http\Requests\Request;

class LarkWebhookSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'lark_webhook_enabled' => [new OnOffRule()],
            'lark_webhook_verification_token' => ['nullable', 'string', 'max:120'],
            'lark_webhook_encrypt_key' => ['nullable', 'string', 'max:120'],

            'lark_webhook_push_enabled' => [new OnOffRule()],
            'lark_webhook_base_domain' => ['required', 'string', 'in:https://open.larksuite.com,https://open.feishu.cn'],
            'lark_webhook_app_id' => ['nullable', 'string', 'max:120'],
            'lark_webhook_app_secret' => ['nullable', 'string', 'max:191'],
            'lark_webhook_base_app_token' => ['nullable', 'string', 'max:191'],
            'lark_webhook_base_table_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}
