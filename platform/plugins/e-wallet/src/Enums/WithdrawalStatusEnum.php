<?php

namespace Botble\EWallet\Enums;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static WithdrawalStatusEnum PENDING()
 * @method static WithdrawalStatusEnum PROCESSING()
 * @method static WithdrawalStatusEnum COMPLETED()
 * @method static WithdrawalStatusEnum REJECTED()
 * @method static WithdrawalStatusEnum CANCELLED()
 */
class WithdrawalStatusEnum extends Enum
{
    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const COMPLETED = 'completed';

    public const REJECTED = 'rejected';

    public const CANCELLED = 'cancelled';

    public static $langPath = 'plugins/e-wallet::withdrawal.statuses';

    public function toHtml(): HtmlString|string
    {
        $color = match ($this->value) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'secondary',
            default => 'primary',
        };

        return BaseHelper::renderBadge($this->label(), $color);
    }
}
