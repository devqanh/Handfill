<?php

namespace Botble\HandmadeWorkflow\Events;

use Botble\Base\Events\Event;
use Botble\Ecommerce\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after an order moves between production steps. Later phases hook into this
 * to charge the wallet milestones and to push the order to Lark Base.
 */
class ProductionStatusChanged extends Event
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $from,
        public string $to,
        public ?string $note = null
    ) {
    }
}
