<?php

namespace Botble\EWallet\Forms;

use Botble\Base\Forms\FieldOptions\AlertFieldOption;
use Botble\Base\Forms\Fields\AlertField;
use Botble\Payment\Concerns\Forms\HasAvailableCountriesField;
use Botble\Payment\Forms\PaymentMethodForm;

class EWalletPaymentMethodForm extends PaymentMethodForm
{
    use HasAvailableCountriesField;

    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(E_WALLET_PAYMENT_METHOD_NAME)
            ->paymentName(trans('plugins/e-wallet::e-wallet.payment.name'))
            ->paymentDescription(trans('plugins/e-wallet::e-wallet.payment.description'))
            ->paymentLogo(url('vendor/core/plugins/e-wallet/images/wallet.png'))
            ->paymentUrl(route('e-wallet.settings.index'))
            ->paymentInstructions(trans('plugins/e-wallet::e-wallet.payment.instructions'))
            ->add(
                'wallet_info',
                AlertField::class,
                AlertFieldOption::make()
                    ->type('info')
                    ->content(trans('plugins/e-wallet::e-wallet.payment.no_configuration_needed'))
            )
            ->addAvailableCountriesField(E_WALLET_PAYMENT_METHOD_NAME);
    }
}
