<?php

namespace Botble\EWallet\Enums;

use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

class GiftCardStatusEnum extends Enum
{
    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    public const REDEEMED = 'redeemed';

    public const EXPIRED = 'expired';

    public const CANCELLED = 'cancelled';

    public static $langPath = 'plugins/e-wallet::gift-card.statuses';

    public function color(): string
    {
        return match ($this->value) {
            self::PENDING => 'secondary',
            self::ACTIVE => 'success',
            self::REDEEMED => 'primary',
            self::EXPIRED => 'warning',
            self::CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function badge(): string
    {
        return sprintf(
            '<span class="badge bg-%s text-white">%s</span>',
            $this->color(),
            $this->label()
        );
    }

    public function toHtml(): HtmlString
    {
        return new HtmlString($this->badge());
    }
}
