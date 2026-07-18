<?php

namespace Botble\LarkWebhook\Supports;

use Botble\Setting\Facades\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LarkWebhookSupport
{
    /**
     * Secret path segment of the webhook URL. Generated once on activation so the
     * endpoint is not guessable, and reused for every install afterwards.
     */
    public static function token(): string
    {
        $token = setting('lark_webhook_token');

        if (! $token) {
            $token = Str::lower(Str::random(32));

            Setting::set('lark_webhook_token', $token)->save();
        }

        return $token;
    }

    public static function regenerateToken(): string
    {
        $token = Str::lower(Str::random(32));

        Setting::set('lark_webhook_token', $token)->save();

        return $token;
    }

    public static function webhookUrl(): string
    {
        return route('lark-webhook.receive', ['token' => static::token()]);
    }

    public static function isEnabled(): bool
    {
        return (bool) setting('lark_webhook_enabled', true);
    }

    public static function verificationToken(): ?string
    {
        return setting('lark_webhook_verification_token') ?: null;
    }

    public static function encryptKey(): ?string
    {
        return setting('lark_webhook_encrypt_key') ?: null;
    }

    /**
     * Lark encrypts the body as base64(iv . ciphertext), AES-256-CBC,
     * with the key being the raw sha256 digest of the Encrypt Key.
     */
    public static function decrypt(string $encrypted, string $encryptKey): ?array
    {
        $raw = base64_decode($encrypted, true);

        if ($raw === false || strlen($raw) <= 16) {
            return null;
        }

        $decrypted = openssl_decrypt(
            substr($raw, 16),
            'AES-256-CBC',
            hash('sha256', $encryptKey, true),
            OPENSSL_RAW_DATA,
            substr($raw, 0, 16)
        );

        if ($decrypted === false) {
            return null;
        }

        $decoded = json_decode($decrypted, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Normalize a payload coming from any Lark flavour (event subscription v2/v1,
     * or a plain JSON body sent by a Base automation) into our columns.
     */
    public static function extract(array $payload): array
    {
        $header = Arr::get($payload, 'header');

        if (is_array($header)) {
            return [
                'event_id' => Arr::get($header, 'event_id'),
                'event_type' => Arr::get($header, 'event_type'),
                'schema_version' => Arr::get($payload, 'schema', '2.0'),
                'app_id' => Arr::get($header, 'app_id'),
                'tenant_key' => Arr::get($header, 'tenant_key'),
                'token' => Arr::get($header, 'token'),
                'create_time' => Arr::get($header, 'create_time'),
            ];
        }

        return [
            'event_id' => Arr::get($payload, 'uuid'),
            'event_type' => Arr::get($payload, 'event.type') ?: Arr::get($payload, 'type'),
            'schema_version' => '1.0',
            'app_id' => Arr::get($payload, 'event.app_id'),
            'tenant_key' => Arr::get($payload, 'event.tenant_key'),
            'token' => Arr::get($payload, 'token'),
            'create_time' => Arr::get($payload, 'ts'),
        ];
    }
}
