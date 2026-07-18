<?php

namespace Botble\EWallet\Forms;

use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\FormAbstract;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Http\Requests\WalletRequest;
use Botble\EWallet\Models\Wallet;

class WalletForm extends FormAbstract
{
    public function setup(): void
    {
        $existingWalletCustomerIds = Wallet::query()->pluck('customer_id')->toArray();

        $customers = Customer::query()
            ->select(['id', 'name', 'email'])
            ->whereNotIn('id', $existingWalletCustomerIds)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($customer) => [$customer->id => $customer->name . ' (' . $customer->email . ')'])
            ->toArray();

        $currency = cms_currency()->getApplicationCurrency()->title ?? 'USD';

        $this
            ->model(Wallet::class)
            ->setValidatorClass(WalletRequest::class)
            ->setFormOptions([
                'url' => route('e-wallet.wallets.store'),
                'method' => 'POST',
            ])
            ->add(
                'customer_id',
                'customSelect',
                [
                    'label' => trans('plugins/e-wallet::e-wallet.wallet.customer'),
                    'choices' => ['' => trans('core/base::forms.select_placeholder')] + $customers,
                    'attr' => [
                        'class' => 'form-control select-search-full',
                    ],
                    'help_block' => [
                        'text' => trans('plugins/e-wallet::e-wallet.wallet.customer_help'),
                    ],
                    'required' => true,
                ]
            )
            ->add(
                'initial_balance',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.wallet.initial_balance'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.wallet.initial_balance_help', ['currency' => $currency]))
                    ->defaultValue(0)
                    ->attributes(['min' => 0, 'step' => '0.01'])
            )
            ->add(
                'reason',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.wallet.reason'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.wallet.reason_help'))
                    ->rows(3)
            );
    }
}
