<?php

namespace Botble\HandmadeWorkflow\Enums;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Enum;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Illuminate\Support\HtmlString;

/**
 * @method static ProductionStatusEnum PENDING_APPROVAL()
 * @method static ProductionStatusEnum DEPOSITED()
 * @method static ProductionStatusEnum PREPARING()
 * @method static ProductionStatusEnum PRODUCING()
 * @method static ProductionStatusEnum PRODUCED()
 * @method static ProductionStatusEnum AWAITING_CONFIRMATION()
 * @method static ProductionStatusEnum CONFIRMED()
 * @method static ProductionStatusEnum PACKING()
 * @method static ProductionStatusEnum SHIPPING()
 * @method static ProductionStatusEnum COMPLETED()
 * @method static ProductionStatusEnum CANCELED()
 */
class ProductionStatusEnum extends Enum
{
    public const PENDING_APPROVAL = 'pending_approval';

    public const DEPOSITED = 'deposited';

    public const PREPARING = 'preparing';

    public const PRODUCING = 'producing';

    public const PRODUCED = 'produced';

    public const AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const CONFIRMED = 'confirmed';

    public const PACKING = 'packing';

    public const SHIPPING = 'shipping';

    public const COMPLETED = 'completed';

    public const CANCELED = 'canceled';

    public static $langPath = 'plugins/handmade-workflow::handmade-workflow.statuses';

    /**
     * Build an instance from a raw value. The parent `make()` is an instance method,
     * so this keeps call sites readable.
     */
    public static function of(?string $value): static
    {
        return (new static())->make($value);
    }

    /**
     * The 10 production steps in order, excluding CANCELED (which is not part of the line).
     *
     * @return array<int, string>
     */
    public static function flow(): array
    {
        return [
            self::PENDING_APPROVAL,
            self::DEPOSITED,
            self::PREPARING,
            self::PRODUCING,
            self::PRODUCED,
            self::AWAITING_CONFIRMATION,
            self::CONFIRMED,
            self::PACKING,
            self::SHIPPING,
            self::COMPLETED,
        ];
    }

    /**
     * Position of a status within the flow, or null when it is off-flow (canceled).
     */
    public static function stepIndex(?string $status): ?int
    {
        $index = array_search($status, self::flow(), true);

        return $index === false ? null : $index;
    }

    /**
     * Which core order status this production step maps to, so core logic
     * (cancel / return / invoice availability) keeps working.
     */
    public function toOrderStatus(): string
    {
        return match ($this->value) {
            self::PENDING_APPROVAL => OrderStatusEnum::PENDING,
            self::COMPLETED => OrderStatusEnum::COMPLETED,
            self::CANCELED => OrderStatusEnum::CANCELED,
            default => OrderStatusEnum::PROCESSING,
        };
    }

    public function toHtml(): HtmlString|string
    {
        $color = match ($this->value) {
            self::PENDING_APPROVAL, self::AWAITING_CONFIRMATION => 'warning',
            self::DEPOSITED, self::PREPARING, self::CONFIRMED, self::PACKING => 'info',
            self::COMPLETED => 'success',
            self::CANCELED => 'danger',
            default => 'primary',
        };

        return BaseHelper::renderBadge($this->label(), $color, icon: $this->getIcon());
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::PENDING_APPROVAL => 'ti ti-file-search',
            self::DEPOSITED => 'ti ti-cash',
            self::PREPARING => 'ti ti-clipboard-list',
            self::PRODUCING => 'ti ti-tools',
            self::PRODUCED => 'ti ti-checkbox',
            self::AWAITING_CONFIRMATION => 'ti ti-hourglass',
            self::CONFIRMED => 'ti ti-thumb-up',
            self::PACKING => 'ti ti-package',
            self::SHIPPING => 'ti ti-truck-delivery',
            self::COMPLETED => 'ti ti-circle-check',
            self::CANCELED => 'ti ti-circle-x',
            default => 'ti ti-circle',
        };
    }
}
