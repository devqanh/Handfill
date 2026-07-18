<?php

namespace Botble\HandmadeWorkflow;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Illuminate\Support\Facades\Schema;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        if (Schema::hasColumn('ec_orders', 'production_status')) {
            Schema::table('ec_orders', function ($table): void {
                $table->dropColumn(['production_status', 'production_status_updated_at']);
            });
        }
    }
}
