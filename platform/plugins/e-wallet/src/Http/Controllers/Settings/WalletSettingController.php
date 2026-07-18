<?php

namespace Botble\EWallet\Http\Controllers\Settings;

use Botble\Base\Http\Controllers\BaseController;
use Botble\EWallet\Forms\Settings\WalletSettingForm;
use Botble\EWallet\Http\Requests\Settings\WalletSettingRequest;
use Botble\EWallet\Services\WebhookService;
use Botble\Setting\Facades\Setting;
use Illuminate\Http\Request;

class WalletSettingController extends BaseController
{
    public function __construct(protected WebhookService $webhookService)
    {
    }
    public function edit()
    {
        $this->pageTitle(trans('plugins/e-wallet::e-wallet.settings.title'));

        return WalletSettingForm::create()->renderForm();
    }

    public function update(WalletSettingRequest $request)
    {
        $settings = [
            ...$request->validated(),
            'e_wallet_topup_payment_methods' => $request->input('e_wallet_topup_payment_methods', []),
            'e_wallet_payout_methods' => $request->input('e_wallet_payout_methods', []),
        ];

        if (isset($settings['e_wallet_min_top_up'])) {
            $settings['e_wallet_min_top_up'] = (int) ($settings['e_wallet_min_top_up'] * 100);
        }

        if (isset($settings['e_wallet_max_top_up'])) {
            $settings['e_wallet_max_top_up'] = (int) ($settings['e_wallet_max_top_up'] * 100);
        }

        $checkboxFields = [
            'e_wallet_enable_e_wallet',
            'e_wallet_allow_negative_balance',
            'e_wallet_enable_top_up',
            'e_wallet_enable_wallet_payment',
            'e_wallet_enable_webhooks',
            'e_wallet_enable_withdrawal',
        ];

        foreach ($checkboxFields as $field) {
            if (! isset($settings[$field])) {
                $settings[$field] = 0;
            }
        }

        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $value = json_encode(array_filter($value));
            }

            Setting::set($key, $value);
        }

        $walletPaymentEnabled = $settings['e_wallet_enable_wallet_payment'] ?? 0;
        Setting::set('payment_' . E_WALLET_PAYMENT_METHOD_NAME . '_status', $walletPaymentEnabled);

        Setting::save();

        return $this->httpResponse()
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function testWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => ['required', 'url'],
            'webhook_type' => ['required', 'string'],
        ]);

        $result = $this->webhookService->testWebhook(
            $request->input('webhook_url'),
            $request->input('webhook_type')
        );

        return $this->httpResponse()
            ->setError(! $result['success'])
            ->setMessage($result['message'])
            ->setData(['status_code' => $result['status_code']]);
    }
}
