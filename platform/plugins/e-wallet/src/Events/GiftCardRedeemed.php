<?php

namespace Botble\EWallet\Events;

use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Models\WalletTransaction;
use Illuminate\Foundation\Events\Dispatchable;

class GiftCardRedeemed
{
    use Dispatchable;

    public function __construct(
        public GiftCard $giftCard,
        public WalletTransaction $transaction
    ) {
    }
}
