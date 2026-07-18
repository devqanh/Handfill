<?php

namespace Botble\EWallet\Events;

use Botble\EWallet\Models\GiftCard;
use Illuminate\Foundation\Events\Dispatchable;

class GiftCardCreated
{
    use Dispatchable;

    public function __construct(public GiftCard $giftCard)
    {
    }
}
