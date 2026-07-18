<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ec_wallet_transactions', 'currency_code')) {
            return;
        }

        Schema::table('ec_wallet_transactions', function (Blueprint $table) {
            $table->string('currency_code', 10)->nullable()->after('wallet_id');
        });

        // Populate currency_code from wallet for existing transactions
        DB::statement('
            UPDATE ec_wallet_transactions t
            INNER JOIN ec_wallets w ON t.wallet_id = w.id
            SET t.currency_code = w.currency_code
            WHERE t.currency_code IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('ec_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('currency_code');
        });
    }
};
