<?php

namespace Botble\EWallet\Services;

use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Models\GiftCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftCardRefundService
{
    public function refundForOrder(Order $order): ?GiftCard
    {
        if (! function_exists('gift_cards_enabled') || ! gift_cards_enabled()) {
            return null;
        }

        $giftCardId = $order->getOrderMetadata('gift_card_id');
        $giftCardDiscount = $order->getOrderMetadata('gift_card_discount');

        if (! $giftCardId || ! $giftCardDiscount) {
            return null;
        }

        if ($order->getOrderMetadata('gift_card_refunded')) {
            return null;
        }

        return DB::transaction(function () use ($order, $giftCardId, $giftCardDiscount) {
            $giftCard = GiftCard::query()
                ->where('id', $giftCardId)
                ->lockForUpdate()
                ->first();

            if (! $giftCard) {
                Log::warning('Gift card not found for refund', [
                    'order_id' => $order->id,
                    'gift_card_id' => $giftCardId,
                ]);

                return null;
            }

            $giftCard->balance += $giftCardDiscount;

            if ($giftCard->status === GiftCardStatusEnum::REDEEMED && $giftCard->balance > 0) {
                $giftCard->status = GiftCardStatusEnum::ACTIVE;
                $giftCard->redeemed_at = null;
                $giftCard->redeemed_by_customer_id = null;
            }

            $giftCard->save();

            $order->setOrderMetadata('gift_card_refunded', true);
            $order->setOrderMetadata('gift_card_refunded_at', now()->toDateTimeString());

            Log::info('Gift card balance refunded', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'gift_card_id' => $giftCard->id,
                'gift_card_code' => $giftCard->code,
                'refunded_amount' => $giftCardDiscount,
                'new_balance' => $giftCard->balance,
            ]);

            return $giftCard;
        });
    }
}
