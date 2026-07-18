<?php

namespace Botble\EWallet\Services;

use Botble\Base\Facades\EmailHandler;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Events\GiftCardCancelled;
use Botble\EWallet\Events\GiftCardCreated;
use Botble\EWallet\Events\GiftCardRedeemed;
use Botble\EWallet\Exceptions\GiftCardAlreadyRedeemedException;
use Botble\EWallet\Exceptions\GiftCardExpiredException;
use Botble\EWallet\Exceptions\InvalidGiftCardCodeException;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Models\WalletTransaction;
use DateTimeInterface;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\DB;

class GiftCardService
{
    public function __construct(
        protected WalletService $walletService,
        protected GiftCardCodeGenerator $codeGenerator,
        protected WalletHelper $helper
    ) {
    }

    public function generate(
        int $valueCents,
        ?string $customCode = null,
        int|string|null $customerId = null,
        ?int $issuedBy = null,
        ?DateTimeInterface $expiresAt = null,
        ?string $note = null
    ): GiftCard {
        $code = $customCode ?? $this->codeGenerator->generate();

        while (GiftCard::query()->where('code', strtoupper($code))->exists()) {
            $code = $this->codeGenerator->generate();
        }

        $giftCard = GiftCard::query()->create([
            'code' => strtoupper($code),
            'initial_value' => $valueCents,
            'balance' => $valueCents,
            'currency_code' => $this->helper->getDefaultCurrency(),
            'status' => GiftCardStatusEnum::ACTIVE,
            'customer_id' => $customerId,
            'issued_by' => $issuedBy ?? auth()->id(),
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'note' => $note,
        ]);

        event(new GiftCardCreated($giftCard));

        return $giftCard;
    }

    public function generateBatch(
        int $valueCents,
        int $count,
        ?int $issuedBy = null,
        ?DateTimeInterface $expiresAt = null,
        ?string $note = null
    ): Collection {
        $giftCards = collect();

        DB::transaction(function () use ($valueCents, $count, $issuedBy, $expiresAt, $note, &$giftCards): void {
            for ($i = 0; $i < $count; $i++) {
                $giftCards->push($this->generate(
                    $valueCents,
                    null,
                    null,
                    $issuedBy,
                    $expiresAt,
                    $note
                ));
            }
        });

        return $giftCards;
    }

    public function getByCode(string $code): ?GiftCard
    {
        return GiftCard::query()
            ->where('code', strtoupper(trim($code)))
            ->first();
    }

    public function validate(string $code): GiftCard
    {
        $giftCard = $this->getByCode($code);

        if (! $giftCard) {
            throw new InvalidGiftCardCodeException(
                trans('plugins/e-wallet::gift-card.errors.not_found')
            );
        }

        if ($giftCard->status->getValue() === GiftCardStatusEnum::REDEEMED) {
            throw new GiftCardAlreadyRedeemedException(
                trans('plugins/e-wallet::gift-card.errors.already_redeemed')
            );
        }

        if ($giftCard->status->getValue() === GiftCardStatusEnum::CANCELLED) {
            throw new InvalidGiftCardCodeException(
                trans('plugins/e-wallet::gift-card.errors.cancelled')
            );
        }

        if ($giftCard->isExpired()) {
            $giftCard->update(['status' => GiftCardStatusEnum::EXPIRED]);

            throw new GiftCardExpiredException(
                trans('plugins/e-wallet::gift-card.errors.expired')
            );
        }

        if ($giftCard->balance <= 0) {
            throw new GiftCardAlreadyRedeemedException(
                trans('plugins/e-wallet::gift-card.errors.already_redeemed')
            );
        }

        return $giftCard;
    }

