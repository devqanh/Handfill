<?php

namespace Botble\EWallet\Forms;

use Botble\Base\Forms\FieldOptions\HiddenFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\Fields\HiddenField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\FormAbstract;
use Botble\EWallet\Http\Requests\WalletAdjustmentRequest;
use Botble\EWallet\Models\Wallet;

class WalletAdjustmentForm extends FormAbstract
{
    public function setup(): void
    {
        $wallet = $this->getModel();

        $this
            ->model(Wallet::class)
            ->setValidatorClass(WalletAdjustmentRequest::class)
            ->setFormOptions([
                'url' => route('e-wallet.wallets.adjust.store'),
            ])
            ->add(
                'wallet_id',
                HiddenField::class,
                HiddenFieldOption::make()->value($wallet?->id)
            )
            ->add(
                'adjustment_type',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.adjustment.type'))
                    ->choices([
                        'credit' => trans('plugins/e-wallet::e-wallet.adjustment.credit'),
                        'debit' => trans('plugins/e-wallet::e-wallet.adjustment.debit'),
                    ])
                    ->required()
            )
            ->add(
                'amount',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.adjustment.amount'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.adjustment.amount_help'))
                    ->required()
                    ->attributes(['min' => 0.01, 'step' => '0.01'])
            )
            ->add(
                'reason',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(trans('plugins/e-wallet::e-wallet.adjustment.reason'))
                    ->helperText(trans('plugins/e-wallet::e-wallet.adjustment.reason_help'))
                    ->required()
                    ->rows(3)
            );
    }
}
