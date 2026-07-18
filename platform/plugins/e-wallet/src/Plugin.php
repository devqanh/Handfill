<?php

namespace Botble\EWallet;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Illuminate\Support\Facades\Schema;

class Plugin extends PluginOperationAbstract
{
    public const ASSETS_VERSION = '1.0.5';

    public static function remove(): void
    {
        Schema::dropIfExists('ec_wallet_topups');
        Schema::dropIfExists('ec_wallet_transactions');
        Schema::dropIfExists('ec_wallets');
    }
}
