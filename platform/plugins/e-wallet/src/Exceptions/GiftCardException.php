<?php

namespace Botble\EWallet\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class GiftCardException extends Exception implements ShouldntReport
{
}
