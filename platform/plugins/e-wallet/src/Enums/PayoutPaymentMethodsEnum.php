<?php

namespace Botble\EWallet\Enums;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Enum;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

/**
 * @method static PayoutPaymentMethodsEnum BANK_TRANSFER()
 * @method static PayoutPaymentMethodsEnum PAYPAL()
 * @method static PayoutPaymentMethodsEnum OTHER()
 */
class PayoutPaymentMethodsEnum extends Enum
{
    public const BANK_TRANSFER = 'bank_transfer';

    public const PAYPAL = 'paypal';

    public const OTHER = 'other';

    public static $langPath = 'plugins/e-wallet::withdrawal.payout_payment_methods';

    public function toHtml(): HtmlString|string
    {
        return match ($this->value) {
            self::BANK_TRANSFER => BaseHelper::renderBadge(trans('plugins/e-wallet::withdrawal.bank_transfer')),
            self::PAYPAL => BaseHelper::renderBadge(trans('plugins/e-wallet::withdrawal.paypal'), 'info'),
            self::OTHER => BaseHelper::renderBadge(trans('plugins/e-wallet::withdrawal.other'), 'secondary'),
            default => parent::toHtml(),
        };
    }

    public static function payoutMethodsEnabled(): array
    {
        $data = [
            self::BANK_TRANSFER => [
                'is_enabled' => (bool) Arr::get(get_wallet_setting('payout_methods', []), self::BANK_TRANSFER, true),
                'key' => self::BANK_TRANSFER,
                'label' => self::BANK_TRANSFER()->label(),
                'fields' => [
                    'bank_info' => [
                        'title' => trans('plugins/e-wallet::withdrawal.bank_information'),
                        'rules' => 'max:500',
                    ],
                ],
            ],
            self::PAYPAL => [
                'is_enabled' => (bool) Arr::get(get_wallet_setting('payout_methods', []), self::PAYPAL, true),
                'key' => self::PAYPAL,
                'label' => self::PAYPAL()->label(),
                'fields' => [
                    'paypal_id' => [
                        'title' => trans('plugins/e-wallet::withdrawal.paypal_id'),
                        'rules' => 'max:120',
                    ],
                ],
            ],
            self::OTHER => [
                'is_enabled' => (bool) Arr::get(get_wallet_setting('payout_methods', []), self::OTHER, true),
                'key' => self::OTHER,
                'label' => self::OTHER()->label(),
                'fields' => [
                    'payment_details' => [
                        'title' => trans('plugins/e-wallet::withdrawal.payment_details'),
                        'rules' => 'max:500',
                    ],
                ],
            ],
        ];

        return apply_filters('e_wallet_payout_methods', $data);
    }
}
