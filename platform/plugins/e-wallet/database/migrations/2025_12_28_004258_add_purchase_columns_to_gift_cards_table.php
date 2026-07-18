<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ec_gift_cards') || Schema::hasColumn('ec_gift_cards', 'purchased_by_customer_id')) {
            return;
        }

        Schema::table('ec_gift_cards', function (Blueprint $table): void {
            $table->foreignId('purchased_by_customer_id')->nullable()->after('customer_id');
            $table->string('recipient_email')->nullable()->after('purchased_by_customer_id');
            $table->string('recipient_name', 191)->nullable()->after('recipient_email');
            $table->text('gift_message')->nullable()->after('recipient_name');
            $table->foreignId('purchase_order_id')->nullable()->after('gift_message');

            $table->index('purchased_by_customer_id');
            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('ec_gift_cards', function (Blueprint $table): void {
            $table->dropIndex(['purchased_by_customer_id']);
            $table->dropIndex(['purchase_order_id']);

            $table->dropColumn([
                'purchased_by_customer_id',
                'recipient_email',
                'recipient_name',
                'gift_message',
                'purchase_order_id',
            ]);
        });
    }
};
