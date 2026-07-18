<?php

namespace Botble\EWallet\Forms;

use Botble\Payment\Forms\PaymentMethodForm;

class WalletPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(E_WALLET_PAYMENT_METHOD_NAME)
            ->paymentName(trans('plugins/e-wallet::e-wallet.name'))
            ->paymentDescription(trans('plugins/e-wallet::e-wallet.checkout.pay_with_wallet'))
            ->paymentLogo(url('vendor/core/plugins/e-wallet/images/wallet.svg'))
            ->paymentInstructions(trans('plugins/e-wallet::e-wallet.settings.payment_instructions'))
            ->defaultDescriptionValue('Pay instantly from your wallet balance. No external payment processing required.');
    }
}
