<?php

namespace Botble\LarkWebhook\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LarkWebhookEvent extends BaseModel
{
    protected $table = 'lark_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'schema_version',
        'app_id',
        'tenant_key',
        'status',
        'message',
        'payload',
        'headers',
        'ip_address',
        'event_created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'event_created_at' => 'datetime',
    ];

    protected function prettyPayload(): Attribute
    {
        return Attribute::get(
            fn () => json_encode($this->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        )->shouldCache();
    }

    protected function prettyHeaders(): Attribute
    {
        return Attribute::get(
            fn () => json_encode($this->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        )->shouldCache();
    }
}
