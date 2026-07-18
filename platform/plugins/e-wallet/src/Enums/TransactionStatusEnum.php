<?php

namespace Botble\EWallet\Enums;

use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

class TransactionStatusEnum extends Enum
{
    public const PENDING = 'pending';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public static $langPath = 'plugins/e-wallet::e-wallet.transaction.statuses';

    public function color(): string
    {
        return match ($this->value) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    public function toHtml(): HtmlString
    {
        return new HtmlString(sprintf(
            '<span class="badge bg-%s text-white">%s</span>',
            $this->color(),
            $this->label()
        ));
    }

    public function badge(): HtmlString
    {
        return $this->toHtml();
    }
}
