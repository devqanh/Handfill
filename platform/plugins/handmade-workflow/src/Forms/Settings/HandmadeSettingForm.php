<?php

namespace Botble\HandmadeWorkflow\Forms\Settings;

use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\HandmadeWorkflow\Http\Requests\Settings\HandmadeSettingRequest;
use Botble\Setting\Forms\SettingForm;

class HandmadeSettingForm extends SettingForm
{
    public function buildForm(): void
    {
        parent::buildForm();

        $this
            ->setSectionTitle(trans('plugins/handmade-workflow::handmade-workflow.settings.name'))
            ->setSectionDescription(trans('plugins/handmade-workflow::handmade-workflow.settings.description'))
            ->setValidatorClass(HandmadeSettingRequest::class)
            ->add(
                'handmade_default_fulfill_fee',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/handmade-workflow::handmade-workflow.settings.default_fulfill_fee'))
                    ->value(setting('handmade_default_fulfill_fee', 0))
                    ->helperText(trans('plugins/handmade-workflow::handmade-workflow.settings.fees_help'))
            )
            ->add(
                'handmade_default_packing_fee_per_unit',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/handmade-workflow::handmade-workflow.settings.default_packing_fee_per_unit'))
                    ->value(setting('handmade_default_packing_fee_per_unit', 0))
            );
    }
}
