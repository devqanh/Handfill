<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ec_gift_cards')) {
            return;
        }

        Schema::create('ec_gift_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->bigInteger('initial_value');
            $table->bigInteger('balance')->default(0);
            $table->string('currency_code', 10);
            $table->string('status', 20)->default('active');
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('issued_by')->nullable();
            $table->foreignId('redeemed_by_customer_id')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
            $table->index('redeemed_by_customer_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_gift_cards');
    }
};
