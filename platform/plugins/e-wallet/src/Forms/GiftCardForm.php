<?php

namespace Botble\EWallet\Forms;

use Botble\Base\Forms\FieldOptions\DatePickerFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\DatePickerField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Http\Requests\GiftCardRequest;
use Botble\EWallet\Models\GiftCard;

class GiftCardForm extends FormAbstract
{
    public function setup(): void
    {
        $customers = Customer::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($customer) => [$customer->id => $customer->name . ' (' . $customer->email . ')'])
            ->toArray();

        $currency = cms_currency()->getApplicationCurrency()->title ?? 'USD';
        $isEditing = $this->getModel() && $this->getModel()->id;

        $this
            ->model(GiftCard::class)
            ->setValidatorClass(GiftCardRequest::class)
            ->setFormOptions([
                'url' => $isEditing
                    ? route('e-wallet.gift-cards.update', $this->getModel()->id)
                    : route('e-wallet.gift-cards.store'),
                'method' => $isEditing ? 'PUT' : 'POST',
            ]);

        if (! $isEditing) {
            $this
                ->add(
                    'value',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(trans('plugins/e-wallet::gift-card.form.initial_value'))
                        ->helperText(trans('plugins/e-wallet::gift-card.form.initial_value_help', ['currency' => $currency]))
                        ->required()
                        ->attributes(['min' => 0.01, 'step' => '0.01'])
                )
                ->add(
                    'custom_code',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(trans('plugins/e-wallet::gift-card.form.code'))
                        ->placeholder(trans('plugins/e-wallet::gift-card.form.code_placeholder'))
                        ->helperText(trans('plugins/e-wallet::gift-card.form.code_help'))
                        ->maxLength(50)
                );
        }

        $this->add(
            'customer_id',
            'customSelect',
            [
                    'label' => trans('plugins/e-wallet::gift-card.form.customer'),
                    'choices' => ['' => trans('core/base::forms.select_placeholder')] + $customers,
                    'attr' => [
                        'class' => 'form-control select-search-full',
                    ],
                    'help_block' => [
                        'text' => trans('plugins/e-wallet::gift-card.form.customer_help'),
                    ],
                ]
        )
            ->add(
                'expires_at',
                DatePickerField::class,
                DatePickerFieldOption::make()
                    ->label(trans('plugins/e-wallet::gift-card.form.expires_at'))
                    ->helperText(trans('plugins/e-wallet::gift-card.form.expires_at_help'))
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
