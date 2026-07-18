<?php

namespace Botble\EWallet\Providers;

use Botble\Ecommerce\Events\OrderCancelledEvent;
use Botble\Ecommerce\Events\OrderReturnedEvent;
use Botble\EWallet\Listeners\ProcessOrderRefund;
use Botble\EWallet\Listeners\RefundGiftCardOnOrderCancel;
use Botble\EWallet\Listeners\RefundGiftCardOnOrderReturn;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderReturnedEvent::class => [
            ProcessOrderRefund::class,
            RefundGiftCardOnOrderReturn::class,
        ],
        OrderCancelledEvent::class => [
            RefundGiftCardOnOrderCancel::class,
        ],
    ];
}
