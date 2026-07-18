<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('ec_orders', 'production_status')) {
            Schema::table('ec_orders', function (Blueprint $table): void {
                $table->string('production_status', 60)->nullable()->index()->after('status');
                $table->timestamp('production_status_updated_at')->nullable()->after('production_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ec_orders', 'production_status')) {
            Schema::table('ec_orders', function (Blueprint $table): void {
                $table->dropColumn(['production_status', 'production_status_updated_at']);
            });
        }
    }
};
