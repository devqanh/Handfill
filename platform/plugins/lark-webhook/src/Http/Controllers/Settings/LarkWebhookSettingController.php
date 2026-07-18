<?php

namespace Botble\LarkWebhook\Http\Controllers\Settings;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\LarkWebhook\Forms\Settings\LarkWebhookSettingForm;
use Botble\LarkWebhook\Http\Requests\Settings\LarkWebhookSettingRequest;
use Botble\LarkWebhook\Services\LarkBaseClient;
use Botble\Setting\Http\Controllers\SettingController;
use Throwable;

class LarkWebhookSettingController extends SettingController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('core/base::base.panel.others'));
    }

    public function edit()
    {
        $this->pageTitle(trans('plugins/lark-webhook::lark-webhook.settings.name'));

        return LarkWebhookSettingForm::create()->renderForm();
    }

    public function update(LarkWebhookSettingRequest $request): BaseHttpResponse
    {
        // `webhook_url` is a read-only display field, never a stored setting.
        return $this->performUpdate($request->validated());
    }

    public function testPush(LarkBaseClient $client): BaseHttpResponse
    {
        try {
            $result = $client->diagnose();
        } catch (Throwable $e) {
            return $this->httpResponse()->setError()->setCode(422)->setMessage($e->getMessage());
        }

        $labels = [
            'config' => trans('plugins/lark-webhook::lark-webhook.settings.check_config'),
            'auth' => trans('plugins/lark-webhook::lark-webhook.settings.check_auth'),
            'read' => trans('plugins/lark-webhook::lark-webhook.settings.check_read'),
            'write' => trans('plugins/lark-webhook::lark-webhook.settings.check_write'),
            'upload' => trans('plugins/lark-webhook::lark-webhook.settings.check_upload'),
        ];

        $hints = [
            'write' => trans('plugins/lark-webhook::lark-webhook.settings.hint_write'),
            'upload' => trans('plugins/lark-webhook::lark-webhook.settings.hint_upload'),
            'read' => trans('plugins/lark-webhook::lark-webhook.settings.hint_read'),
        ];

        $lines = [];

        foreach ($result['checks'] as $check) {
            $label = $labels[$check['key']] ?? $check['key'];
            $lines[] = ($check['ok'] ? '✅ ' : '❌ ') . $label
                . ($check['ok'] ? '' : ' — ' . ($hints[$check['key']] ?? $check['detail']));
        }

        $message = implode('<br>', $lines);

        if (! $result['ok']) {
            return $this->httpResponse()->setError()->setCode(422)->setMessage($message);
        }

        return $this->httpResponse()->setMessage(
            trans('plugins/lark-webhook::lark-webhook.settings.test_push_success') . '<br>' . $message
        );
    }
}
