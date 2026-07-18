<?php

namespace Botble\EWallet\Events;

use Botble\EWallet\Models\WalletTransaction;
use Illuminate\Foundation\Events\Dispatchable;

class WalletTransactionCreated
{
    use Dispatchable;

    public function __construct(
        public WalletTransaction $transaction
    ) {
    }
}
