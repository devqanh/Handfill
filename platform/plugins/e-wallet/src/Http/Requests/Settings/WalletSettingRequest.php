<?php

namespace Botble\EWallet\Http\Requests\Settings;

use Botble\EWallet\Enums\PayoutPaymentMethodsEnum;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class WalletSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'e_wallet_enable_e_wallet' => ['nullable', 'in:0,1'],
            'e_wallet_allow_negative_balance' => ['nullable', 'in:0,1'],
            'e_wallet_refund_to_wallet' => ['nullable', 'in:wallet,payment_method'],
            'e_wallet_enable_top_up' => ['nullable', 'in:0,1'],
            'e_wallet_min_top_up' => ['nullable', 'numeric', 'min:0.01'],
            'e_wallet_max_top_up' => ['nullable', 'numeric', 'min:0.01', 'gte:e_wallet_min_top_up'],
            'e_wallet_topup_code_prefix' => ['nullable', 'string', 'min:1', 'max:5', 'regex:/^[A-Za-z0-9\-]+$/'],
            'e_wallet_topup_payment_methods' => ['nullable', 'array'],
            'e_wallet_topup_payment_methods.*' => ['string', Rule::in(PaymentMethodEnum::values())],
            'e_wallet_enable_withdrawal' => ['nullable', 'in:0,1'],
            'e_wallet_min_withdrawal' => ['nullable', 'numeric', 'min:0.01'],
            'e_wallet_max_withdrawal' => ['nullable', 'numeric', 'min:0.01', 'gte:e_wallet_min_withdrawal'],
            'e_wallet_payout_methods' => ['nullable', 'array'],
            'e_wallet_payout_methods.*' => ['string', Rule::in(PayoutPaymentMethodsEnum::values())],
            'e_wallet_enable_webhooks' => ['nullable', 'in:0,1'],
            'e_wallet_topup_created_webhook_url' => ['nullable', 'url', 'max:500'],
            'e_wallet_topup_completed_webhook_url' => ['nullable', 'url', 'max:500'],
            'e_wallet_topup_failed_webhook_url' => ['nullable', 'url', 'max:500'],
            'e_wallet_topup_cancelled_webhook_url' => ['nullable', 'url', 'max:500'],
            'e_wallet_gift_cards_enabled' => ['nullable', 'in:0,1'],
            'e_wallet_gift_card_code_prefix' => ['nullable', 'string', 'min:2', 'max:4', 'alpha'],
            'e_wallet_gift_card_default_expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'e_wallet_gift_card_min_value' => ['nullable', 'numeric', 'min:0.01'],
            'e_wallet_gift_card_max_value' => ['nullable', 'numeric', 'min:0.01', 'gte:e_wallet_gift_card_min_value'],
            'e_wallet_gift_card_public_balance_check' => ['nullable', 'in:0,1'],
            'e_wallet_gift_card_allow_partial_use' => ['nullable', 'in:0,1'],
            'e_wallet_unified_discount_field' => ['nullable', 'in:0,1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'e_wallet_enable_e_wallet' => trans('plugins/e-wallet::e-wallet.settings.enable_e_wallet'),
            'e_wallet_refund_to_wallet' => trans('plugins/e-wallet::e-wallet.settings.refund_destination'),
            'e_wallet_min_top_up' => trans('plugins/e-wallet::e-wallet.settings.min_top_up'),
            'e_wallet_max_top_up' => trans('plugins/e-wallet::e-wallet.settings.max_top_up'),
            'e_wallet_topup_code_prefix' => trans('plugins/e-wallet::e-wallet.settings.topup_code_prefix'),
            'e_wallet_topup_payment_methods' => trans('plugins/e-wallet::e-wallet.settings.topup_payment_methods'),
            'e_wallet_enable_withdrawal' => trans('plugins/e-wallet::withdrawal.settings.enable_withdrawal'),
            'e_wallet_min_withdrawal' => trans('plugins/e-wallet::withdrawal.settings.min_withdrawal'),
            'e_wallet_max_withdrawal' => trans('plugins/e-wallet::withdrawal.settings.max_withdrawal'),
            'e_wallet_payout_methods' => trans('plugins/e-wallet::withdrawal.settings.payout_methods'),
            'e_wallet_topup_created_webhook_url' => trans('plugins/e-wallet::e-wallet.webhook.topup_created_url'),
            'e_wallet_topup_completed_webhook_url' => trans('plugins/e-wallet::e-wallet.webhook.topup_completed_url'),
            'e_wallet_topup_failed_webhook_url' => trans('plugins/e-wallet::e-wallet.webhook.topup_failed_url'),
            'e_wallet_topup_cancelled_webhook_url' => trans('plugins/e-wallet::e-wallet.webhook.topup_cancelled_url'),
            'e_wallet_gift_cards_enabled' => trans('plugins/e-wallet::gift-card.settings.enable'),
            'e_wallet_gift_card_code_prefix' => trans('plugins/e-wallet::gift-card.settings.code_prefix'),
            'e_wallet_gift_card_default_expiry_days' => trans('plugins/e-wallet::gift-card.settings.default_expiry_days'),
            'e_wallet_gift_card_min_value' => trans('plugins/e-wallet::gift-card.settings.min_value'),
            'e_wallet_gift_card_max_value' => trans('plugins/e-wallet::gift-card.settings.max_value'),
            'e_wallet_gift_card_public_balance_check' => trans('plugins/e-wallet::gift-card.settings.public_balance_check'),
        ];
    }
}
