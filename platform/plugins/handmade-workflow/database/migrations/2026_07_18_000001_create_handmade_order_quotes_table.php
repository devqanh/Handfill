<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('handmade_order_quotes')) {
            Schema::create('handmade_order_quotes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->unique();

                $table->decimal('product_cost', 15, 2)->default(0);
                $table->decimal('shipping_cost', 15, 2)->default(0);
                $table->decimal('fulfill_fee', 15, 2)->default(0);
                $table->decimal('packing_fee', 15, 2)->default(0);

                $table->date('expected_delivery_date')->nullable();
                $table->text('note')->nullable();

                $table->foreignId('quoted_by')->nullable();
                $table->timestamp('quoted_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('deposit_paid_at')->nullable();
                $table->timestamp('final_paid_at')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('handmade_order_quotes');
    }
};
