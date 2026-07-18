<?php

namespace Botble\EWallet\Listeners;

use Botble\Ecommerce\Events\OrderReturnedEvent;
use Botble\EWallet\Services\GiftCardRefundService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefundGiftCardOnOrderReturn implements ShouldQueue
{
    public function __construct(protected GiftCardRefundService $refundService)
    {
    }

    public function handle(OrderReturnedEvent $event): void
    {
        $order = $event->order->order;

        if ($order) {
            $this->refundService->refundForOrder($order);
        }
    }
}
