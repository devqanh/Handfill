<?php

namespace Botble\EWallet\Listeners;

use Botble\Ecommerce\Events\OrderCancelledEvent;
use Botble\EWallet\Services\GiftCardRefundService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefundGiftCardOnOrderCancel implements ShouldQueue
{
    public function __construct(protected GiftCardRefundService $refundService)
    {
    }

    public function handle(OrderCancelledEvent $event): void
    {
        $this->refundService->refundForOrder($event->order);
    }
}
