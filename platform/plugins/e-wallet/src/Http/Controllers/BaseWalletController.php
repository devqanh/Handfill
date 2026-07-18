<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\EWallet\Plugin;

abstract class BaseWalletController extends BaseController
{
    public function __construct()
    {
        $version = Plugin::ASSETS_VERSION;

        if (BaseHelper::adminLanguageDirection() === 'rtl') {
            Assets::addStylesDirectly("vendor/core/plugins/e-wallet/css/wallet-admin-rtl.css?v={$version}");
        } else {
            Assets::addStylesDirectly("vendor/core/plugins/e-wallet/css/wallet-admin.css?v={$version}");
        }
    }

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.name'), route('e-wallet.index'));
    }
}
