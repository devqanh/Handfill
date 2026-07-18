<?php

namespace Botble\HandmadeWorkflow\Http\Controllers\Settings;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\HandmadeWorkflow\Forms\Settings\HandmadeSettingForm;
use Botble\HandmadeWorkflow\Http\Requests\Settings\HandmadeSettingRequest;
use Botble\Setting\Http\Controllers\SettingController;

class HandmadeSettingController extends SettingController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('core/base::base.panel.others'));
    }

    public function edit()
    {
        $this->pageTitle(trans('plugins/handmade-workflow::handmade-workflow.settings.name'));

        return HandmadeSettingForm::create()->renderForm();
    }

    public function update(HandmadeSettingRequest $request): BaseHttpResponse
    {
        return $this->performUpdate($request->validated());
    }
}
