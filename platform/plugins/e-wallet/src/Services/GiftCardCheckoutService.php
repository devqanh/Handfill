<?php

namespace Botble\EWallet\Services;

use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Models\Invoice;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Exceptions\GiftCardAlreadyRedeemedException;
use Botble\EWallet\Exceptions\GiftCardExpiredException;
use Botble\EWallet\Exceptions\GiftCardInsufficientBalanceException;
use Botble\EWallet\Exceptions\InvalidGiftCardCodeException;
use Botble\EWallet\Models\GiftCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class GiftCardCheckoutService
{
    protected const SESSION_KEY = 'applied_gift_card';

    protected const DISCOUNT_SESSION_KEY = 'gift_card_discount';

    public function apply(string $code, int $orderTotal): array
    {
        $giftCard = GiftCard::query()
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

        if (! gift_card_allow_partial_use() && $giftCard->balance < $orderTotal) {
            throw new GiftCardInsufficientBalanceException(
                trans('plugins/e-wallet::gift-card.errors.insufficient_balance', [
                    'balance' => $giftCard->formatted_balance,
                    'total' => format_price($orderTotal / 100),
                ])
            );
        }

        $discountAmount = min($giftCard->balance, $orderTotal);
        $discountInDollars = $discountAmount / 100;

        Session::put(self::SESSION_KEY, [
            'id' => $giftCard->id,
            'code' => $giftCard->code,
            'balance' => $giftCard->balance,
            'currency_code' => $giftCard->currency_code,
            'discount_amount' => $discountAmount,
            'formatted_discount' => format_price($discountInDollars, $giftCard->gift_card_currency),
            'formatted_balance' => $giftCard->formatted_balance,
        ]);

        Session::put(self::DISCOUNT_SESSION_KEY, $discountInDollars);

        return Session::get(self::SESSION_KEY);
    }

    public function remove(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::DISCOUNT_SESSION_KEY);
    }

    public function getApplied(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    public function recalculate(int $orderTotal): ?array
    {
        $applied = $this->getApplied();

        if (! $applied) {
            return null;
        }

        $giftCard = GiftCard::find($applied['id']);

        if (! $giftCard || ! $giftCard->isRedeemable()) {
            $this->remove();

            return null;
        }

        $discountAmount = min($giftCard->balance, $orderTotal);

        Session::put(self::SESSION_KEY, array_merge($applied, [
            'balance' => $giftCard->balance,
            'discount_amount' => $discountAmount,
            'formatted_discount' => format_price($discountAmount / 100, $giftCard->gift_card_currency),
        ]));

        return Session::get(self::SESSION_KEY);
    }

    public function deductForOrder(Order $order): ?GiftCard
    {
        $applied = $this->getApplied();

        if (! $applied) {
            $applied = $this->getAppliedFromOrderMetadata($order);
        }

        if (! $applied) {
            return null;
        }

        if ($order->getOrderMetadata('gift_card_id')) {
            return null;
        }

        return DB::transaction(function () use ($order, $applied) {
            $giftCard = GiftCard::query()
                ->where('id', $applied['id'])
                ->lockForUpdate()
                ->first();

            if (! $giftCard || ! $giftCard->isRedeemable()) {
                $this->remove();
                $this->clearPendingMetadata($order);

                return null;
            }

            $deductAmount = min($applied['discount_amount'], $giftCard->balance);

            $giftCard->balance -= $deductAmount;

            if ($giftCard->balance <= 0) {
                $giftCard->balance = 0;
                $giftCard->status = GiftCardStatusEnum::REDEEMED;
                $giftCard->redeemed_at = now();
                $giftCard->redeemed_by_customer_id = $order->user_id;
            }

            $giftCard->save();

            $order->setOrderMetadata('gift_card_id', $giftCard->id);
            $order->setOrderMetadata('gift_card_code', $giftCard->masked_code);
            $order->setOrderMetadata('gift_card_discount', $deductAmount);

            $this->clearPendingMetadata($order);

            $discountInDollars = $deductAmount / 100;

            $order->discount_amount = ($order->discount_amount ?? 0) + $discountInDollars;

            $newOrderAmount = max(0, $order->amount - $discountInDollars);
            $order->amount = $newOrderAmount;
            $order->save();

            $this->updateInvoiceWithGiftCardDiscount($order, $discountInDollars);

            $this->remove();

            return $giftCard;
        });
    }

    protected function getAppliedFromOrderMetadata(Order $order): ?array
    {
        $giftCardId = $order->getOrderMetadata('pending_gift_card_id');

        if (! $giftCardId) {
            return null;
        }

        return [
            'id' => $giftCardId,
            'code' => $order->getOrderMetadata('pending_gift_card_code'),
            'discount_amount' => $order->getOrderMetadata('pending_gift_card_discount'),
        ];
    }

    protected function clearPendingMetadata(Order $order): void
    {
        $order->setOrderMetadata('pending_gift_card_id', null);
        $order->setOrderMetadata('pending_gift_card_code', null);
        $order->setOrderMetadata('pending_gift_card_discount', null);
    }

    protected function updateInvoiceWithGiftCardDiscount(Order $order, float $discount): void
    {
        $invoice = Invoice::query()
            ->where('reference_id', $order->id)
            ->where('reference_type', get_class($order))
            ->first();

        if (! $invoice) {
            return;
        }

        $invoice->discount_amount = ($invoice->discount_amount ?? 0) + $discount;
        $invoice->amount = max(0, $invoice->amount - $discount);
        $invoice->save();
    }

    public function getDiscountAmount(): int
    {
        $applied = $this->getApplied();

        return $applied['discount_amount'] ?? 0;
    }

    public function getCurrentOrderTotal(): int
    {
        $cart = Cart::instance('cart');
        $rawTotal = (int) ($cart->rawTotal() * 100);

        $couponDiscount = (int) (session('applied_coupon_amount', 0) * 100);
        $promotionDiscount = (int) (session('promotion_discount_amount', 0) * 100);
        $loyaltyDiscount = (int) (session('loyalty_points_discount', 0) * 100);

        return max(0, $rawTotal - $couponDiscount - $promotionDiscount - $loyaltyDiscount);
    }
}
