<?php

namespace Botble\LarkWebhook\Forms\Settings;

use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\PasswordField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\LarkWebhook\Http\Requests\Settings\LarkWebhookSettingRequest;
use Botble\LarkWebhook\Supports\LarkWebhookSupport;
use Botble\Setting\Forms\SettingForm;

class LarkWebhookSettingForm extends SettingForm
{
    public function buildForm(): void
    {
        parent::buildForm();

        $this
            ->setSectionTitle(trans('plugins/lark-webhook::lark-webhook.settings.name'))
            ->setSectionDescription(trans('plugins/lark-webhook::lark-webhook.settings.description'))
            ->setValidatorClass(LarkWebhookSettingRequest::class)

            // ---- Chiều NHẬN (inbound): Lark gửi sự kiện về đây ----
            ->add(
                'inbound_heading',
                HtmlField::class,
                HtmlFieldOption::make()->content(
                    '<h5 class="mb-1">' . trans('plugins/lark-webhook::lark-webhook.settings.inbound_heading') . '</h5>' .
                    '<p class="text-muted">' . trans('plugins/lark-webhook::lark-webhook.settings.inbound_desc') . '</p>'
                )
            )
            ->add(
                'lark_webhook_enabled',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.enabled'))
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.enabled_helper'))
                    ->value(LarkWebhookSupport::isEnabled())
            )
            ->add(
                'webhook_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.webhook_url'))
                    ->value(LarkWebhookSupport::webhookUrl())
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.webhook_url_helper'))
                    ->attributes(['readonly' => true, 'onclick' => 'this.select()'])
            )
            ->add(
                'lark_webhook_verification_token',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.verification_token'))
                    ->value(setting('lark_webhook_verification_token'))
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.verification_token_helper'))
                    ->placeholder('v_xxxxxxxxxxxxxxxx')
            )
            ->add(
                'lark_webhook_encrypt_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.encrypt_key'))
                    ->value(setting('lark_webhook_encrypt_key'))
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.encrypt_key_helper'))
            )

            // ---- Chiều GỬI (outbound): CMS bắn record lên Lark Base ----
            ->add(
                'outbound_heading',
                HtmlField::class,
                HtmlFieldOption::make()->content(
                    '<hr><h5 class="mb-1">' . trans('plugins/lark-webhook::lark-webhook.settings.outbound_heading') . '</h5>' .
                    '<p class="text-muted">' . trans('plugins/lark-webhook::lark-webhook.settings.outbound_desc') . '</p>'
                )
            )
            ->add(
                'lark_webhook_push_enabled',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.push_enabled'))
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.push_enabled_helper'))
                    ->value((bool) setting('lark_webhook_push_enabled', false))
            )
            ->add(
                'lark_webhook_base_domain',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.base_domain'))
                    ->choices([
                        'https://open.larksuite.com' => 'Lark Suite (open.larksuite.com)',
                        'https://open.feishu.cn' => 'Feishu (open.feishu.cn)',
                    ])
                    ->selected(setting('lark_webhook_base_domain', 'https://open.larksuite.com'))
            )
            ->add(
                'lark_webhook_app_id',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.app_id'))
                    ->value(setting('lark_webhook_app_id'))
                    ->placeholder('cli_xxxxxxxxxxxxxxxx')
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.app_id_helper'))
            )
            ->add(
                'lark_webhook_app_secret',
                PasswordField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.app_secret'))
                    ->value(setting('lark_webhook_app_secret'))
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.app_secret_helper'))
                    ->attributes(['autocomplete' => 'new-password'])
            )
            ->add(
                'lark_webhook_base_app_token',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.base_app_token'))
                    ->value(setting('lark_webhook_base_app_token'))
                    ->placeholder('bascnXXXXXXXXXXXXXXXX')
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.base_app_token_helper'))
            )
            ->add(
                'lark_webhook_base_table_id',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/lark-webhook::lark-webhook.settings.base_table_id'))
                    ->value(setting('lark_webhook_base_table_id'))
                    ->placeholder('tblXXXXXXXXXXXXXX')
                    ->helperText(trans('plugins/lark-webhook::lark-webhook.settings.base_table_id_helper'))
            )
            ->add(
                'test_push',
                HtmlField::class,
                HtmlFieldOption::make()->content(
                    view('plugins/lark-webhook::settings.test-push-button')->render()
                )
            );
    }
}