    public function redeem(
        string $code,
        int|string $customerId,
        ?string $idempotencyKey = null
    ): WalletTransaction {
        return DB::transaction(function () use ($code, $customerId, $idempotencyKey): WalletTransaction {
            $giftCard = GiftCard::query()
                ->lockForUpdate()
                ->where('code', strtoupper(trim($code)))
                ->first();

            if (! $giftCard) {
                throw new InvalidGiftCardCodeException(
                    trans('plugins/e-wallet::gift-card.errors.not_found')
                );
            }

            if (! $giftCard->isRedeemable()) {
                if ($giftCard->status->getValue() === GiftCardStatusEnum::REDEEMED) {
                    throw new GiftCardAlreadyRedeemedException(
                        trans('plugins/e-wallet::gift-card.errors.already_redeemed')
                    );
                }

                if ($giftCard->isExpired()) {
                    throw new GiftCardExpiredException(
                        trans('plugins/e-wallet::gift-card.errors.expired')
                    );
                }

                throw new InvalidGiftCardCodeException(
                    trans('plugins/e-wallet::gift-card.errors.not_redeemable')
                );
            }

            $redemptionAmount = $giftCard->balance;

            $transaction = $this->walletService->credit(
                $customerId,
                $redemptionAmount,
                TransactionTypeEnum::GIFT_CARD_REDEMPTION,
                GiftCard::class,
                $giftCard->id,
                trans('plugins/e-wallet::gift-card.redemption.credited_to_wallet', [
                    'code' => $giftCard->masked_code,
                ]),
                $idempotencyKey ?? 'gc_redeem_' . $giftCard->id . '_' . $customerId,
                [
                    'gift_card_id' => $giftCard->id,
                    'gift_card_code' => $giftCard->masked_code,
                    'original_value' => $giftCard->initial_value,
                ]
            );

            $giftCard->update([
                'balance' => 0,
                'status' => GiftCardStatusEnum::REDEEMED,
                'redeemed_by_customer_id' => $customerId,
                'redeemed_at' => now(),
            ]);

            event(new GiftCardRedeemed($giftCard, $transaction));

            return $transaction;
        });
    }

    public function cancel(int|string $giftCardId): GiftCard
    {
        $giftCard = GiftCard::query()->findOrFail($giftCardId);

        if ($giftCard->status->getValue() === GiftCardStatusEnum::REDEEMED) {
            throw new GiftCardAlreadyRedeemedException(
                trans('plugins/e-wallet::gift-card.errors.already_redeemed')
            );
        }

        $giftCard->update([
            'status' => GiftCardStatusEnum::CANCELLED,
        ]);

        event(new GiftCardCancelled($giftCard));

        return $giftCard->fresh();
    }

