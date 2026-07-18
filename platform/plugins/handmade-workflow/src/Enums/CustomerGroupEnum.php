<?php

namespace Botble\HandmadeWorkflow\Enums;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static CustomerGroupEnum SHIP_VN()
 * @method static CustomerGroupEnum SHIP_QT()
 */
class CustomerGroupEnum extends Enum
{
    /** Đặt sản xuất hoàn thiện và tự ship — chỉ trả phí sản phẩm. */
    public const SHIP_VN = 'ship_vn';

    /** Đặt sản xuất và fulfill toàn bộ — SP + ship QT + packing + nguyên vật liệu. */
    public const SHIP_QT = 'ship_qt';

    public static $langPath = 'plugins/handmade-workflow::handmade-workflow.customer_groups';

    public static function of(?string $value): static
    {
        return (new static())->make($value);
    }

    public function toHtml(): HtmlString|string
    {
        return BaseHelper::renderBadge(
            $this->label(),
            $this->value === self::SHIP_QT ? 'info' : 'secondary'
        );
    }
}
