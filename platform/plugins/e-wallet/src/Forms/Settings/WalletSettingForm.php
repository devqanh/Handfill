<?php

namespace Botble\EWallet\Forms\Settings;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\AlertFieldOption;
use Botble\Base\Forms\FieldOptions\MultiChecklistFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\RadioFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\AlertField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\MultiCheckListField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\RadioField;
use Botble\Base\Forms\Fields\TextField;
use Botble\EWallet\Enums\PayoutPaymentMethodsEnum;
use Botble\EWallet\Http\Requests\Settings\WalletSettingRequest;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Setting\Forms\SettingForm;

class WalletSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $isEnabled = get_wallet_setting('enable_e_wallet', true);
        $currency = cms_currency()->getDefaultCurrency();

        $this
            ->setSectionTitle(trans('plugins/e-wallet::e-wallet.settings.title'))
            ->setSectionDescription(trans('plugins/e-wallet::e-wallet.settings.description'))
            ->setValidatorClass(WalletSettingRequest::class)

            ->add(
                'e_wallet_enable_e_wallet',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.settings.enable_e_wallet'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.settings.enable_e_wallet_help'))
                    ->value($isEnabled)
                    ->attributes([
                        'data-bb-toggle' => 'collapse',
                        'data-bb-target' => '.wallet-settings',
                    ])
            )

            ->add('open_fieldset_wallet_settings', HtmlField::class, [
                'html' => sprintf(
                    '<fieldset class="form-fieldset wallet-settings" style="display: %s;" data-bb-value="1">',
                    $isEnabled ? 'block' : 'none'
                ),
            ])

            ->add('balance_section_heading', HtmlField::class, [
                'html' => sprintf(
                    '<div class="alert alert-primary d-flex align-items-center mb-4"><x-core::icon name="ti ti-wallet" class="icon-lg me-2" /><div><h5 class="alert-heading mb-1">%s</h5><p class="mb-0">%s</p></div></div>',
                    trans('plugins/e-wallet::e-wallet.settings.balance_section'),
                    trans('plugins/e-wallet::e-wallet.settings.balance_section_description')
                ),
            ])

            ->add(
                'e_wallet_allow_negative_balance',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.settings.allow_negative_balance'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.settings.allow_negative_balance_help'))
                    ->value(get_wallet_setting('allow_negative_balance', false))
            )

            ->add('refund_section_divider', HtmlField::class, [
                'html' => '<hr class="my-4">',
            ])

            ->add('refund_section_heading', HtmlField::class, [
                'html' => sprintf(
                    '<div class="alert alert-info d-flex align-items-center mb-4"><x-core::icon name="ti ti-receipt-refund" class="icon-lg me-2" /><div><h5 class="alert-heading mb-1">%s</h5><p class="mb-0">%s</p></div></div>',
                    trans('plugins/e-wallet::e-wallet.settings.refund_section'),
                    trans('plugins/e-wallet::e-wallet.settings.refund_section_description')
                ),
            ])

            ->add(
                'e_wallet_refund_to_wallet',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.settings.refund_destination'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.settings.refund_destination_help'))
                    ->choices([
                        'wallet' => trans('plugins/e-wallet::e-wallet.settings.refund_to_wallet'),
                        'payment_method' => trans('plugins/e-wallet::e-wallet.settings.refund_to_payment_method'),
                    ])
                    ->selected(get_wallet_setting('refund_to_wallet', 'wallet'))
            )

            ->add(
                'refund_wallet_info',
                AlertField::class,
                AlertFieldOption::make()
                    ->type('info')
                    ->content(trans('plugins/e-wallet::e-wallet.settings.refund_wallet_info'))
            )

            ->add('withdrawal_section_divider', HtmlField::class, [
                'html' => '<hr class="my-4">',
            ])

            ->add('withdrawal_section_heading', HtmlField::class, [
                'html' => sprintf(
                    '<div class="alert alert-danger d-flex align-items-center mb-4"><x-core::icon name="ti ti-arrow-down-right" class="icon-lg me-2" /><div><h5 class="alert-heading mb-1">%s</h5><p class="mb-0">%s</p></div></div>',
                    trans('plugins/e-wallet::withdrawal.settings.title'),
                    trans('plugins/e-wallet::withdrawal.settings.description')
                ),
            ])

            ->add(
                'e_wallet_enable_withdrawal',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::withdrawal.settings.enable_withdrawal'))
                    ->helperText(trans('plugins/e-wallet::withdrawal.settings.enable_withdrawal_help'))
                    ->value(get_wallet_setting('enable_withdrawal', true))
                    ->attributes([
                        'data-bb-toggle' => 'collapse',
                        'data-bb-target' => '.withdrawal-settings',
                    ])
            )

            ->add('open_withdrawal_settings', HtmlField::class, [
                'html' => sprintf(
                    '<div class="withdrawal-settings" style="display: %s;" data-bb-value="1">',
                    get_wallet_setting('enable_withdrawal', true) ? 'block' : 'none'
                ),
            ])

            ->add(
                'e_wallet_min_withdrawal',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::withdrawal.settings.min_withdrawal'))
                    ->helperText(trans('plugins/e-wallet::withdrawal.settings.min_withdrawal_help'))
                    ->value(get_wallet_setting('min_withdrawal', 10))
                    ->attributes([
                        'min' => 0.01,
                        'step' => '0.01',
                    ])
            )

            ->add(
                'e_wallet_max_withdrawal',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::withdrawal.settings.max_withdrawal'))
                    ->helperText(trans('plugins/e-wallet::withdrawal.settings.max_withdrawal_help'))
                    ->value(get_wallet_setting('max_withdrawal', 100000000))
                    ->attributes([
                        'min' => 0.01,
                        'step' => '0.01',
                    ])
            )

            ->add('payout_methods_divider', HtmlField::class, [
                'html' => '<hr class="my-3">',
            ])

            ->add('payout_methods_label', HtmlField::class, [
                'html' => sprintf(
                    '<label class="form-label fw-bold mb-2">%s</label><p class="text-muted small mb-3">%s</p>',
                    trans('plugins/e-wallet::withdrawal.settings.payout_methods'),
                    trans('plugins/e-wallet::withdrawal.settings.payout_methods_help')
                ),
            ])

            ->add(
                'e_wallet_payout_methods[]',
                MultiCheckListField::class,
                MultiChecklistFieldOption::make()
                    ->label(false)
                    ->choices($this->getPayoutMethodOptions())
                    ->selected($this->getSelectedPayoutMethods())
            )

            ->add('close_withdrawal_settings', HtmlField::class, [
                'html' => '</div>',
            ])

            ->add('topup_section_divider', HtmlField::class, [
                'html' => '<hr class="my-4">',
            ])

            ->add('topup_section_heading', HtmlField::class, [
                'html' => sprintf(
                    '<div class="alert alert-success d-flex align-items-center mb-4"><x-core::icon name="ti ti-credit-card" class="icon-lg me-2" /><div><h5 class="alert-heading mb-1">%s</h5><p class="mb-0">%s</p></div></div>',
                    trans('plugins/e-wallet::e-wallet.settings.topup_section'),
                    trans('plugins/e-wallet::e-wallet.settings.topup_section_description')
                ),
            ])

            ->add(
                'e_wallet_enable_top_up',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.settings.enable_top_up'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.settings.enable_top_up_help'))
                    ->value(get_wallet_setting('enable_top_up', true))
                    ->attributes([
                        'data-bb-toggle' => 'collapse',
                        'data-bb-target' => '.topup-settings',
                    ])
            )

            ->add('open_topup_settings', HtmlField::class, [
                'html' => sprintf(
                    '<div class="topup-settings" style="display: %s;" data-bb-value="1">',
                    get_wallet_setting('enable_top_up', true) ? 'block' : 'none'
                ),
            ])

            ->add(
                'e_wallet_min_top_up',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.settings.min_top_up'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.settings.min_top_up_help', [
                        'currency' => $currency->symbol,
                    ]))
                    ->value(get_wallet_setting('min_top_up', 1) / 100)
                    ->attributes([
                        'min' => 0.01,
                        'step' => '0.01',
                    ])
            )

            ->add(
                'e_wallet_max_top_up',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.settings.max_top_up'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.settings.max_top_up_help', [
                        'currency' => $currency->symbol,
                    ]))
                    ->value(get_wallet_setting('max_top_up', 5) / 100)
                    ->attributes([
                        'min' => 0.01,
                        'step' => '0.01',
                    ])
            )

            ->add(
                'e_wallet_topup_code_prefix',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.settings.topup_code_prefix'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.settings.topup_code_prefix_help'))
                    ->value(get_wallet_setting('topup_code_prefix', 'TU-'))
                    ->attributes(['maxlength' => 5, 'style' => 'max-width: 120px; text-transform: uppercase;'])
            )

            ->add('topup_payment_methods_divider', HtmlField::class, [
                'html' => '<hr class="my-3">',
            ])

            ->add('topup_payment_methods_label', HtmlField::class, [
                'html' => sprintf(
                    '<label class="form-label fw-bold mb-2">%s</label><p class="text-muted small mb-3">%s</p>',
                    trans('plugins/e-wallet::e-wallet.settings.topup_payment_methods'),
                    trans('plugins/e-wallet::e-wallet.settings.topup_payment_methods_help')
                ),
            ])

            ->add(
                'e_wallet_topup_payment_methods[]',
                MultiCheckListField::class,
                MultiChecklistFieldOption::make()
                    ->label(false)
                    ->choices($this->getTopUpPaymentMethodOptions())
                    ->selected($this->getSelectedTopUpPaymentMethods())
            )

            ->add('close_topup_settings', HtmlField::class, [
                'html' => '</div>',
            ])

            ->add('gift_card_section_divider', HtmlField::class, [
                'html' => '<hr class="my-4">',
            ])

            ->add('gift_card_section_heading', HtmlField::class, [
                'html' => sprintf(
                    '<div class="alert alert-purple d-flex align-items-center mb-4"><x-core::icon name="ti ti-gift" class="icon-lg me-2" /><div><h5 class="alert-heading mb-1">%s</h5><p class="mb-0">%s</p></div></div>',
                    trans('plugins/e-wallet::gift-card.settings.title'),
                    trans('plugins/e-wallet::gift-card.settings.description')
                ),
            ])

            ->add(
                'e_wallet_gift_cards_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.enable'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.enable_help'))
                    ->value($giftCardsEnabled = get_wallet_setting('gift_cards_enabled', true))
                    ->attributes([
                        'data-bb-toggle' => 'collapse',
                        'data-bb-target' => '.gift-card-settings',
                    ])
            )

            ->add('open_gift_card_settings', HtmlField::class, [
                'html' => sprintf(
                    '<div class="gift-card-settings" style="display: %s;" data-bb-value="1">',
                    $giftCardsEnabled ? 'block' : 'none'
                ),
            ])

            ->add(
                'e_wallet_gift_card_code_prefix',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.code_prefix'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.code_prefix_help'))
                    ->value(get_wallet_setting('gift_card_code_prefix', 'GC'))
                    ->attributes(['maxlength' => 4, 'style' => 'max-width: 100px; text-transform: uppercase;'])
            )

            ->add(
                'e_wallet_gift_card_default_expiry_days',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.default_expiry_days'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.default_expiry_days_help'))
                    ->value(get_wallet_setting('gift_card_default_expiry_days'))
                    ->attributes(['min' => 0, 'step' => 1, 'placeholder' => trans('plugins/e-wallet::gift-card.settings.no_expiry')])
            )

            ->add('gift_card_value_divider', HtmlField::class, [
                'html' => '<hr class="my-3">',
            ])

            ->add('gift_card_value_label', HtmlField::class, [
                'html' => sprintf(
                    '<label class="form-label fw-bold mb-2">%s</label>',
                    trans('plugins/e-wallet::gift-card.settings.value_limits')
                ),
            ])

            ->add(
                'e_wallet_gift_card_min_value',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.min_value'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.min_value_help', [
                        'currency' => $currency->symbol,
                    ]))
                    ->value(get_wallet_setting('gift_card_min_value', 1))
                    ->attributes(['min' => 0.01, 'step' => '0.01'])
            )

            ->add(
                'e_wallet_gift_card_max_value',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.max_value'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.max_value_help', [
                        'currency' => $currency->symbol,
                    ]))
                    ->value(get_wallet_setting('gift_card_max_value', 500000))
                    ->attributes(['min' => 0.01, 'step' => '0.01'])
            )

            ->add('gift_card_public_divider', HtmlField::class, [
                'html' => '<hr class="my-3">',
            ])

            ->add(
                'e_wallet_gift_card_public_balance_check',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.public_balance_check'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.public_balance_check_help'))
                    ->value(get_wallet_setting('gift_card_public_balance_check', true))
            )

            ->add('gift_card_checkout_divider', HtmlField::class, [
                'html' => '<hr class="my-3">',
            ])

            ->add('gift_card_checkout_label', HtmlField::class, [
                'html' => sprintf(
                    '<label class="form-label fw-bold mb-2">%s</label>',
                    trans('plugins/e-wallet::gift-card.settings.checkout_options')
                ),
            ])

            ->add(
                'e_wallet_gift_card_allow_partial_use',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.allow_partial_use'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.allow_partial_use_help'))
                    ->value(get_wallet_setting('gift_card_allow_partial_use', true))
            )

            ->add(
                'e_wallet_unified_discount_field',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.settings.unified_discount_field'))
                    ->helperText(trans('plugins/e-wallet::gift-card.settings.unified_discount_field_help'))
                    ->value(get_wallet_setting('unified_discount_field', true))
            )

            ->add('close_gift_card_settings', HtmlField::class, [
                'html' => '</div>',
            ])

            ->add('payment_section_divider', HtmlField::class, [
                'html' => '<hr class="my-4">',
            ])

            ->add('payment_section_heading', HtmlField::class, [
                'html' => sprintf(
                    '<div class="alert alert-primary d-flex align-items-center mb-4"><x-core::icon name="ti ti-credit-card" class="icon-lg me-2" /><div><h5 class="alert-heading mb-1">%s</h5><p class="mb-0">%s</p></div></div>',
                    trans('plugins/e-wallet::e-wallet.settings.payment_section'),
                    trans('plugins/e-wallet::e-wallet.settings.payment_section_description')
                ),
            ])

            ->add(
                'wallet_payment_info',
                AlertField::class,
                AlertFieldOption::make()
                    ->type(get_payment_setting('status', E_WALLET_PAYMENT_METHOD_NAME) == 1 ? 'success' : 'warning')
                    ->content(
                        get_payment_setting('status', E_WALLET_PAYMENT_METHOD_NAME) == 1
                            ? trans('plugins/e-wallet::e-wallet.settings.wallet_payment_enabled_info', [
                                'link' => route('payments.methods'),
                            ])
                            : trans('plugins/e-wallet::e-wallet.settings.wallet_payment_disabled_info', [
                                'link' => route('payments.methods'),
                            ])
                    )
            )

            ->add('webhook_section_divider', HtmlField::class, [
                'html' => '<hr class="my-4">',
            ])

            ->add('webhook_section_heading', HtmlField::class, [
                'html' => sprintf(
                    '<div class="alert alert-secondary d-flex align-items-center mb-4"><x-core::icon name="ti ti-webhook" class="icon-lg me-2" /><div><h5 class="alert-heading mb-1">%s</h5><p class="mb-0">%s</p></div></div>',
                    trans('plugins/e-wallet::e-wallet.webhook.title'),
                    trans('plugins/e-wallet::e-wallet.webhook.description')
                ),
            ])

            ->add(
                'e_wallet_enable_webhooks',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.webhook.enable_webhooks'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.webhook.enable_webhooks_help'))
                    ->value($webhooksEnabled = get_wallet_setting('enable_webhooks', false))
            )
            ->addOpenCollapsible('e_wallet_enable_webhooks', '1', $webhooksEnabled == '1')

            ->add(
                'e_wallet_topup_created_webhook_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.webhook.topup_created_url'))
                    ->value(get_wallet_setting('topup_created_webhook_url'))
                    ->placeholder('https://your-webhook-endpoint.com/topup-created')
                    ->helperText(trans('plugins/e-wallet::e-wallet.webhook.topup_created_url_help'))
            )
            ->add('topup_created_sample_data', HtmlField::class, [
                'html' => $this->getWebhookSampleDataHtml('topup_created', $this->getTopUpCreatedSampleData()),
            ])

            ->add(
                'e_wallet_topup_completed_webhook_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.webhook.topup_completed_url'))
                    ->value(get_wallet_setting('topup_completed_webhook_url'))
                    ->placeholder('https://your-webhook-endpoint.com/topup-completed')
                    ->helperText(trans('plugins/e-wallet::e-wallet.webhook.topup_completed_url_help'))
            )
            ->add('topup_completed_sample_data', HtmlField::class, [
                'html' => $this->getWebhookSampleDataHtml('topup_completed', $this->getTopUpCompletedSampleData()),
            ])

            ->add(
                'e_wallet_topup_failed_webhook_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.webhook.topup_failed_url'))
                    ->value(get_wallet_setting('topup_failed_webhook_url'))
                    ->placeholder('https://your-webhook-endpoint.com/topup-failed')
                    ->helperText(trans('plugins/e-wallet::e-wallet.webhook.topup_failed_url_help'))
            )
            ->add('topup_failed_sample_data', HtmlField::class, [
                'html' => $this->getWebhookSampleDataHtml('topup_failed', $this->getTopUpFailedSampleData()),
            ])

            ->add(
                'e_wallet_topup_cancelled_webhook_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.webhook.topup_cancelled_url'))
                    ->value(get_wallet_setting('topup_cancelled_webhook_url'))
                    ->placeholder('https://your-webhook-endpoint.com/topup-cancelled')
                    ->helperText(trans('plugins/e-wallet::e-wallet.webhook.topup_cancelled_url_help'))
            )
            ->add('topup_cancelled_sample_data', HtmlField::class, [
                'html' => $this->getWebhookSampleDataHtml('topup_cancelled', $this->getTopUpCancelledSampleData()),
            ])

            ->add('webhook_test_script', HtmlField::class, [
                'html' => $this->getWebhookTestScript(),
            ])
            ->addCloseCollapsible('e_wallet_enable_webhooks', '1')

            ->add('close_fieldset_wallet_settings', HtmlField::class, [
                'html' => '</fieldset>',
            ]);
    }

    protected function getWebhookSampleDataHtml(string $webhookType, array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $title = trans('plugins/e-wallet::e-wallet.webhook.view_sample_data');
        $testUrl = route('e-wallet.settings.webhook.test');
        $buttonText = trans('plugins/e-wallet::e-wallet.webhook.test_button');
        $icon = BaseHelper::renderIcon('ti ti-send-2');

        return <<<HTML
            <div class="mb-3">
                <details class="border rounded p-3">
                    <summary class="cursor-pointer text-primary fw-bold">{$title}</summary>
                    <pre class="mt-3 p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>{$json}</code></pre>
                </details>
            </div>
            <div class="mb-3">
                <button type="button"
                        class="btn btn-sm btn-primary test-webhook-btn"
                        data-webhook-type="{$webhookType}"
                        data-url-field="e_wallet_{$webhookType}_webhook_url"
                        data-test-url="{$testUrl}">
                    {$icon} {$buttonText}
                </button>
                <div class="webhook-test-result mt-2" id="test-result-{$webhookType}" style="display: none;"></div>
            </div>
        HTML;
    }

    protected function getTopUpCreatedSampleData(): array
    {
        return [
            'event' => 'topup.created',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'topup_id' => 123,
                'topup_code' => 'TU-ABC12345',
                'customer_id' => 1,
                'customer_email' => 'customer@example.com',
                'customer_name' => 'John Doe',
                'amount' => 10000,
                'currency_code' => 'USD',
                'converted_amount' => 10000,
                'wallet_currency_code' => 'USD',
                'exchange_rate' => 1.0,
                'status' => 'pending',
                'payment_method' => null,
                'payment_id' => null,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function getTopUpCompletedSampleData(): array
    {
        return [
            'event' => 'topup.completed',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'topup_id' => 123,
                'topup_code' => 'TU-ABC12345',
                'customer_id' => 1,
                'customer_email' => 'customer@example.com',
                'customer_name' => 'John Doe',
                'amount' => 10000,
                'currency_code' => 'USD',
                'converted_amount' => 10000,
                'wallet_currency_code' => 'USD',
                'exchange_rate' => 1.0,
                'status' => 'completed',
                'payment_method' => 'stripe',
                'payment_id' => 'pi_abc123xyz',
                'created_at' => now()->subMinutes(5)->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function getTopUpFailedSampleData(): array
    {
        return [
            'event' => 'topup.failed',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'topup_id' => 123,
                'topup_code' => 'TU-ABC12345',
                'customer_id' => 1,
                'customer_email' => 'customer@example.com',
                'customer_name' => 'John Doe',
                'amount' => 10000,
                'currency_code' => 'USD',
                'converted_amount' => 10000,
                'wallet_currency_code' => 'USD',
                'exchange_rate' => 1.0,
                'status' => 'failed',
                'payment_method' => 'stripe',
                'payment_id' => null,
                'failure_reason' => 'Payment declined by card issuer',
                'created_at' => now()->subMinutes(5)->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function getTopUpCancelledSampleData(): array
    {
        return [
            'event' => 'topup.cancelled',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'topup_id' => 123,
                'topup_code' => 'TU-ABC12345',
                'customer_id' => 1,
                'customer_email' => 'customer@example.com',
                'customer_name' => 'John Doe',
                'amount' => 10000,
                'currency_code' => 'USD',
                'converted_amount' => 10000,
                'wallet_currency_code' => 'USD',
                'exchange_rate' => 1.0,
                'status' => 'cancelled',
                'payment_method' => null,
                'payment_id' => null,
                'created_at' => now()->subMinutes(10)->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function getWebhookTestScript(): string
    {
        $pleaseEnterUrlText = trans('plugins/e-wallet::e-wallet.webhook.please_enter_url');
        $testingText = trans('plugins/e-wallet::e-wallet.webhook.testing');
        $testFailedText = trans('plugins/e-wallet::e-wallet.webhook.test_failed');
        $testSuccessText = trans('plugins/e-wallet::e-wallet.webhook.test_success');
        $statusCodeText = trans('plugins/e-wallet::e-wallet.webhook.status_code');
        $errorOccurredText = trans('plugins/e-wallet::e-wallet.webhook.error_occurred');

        $loaderIcon = '<span style="display: inline-block; animation: spin 1s linear infinite;">' . BaseHelper::renderIcon('ti ti-loader-2', attributes: ['class' => 'm-0']) . '</span>';
        $loaderIcon = json_encode($loaderIcon);

        return <<<HTML
            <style>
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            </style>
            <script>
                $(document).ready(function() {
                    const loaderIcon = {$loaderIcon};

                    $('.test-webhook-btn').on('click', function() {
                        const button = $(this);
                        const webhookType = button.data('webhook-type');
                        const urlFieldId = button.data('url-field');
                        const testUrl = button.data('test-url');
                        const webhookUrl = $('#' + urlFieldId).val();
                        const resultDiv = $('#test-result-' + webhookType);
                        const originalButtonHtml = button.html();

                        if (!webhookUrl) {
                            Botble.showError('{$pleaseEnterUrlText}');
                            return;
                        }

                        button.prop('disabled', true);
                        button.html(loaderIcon + ' {$testingText}');
                        resultDiv.hide();

                        $.ajax({
                            url: testUrl,
                            type: 'POST',
                            data: {
                                webhook_url: webhookUrl,
                                webhook_type: webhookType,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.error) {
                                    resultDiv.html('<div class="alert alert-danger">' +
                                        '<p><strong>{$testFailedText}</strong> <br>' + response.message +
                                        (response.data ? '<br>{$statusCodeText}: ' + response.data.status_code : '') +
                                        '</p> </div>');
                                } else {
                                    resultDiv.html('<div class="alert alert-success">' +
                                        '<strong>{$testSuccessText}</strong> ' + response.message +
                                        '<br>{$statusCodeText}: ' + response.data.status_code +
                                        '</div>');
                                }
                                resultDiv.slideDown();
                            },
                            error: function(xhr) {
                                let errorMessage = '{$errorOccurredText}';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                resultDiv.html('<div class="alert alert-danger">' +
                                    '<strong>{$testFailedText}</strong> ' + errorMessage +
                                    '</div>');
                                resultDiv.slideDown();
                            },
                            complete: function() {
                                button.prop('disabled', false);
                                button.html(originalButtonHtml);
                            }
                        });
                    });
                });
            </script>
        HTML;
    }

    protected function getTopUpPaymentMethodOptions(): array
    {
        $options = [];

        foreach (PaymentMethodEnum::toArray() as $value) {
            if ($value === E_WALLET_PAYMENT_METHOD_NAME) {
                continue;
            }

            if (! get_payment_setting('status', $value)) {
                continue;
            }

            $label = get_payment_setting('name', $value) ?: PaymentMethodEnum::getLabel($value);
            $options[$value] = $label;
        }

        return $options;
    }

    protected function getSelectedTopUpPaymentMethods(): array
    {
        $saved = get_wallet_setting('topup_payment_methods');

        if (empty($saved)) {
            return array_keys($this->getTopUpPaymentMethodOptions());
        }

        if (is_string($saved)) {
            return json_decode($saved, true) ?: [];
        }

        return (array) $saved;
    }

    protected function getPayoutMethodOptions(): array
    {
        $options = [];

        foreach (PayoutPaymentMethodsEnum::toArray() as $value) {
            $options[$value] = PayoutPaymentMethodsEnum::getLabel($value);
        }

        return $options;
    }

    protected function getSelectedPayoutMethods(): array
    {
        $saved = get_wallet_setting('payout_methods');

        if (empty($saved)) {
            return array_keys($this->getPayoutMethodOptions());
        }

        if (is_string($saved)) {
            return json_decode($saved, true) ?: [];
        }

        return (array) $saved;
    }
}