    public function checkBalance(string $code): array
    {
        try {
            $giftCard = $this->validate($code);

            return [
                'valid' => true,
                'balance' => $giftCard->balance,
                'formatted_balance' => $giftCard->formatted_balance,
                'currency_code' => $giftCard->currency_code,
                'expires_at' => $giftCard->expires_at?->toIso8601String(),
                'status' => $giftCard->status->getValue(),
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function purchaseWithWallet(
        int $valueCents,
        int|string $purchasedByCustomerId,
        ?string $recipientName = null,
        ?string $recipientEmail = null,
        ?string $giftMessage = null,
        ?DateTimeInterface $expiresAt = null
    ): GiftCard {
        return DB::transaction(function () use ($valueCents, $purchasedByCustomerId, $recipientName, $recipientEmail, $giftMessage, $expiresAt): GiftCard {
            $code = $this->codeGenerator->generate();

            while (GiftCard::query()->where('code', strtoupper($code))->exists()) {
                $code = $this->codeGenerator->generate();
            }

            $giftCard = GiftCard::query()->create([
                'code' => strtoupper($code),
                'initial_value' => $valueCents,
                'balance' => $valueCents,
                'currency_code' => $this->helper->getDefaultCurrency(),
                'status' => GiftCardStatusEnum::ACTIVE,
                'purchased_by_customer_id' => $purchasedByCustomerId,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'gift_message' => $giftMessage,
                'expires_at' => $expiresAt,
                'activated_at' => now(),
            ]);

            $this->walletService->debit(
                $purchasedByCustomerId,
                $valueCents,
                TransactionTypeEnum::GIFT_CARD_PURCHASE,
                GiftCard::class,
                $giftCard->id,
                trans('plugins/e-wallet::gift-card.purchase.wallet_debit_description', [
                    'code' => $giftCard->masked_code,
                    'amount' => $giftCard->formatted_initial_value,
                ]),
                'gc_purchase_' . $giftCard->id,
                [
                    'gift_card_id' => $giftCard->id,
                    'gift_card_code' => $giftCard->masked_code,
                ]
            );

            event(new GiftCardCreated($giftCard));

            if ($recipientEmail) {
                $this->sendGiftCardEmail($giftCard);
            }

            return $giftCard;
        });
    }

    public function createPurchase(
        int $valueCents,
        int|string $purchasedByCustomerId,
        ?string $recipientName = null,
        ?string $recipientEmail = null,
        ?string $giftMessage = null,
        ?DateTimeInterface $expiresAt = null
    ): GiftCard {
        $code = $this->codeGenerator->generate();

        while (GiftCard::query()->where('code', strtoupper($code))->exists()) {
            $code = $this->codeGenerator->generate();
        }

        return GiftCard::query()->create([
            'code' => strtoupper($code),
            'initial_value' => $valueCents,
            'balance' => $valueCents,
            'currency_code' => $this->helper->getDefaultCurrency(),
            'status' => GiftCardStatusEnum::PENDING,
            'purchased_by_customer_id' => $purchasedByCustomerId,
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'gift_message' => $giftMessage,
            'expires_at' => $expiresAt,
        ]);
    }

    public function activatePurchase(GiftCard $giftCard, int|string|null $orderId = null): GiftCard
    {
        $giftCard->update([
            'status' => GiftCardStatusEnum::ACTIVE,
            'activated_at' => now(),
            'purchase_order_id' => $orderId,
        ]);

        event(new GiftCardCreated($giftCard));

        if ($giftCard->recipient_email) {
            $this->sendGiftCardEmail($giftCard);
        }

        return $giftCard->fresh();
    }

    public function deductBalance(GiftCard $giftCard, int $amount, int|string|null $customerId = null): GiftCard
    {
        return DB::transaction(function () use ($giftCard, $amount, $customerId): GiftCard {
            $giftCard = GiftCard::query()
                ->lockForUpdate()
                ->find($giftCard->id);

            $deductAmount = min($amount, $giftCard->balance);
            $giftCard->balance -= $deductAmount;

            if ($giftCard->balance <= 0) {
                $giftCard->balance = 0;
                $giftCard->status = GiftCardStatusEnum::REDEEMED;
                $giftCard->redeemed_at = now();

                if ($customerId) {
                    $giftCard->redeemed_by_customer_id = $customerId;
                }
            }

            $giftCard->save();

            return $giftCard;
        });
    }

    public function sendGiftCardEmail(GiftCard $giftCard): void
    {
        if (! $giftCard->recipient_email) {
            return;
        }

        $senderName = $giftCard->purchasedBy?->name ?? setting('site_title', config('app.name'));

        EmailHandler::setModule(\E_WALLET_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'recipient_name' => $giftCard->recipient_name ?? trans('plugins/e-wallet::gift-card.email.default_recipient'),
                'sender_name' => $senderName,
                'gift_card_code' => $giftCard->code,
                'gift_card_value' => $giftCard->formatted_initial_value,
                'gift_card_expiry' => $giftCard->expires_at?->format('F j, Y'),
                'gift_message' => $giftCard->gift_message,
                'redeem_url' => route('public.gift-card.check') . '?code=' . urlencode($giftCard->code),
            ])
            ->sendUsingTemplate('gift_card_shared', $giftCard->recipient_email);
    }

    public function resendGiftCardEmail(GiftCard $giftCard): bool
    {
        if (! $giftCard->recipient_email) {
            return false;
        }

        $this->sendGiftCardEmail($giftCard);

        return true;
    }
}
