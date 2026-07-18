<?php

namespace Botble\EWallet\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class DuplicateTransactionException extends Exception implements ShouldntReport
{
    public function __construct(string $idempotencyKey)
    {
        parent::__construct(
            trans('plugins/e-wallet::e-wallet.errors.duplicate_transaction')
        );
    }
}
