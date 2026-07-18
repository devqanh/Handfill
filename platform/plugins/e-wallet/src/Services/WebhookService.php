<?php

namespace Botble\EWallet\Services;

use Botble\EWallet\Models\WalletTopUp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookService
{
    public const EVENT_TOPUP_CREATED = 'topup.created';

    public const EVENT_TOPUP_COMPLETED = 'topup.completed';

    public const EVENT_TOPUP_FAILED = 'topup.failed';

    public const EVENT_TOPUP_CANCELLED = 'topup.cancelled';

    public function getWebhookUrl(string $event): ?string
    {
        return match ($event) {
            self::EVENT_TOPUP_CREATED => get_wallet_setting('topup_created_webhook_url'),
            self::EVENT_TOPUP_COMPLETED => get_wallet_setting('topup_completed_webhook_url'),
            self::EVENT_TOPUP_FAILED => get_wallet_setting('topup_failed_webhook_url'),
            self::EVENT_TOPUP_CANCELLED => get_wallet_setting('topup_cancelled_webhook_url'),
            default => null,
        };
    }

    public function isEnabled(): bool
    {
        return (bool) get_wallet_setting('enable_webhooks', false);
    }

    public function sendTopUpWebhook(WalletTopUp $topup, string $event): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $url = $this->getWebhookUrl($event);

        if (! $url) {
            return false;
        }

        $payload = $this->buildTopUpPayload($topup, $event);

        return $this->send($url, $payload);
    }

    public function buildTopUpPayload(WalletTopUp $topup, string $event): array
    {
        $topup->load('customer:id,name,email');

        $data = [
            'topup_id' => $topup->id,
            'topup_code' => $topup->code,
            'customer_id' => $topup->customer_id,
            'customer_email' => $topup->customer?->email,
            'customer_name' => $topup->customer?->name,
            'amount' => $topup->amount,
            'currency_code' => $topup->currency_code,
            'converted_amount' => $topup->converted_amount,
            'wallet_currency_code' => $topup->wallet_currency_code,
            'exchange_rate' => $topup->exchange_rate,
            'status' => $topup->status->getValue(),
            'payment_method' => $topup->payment_method,
            'payment_id' => $topup->payment_id,
            'created_at' => $topup->created_at?->toIso8601String(),
            'updated_at' => $topup->updated_at?->toIso8601String(),
        ];

        if ($event === self::EVENT_TOPUP_FAILED && isset($topup->metadata['failure_reason'])) {
            $data['failure_reason'] = $topup->metadata['failure_reason'];
        }

        return [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];
    }

    public function send(string $url, array $payload): bool
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'X-Webhook-Event' => $payload['event'],
                'X-Webhook-Timestamp' => $payload['timestamp'],
            ];

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::warning('E-Wallet webhook failed', [
                    'url' => $url,
                    'event' => $payload['event'],
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('E-Wallet webhook sent successfully', [
                'url' => $url,
                'event' => $payload['event'],
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('E-Wallet webhook error', [
                'url' => $url,
                'event' => $payload['event'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function testWebhook(string $url, string $webhookType): array
    {
        $event = match ($webhookType) {
            'topup_created' => self::EVENT_TOPUP_CREATED,
            'topup_completed' => self::EVENT_TOPUP_COMPLETED,
            'topup_failed' => self::EVENT_TOPUP_FAILED,
            'topup_cancelled' => self::EVENT_TOPUP_CANCELLED,
            default => self::EVENT_TOPUP_CREATED,
        };

        $payload = $this->getTestPayload($event);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'X-Webhook-Event' => $payload['event'],
                'X-Webhook-Timestamp' => $payload['timestamp'],
                'X-Webhook-Test' => 'true',
            ];

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $payload);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message' => $response->successful()
                    ? trans('plugins/e-wallet::e-wallet.webhook.test_success')
                    : trans('plugins/e-wallet::e-wallet.webhook.test_failed'),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'status_code' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function getTestPayload(string $event): array
    {
        return [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'topup_id' => 123,
                'topup_code' => 'TU-TEST12345',
                'customer_id' => 1,
                'customer_email' => 'test@example.com',
                'customer_name' => 'Test Customer',
                'amount' => 10000,
                'currency_code' => 'USD',
                'converted_amount' => 10000,
                'wallet_currency_code' => 'USD',
                'exchange_rate' => 1.0,
                'status' => match ($event) {
                    self::EVENT_TOPUP_CREATED => 'pending',
                    self::EVENT_TOPUP_COMPLETED => 'completed',
                    self::EVENT_TOPUP_FAILED => 'failed',
                    self::EVENT_TOPUP_CANCELLED => 'cancelled',
                    default => 'pending',
                },
                'payment_method' => $event === self::EVENT_TOPUP_COMPLETED ? 'stripe' : null,
                'payment_id' => $event === self::EVENT_TOPUP_COMPLETED ? 'pi_test123' : null,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }
}
