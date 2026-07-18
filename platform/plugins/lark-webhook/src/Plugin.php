<?php

namespace Botble\LarkWebhook;

use Botble\LarkWebhook\Supports\LarkWebhookSupport;
use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Models\Setting;
use Illuminate\Support\Facades\Schema;

class Plugin extends PluginOperationAbstract
{
    public static function activated(): void
    {
        LarkWebhookSupport::token();
    }

    public static function remove(): void
    {
        Schema::dropIfExists('lark_webhook_events');

        Setting::query()
            ->where('key', 'like', 'lark_webhook_%')
            ->delete();
    }
}
