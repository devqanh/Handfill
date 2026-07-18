<?php

namespace Botble\LarkWebhook\Http\Controllers;

use Botble\LarkWebhook\Models\LarkWebhookEvent;
use Botble\LarkWebhook\Supports\LarkWebhookSupport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class LarkWebhookReceiverController extends Controller
{
    /**
     * Lets you paste the URL in a browser to confirm it is reachable.
     */
    public function ping(string $token): JsonResponse
    {
        abort_unless(hash_equals(LarkWebhookSupport::token(), $token), 404);

        return response()->json([
            'status' => 'ok',
            'message' => 'Lark webhook endpoint is ready. Send events here with POST.',
        ]);
    }

    public function receive(string $token, Request $request): JsonResponse
    {
        abort_unless(hash_equals(LarkWebhookSupport::token(), $token), 404);
        abort_unless(LarkWebhookSupport::isEnabled(), 403, 'Lark webhook is disabled.');

        $payload = $request->all();

        if ($encrypted = Arr::get($payload, 'encrypt')) {
            $encryptKey = LarkWebhookSupport::encryptKey();

            if (! $encryptKey) {
                return $this->reject($request, $payload, 'Received an encrypted payload but no Encrypt Key is configured.');
            }

            $decrypted = LarkWebhookSupport::decrypt($encrypted, $encryptKey);

            if ($decrypted === null) {
                return $this->reject($request, $payload, 'Unable to decrypt the payload. The Encrypt Key may be incorrect.');
            }

            $payload = $decrypted;
        }

        $meta = LarkWebhookSupport::extract($payload);

        if ($expected = LarkWebhookSupport::verificationToken()) {
            $given = $meta['token'];

            if (! $given || ! hash_equals($expected, (string) $given)) {
                return $this->reject($request, $payload, 'Verification token mismatch.');
            }
        }

        // Lark calls this once when you save the request URL in the developer console.
        if (Arr::get($payload, 'type') === 'url_verification') {
            $this->store($request, $payload, $meta, 'verified', 'URL verification challenge.');

            return response()->json(['challenge' => Arr::get($payload, 'challenge')]);
        }

        $this->store($request, $payload, $meta, 'received');

        return response()->json(['code' => 0, 'msg' => 'success']);
    }

    protected function reject(Request $request, array $payload, string $message): JsonResponse
    {
        $this->store($request, $payload, LarkWebhookSupport::extract($payload), 'rejected', $message);

        return response()->json(['code' => 1, 'msg' => $message], 400);
    }

    protected function store(Request $request, array $payload, array $meta, string $status, ?string $message = null): void
    {
        // Lark retries a delivery until it gets a 2xx, so the same event can arrive twice.
        if ($meta['event_id'] && LarkWebhookEvent::query()->where('event_id', $meta['event_id'])->exists()) {
            return;
        }

        LarkWebhookEvent::query()->create([
            'event_id' => $meta['event_id'],
            'event_type' => $meta['event_type'] ?: 'unknown',
            'schema_version' => $meta['schema_version'],
            'app_id' => $meta['app_id'],
            'tenant_key' => $meta['tenant_key'],
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
            'headers' => $this->sanitizeHeaders($request),
            'ip_address' => $request->ip(),
            'event_created_at' => $this->parseCreateTime($meta['create_time']),
        ]);
    }

    protected function sanitizeHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->except(['cookie', 'authorization'])
            ->map(fn (array $values) => implode(', ', $values))
            ->all();
    }

    protected function parseCreateTime(mixed $createTime): ?Carbon
    {
        if (! $createTime || ! is_numeric($createTime)) {
            return null;
        }

        $createTime = (int) $createTime;

        // Schema 2.0 sends milliseconds, schema 1.0 sends seconds.
        return Carbon::createFromTimestamp($createTime > 99999999999 ? intdiv($createTime, 1000) : $createTime);
    }
}
