<?php

namespace Botble\EWallet\Events;

use Botble\EWallet\Models\GiftCard;
use Illuminate\Foundation\Events\Dispatchable;

class GiftCardCancelled
{
    use Dispatchable;

    public function __construct(public GiftCard $giftCard)
    {
    }
}
