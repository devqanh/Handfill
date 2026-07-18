<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('handmade_order_quotes', 'deposit_percent')) {
            Schema::table('handmade_order_quotes', function (Blueprint $table): void {
                $table->unsignedTinyInteger('deposit_percent')->default(50)->after('packing_fee');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('handmade_order_quotes', 'deposit_percent')) {
            Schema::table('handmade_order_quotes', function (Blueprint $table): void {
                $table->dropColumn('deposit_percent');
            });
        }
    }
};
