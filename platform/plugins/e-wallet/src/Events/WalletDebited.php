<?php

namespace Botble\EWallet\Events;

use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Illuminate\Foundation\Events\Dispatchable;

class WalletDebited
{
    use Dispatchable;

    public function __construct(
        public Wallet $wallet,
        public WalletTransaction $transaction
    ) {
    }
}
