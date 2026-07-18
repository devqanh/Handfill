<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Made-to-order lines have no catalogue product, so their order items carry a null
 * product_id. Invoice generation copies that into ec_invoice_items.reference_id,
 * which was NOT NULL — so generating a PDF for a custom order failed outright.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ec_invoice_items')) {
            return;
        }

        // Raw statement: doctrine/dbal is not required for a plain nullable change here.
        DB::statement('ALTER TABLE `ec_invoice_items` MODIFY `reference_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ec_invoice_items')) {
            return;
        }

        DB::statement('ALTER TABLE `ec_invoice_items` MODIFY `reference_id` BIGINT UNSIGNED NOT NULL');
    }
};
