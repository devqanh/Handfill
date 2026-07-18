<?php

namespace Botble\EWallet\Services;

use Botble\Base\Facades\EmailHandler;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Models\WalletTopUp;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TopUpService
{
    public function __construct(
        protected WalletService $walletService,
        protected WalletHelper $helper,
        protected WebhookService $webhookService
    ) {
    }

    public function createTopUp(
        int|string $customerId,
        int $amountCents,
        ?string $currencyCode = null,
        float $exchangeRate = 1.0
    ): WalletTopUp {
        $wallet = $this->walletService->getOrCreateWallet($customerId);
        $defaultCurrency = $this->helper->getDefaultCurrency();
        $currencyCode = $currencyCode ?? $defaultCurrency;

        $convertedAmount = $currencyCode !== $defaultCurrency
            ? (int) round($amountCents / $exchangeRate)
            : $amountCents;

        $minAmount = $this->helper->getMinTopUp();
        $maxAmount = $this->helper->getMaxTopUp();

        if ($convertedAmount < $minAmount) {
            throw new InvalidArgumentException(
                trans('plugins/e-wallet::e-wallet.errors.amount_below_minimum', [
                    'min' => format_price($minAmount / 100),
                ])
            );
        }

        if ($convertedAmount > $maxAmount) {
            throw new InvalidArgumentException(
                trans('plugins/e-wallet::e-wallet.errors.amount_above_maximum', [
                    'max' => format_price($maxAmount / 100),
                ])
            );
        }

        $topup = WalletTopUp::query()->create([
            'customer_id' => $customerId,
            'wallet_id' => $wallet->id,
            'code' => $this->generateCode(),
            'amount' => $amountCents,
            'currency_code' => $currencyCode,
            'converted_amount' => $convertedAmount,
            'wallet_currency_code' => $defaultCurrency,
            'exchange_rate' => $exchangeRate,
            'status' => TopUpStatusEnum::PENDING,
        ]);

        $this->webhookService->sendTopUpWebhook($topup, WebhookService::EVENT_TOPUP_CREATED);

        return $topup;
    }

    public function completeTopUp(
        WalletTopUp $topup,
        string $paymentId,
        ?string $paymentMethod = null
    ): WalletTopUp {
        if (! in_array($topup->status->getValue(), [TopUpStatusEnum::PENDING, TopUpStatusEnum::PROCESSING])) {
            return $topup;
        }

        $this->walletService->credit(
            customerId: $topup->customer_id,
            amountCents: $topup->converted_amount,
            type: TransactionTypeEnum::TOP_UP,
            referenceType: WalletTopUp::class,
            referenceId: $topup->id,
            description: trans('plugins/e-wallet::e-wallet.transaction.topup_completed', [
                'code' => $topup->code,
            ]),
            idempotencyKey: 'topup_' . $topup->id,
            metadata: [
                'topup_code' => $topup->code,
                'payment_amount' => $topup->amount,
                'payment_currency' => $topup->currency_code,
                'converted_amount' => $topup->converted_amount,
                'exchange_rate' => $topup->exchange_rate,
            ]
        );

        $updateData = [
            'status' => TopUpStatusEnum::COMPLETED,
            'payment_id' => $paymentId,
        ];

        if ($paymentMethod) {
            $updateData['payment_method'] = $paymentMethod;
        }

        $topup->update($updateData);

        $topup = $topup->fresh();

        $this->webhookService->sendTopUpWebhook($topup, WebhookService::EVENT_TOPUP_COMPLETED);

        $this->sendTopUpCompletedEmail($topup);

        return $topup;
    }

    public function failTopUp(WalletTopUp $topup, ?string $reason = null): WalletTopUp
    {
        $topup->update([
            'status' => TopUpStatusEnum::FAILED,
            'metadata' => array_merge($topup->metadata ?? [], [
                'failure_reason' => $reason,
            ]),
        ]);

        $topup = $topup->fresh();

        $this->webhookService->sendTopUpWebhook($topup, WebhookService::EVENT_TOPUP_FAILED);

        $this->sendTopUpFailedEmail($topup);

        return $topup;
    }

    public function cancelTopUp(WalletTopUp $topup): WalletTopUp
    {
        $topup->update([
            'status' => TopUpStatusEnum::CANCELLED,
        ]);

        $topup = $topup->fresh();

        $this->webhookService->sendTopUpWebhook($topup, WebhookService::EVENT_TOPUP_CANCELLED);

        return $topup;
    }

    protected function generateCode(): string
    {
        $prefix = $this->helper->getTopUpCodePrefix();

        do {
            $code = $prefix . strtoupper(Str::random(8));
        } while (WalletTopUp::query()->where('code', $code)->exists());

        return $code;
    }

    protected function sendTopUpCompletedEmail(WalletTopUp $topup): void
    {
        $customer = $topup->customer;

        if (! $customer || ! $customer->email) {
            return;
        }

        $wallet = $topup->wallet;

        EmailHandler::setModule(\E_WALLET_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'customer_name' => $customer->name,
                'topup_code' => $topup->code,
                'topup_amount' => $topup->formatted_converted_amount,
                'wallet_balance' => $wallet->formatted_balance,
                'payment_method' => $topup->payment_method,
            ])
            ->sendUsingTemplate('topup_completed', $customer->email);
    }

    protected function sendTopUpFailedEmail(WalletTopUp $topup): void
    {
        $customer = $topup->customer;

        if (! $customer || ! $customer->email) {
            return;
        }

        EmailHandler::setModule(\E_WALLET_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'customer_name' => $customer->name,
                'topup_code' => $topup->code,
                'topup_amount' => $topup->formatted_converted_amount,
            ])
            ->sendUsingTemplate('topup_failed', $customer->email);
    }
}
