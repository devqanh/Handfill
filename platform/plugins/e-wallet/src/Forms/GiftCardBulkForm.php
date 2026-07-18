<?php

namespace Botble\EWallet\Forms;

use Botble\Base\Forms\FieldOptions\DatePickerFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\Fields\DatePickerField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\FormAbstract;
use Botble\EWallet\Http\Requests\GiftCardBulkRequest;
use Botble\EWallet\Models\GiftCard;

class GiftCardBulkForm extends FormAbstract
{
    public function setup(): void
    {
        $currency = cms_currency()->getApplicationCurrency()->title ?? 'USD';

        $this
            ->model(GiftCard::class)
            ->setValidatorClass(GiftCardBulkRequest::class)
            ->setFormOptions([
                'url' => route('e-wallet.gift-cards.bulk.store'),
            ])
            ->add(
                'quantity',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.bulk.quantity'))
                    ->helperText(trans('plugins/e-wallet::gift-card.bulk.quantity_help'))
                    ->required()
                    ->attributes(['min' => 1, 'max' => 100, 'step' => '1'])
                    ->defaultValue(10)
            )
            ->add(
                'value',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.bulk.value'))
                    ->helperText(trans('plugins/e-wallet::gift-card.bulk.value_help', ['currency' => $currency]))
                    ->required()
                    ->attributes(['min' => 0.01, 'step' => '0.01'])
            )
            ->add(
                'expires_at',
                DatePickerField::class,
                DatePickerFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.bulk.expires_at'))
                    ->helperText(trans('plugins/e-wallet::gift-card.bulk.expires_at_help'))
            )
            ->add(
                'note',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.form.note'))
                    ->helperText(trans('plugins/e-wallet::gift-card.form.note_help'))
                    ->rows(3)
            );
    }
}
