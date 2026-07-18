<?php

namespace Botble\EWallet\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Theme\Facades\Theme;

abstract class BaseFrontController extends BaseController
{
    public function __construct()
    {
        $version = EcommerceHelper::getAssetVersion();

        Theme::asset()
            ->add('customer-style', 'vendor/core/plugins/ecommerce/css/customer.css', ['bootstrap-css'], version: $version);
    }
}
