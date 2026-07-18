<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ec_wallet_topups')) {
            return;
        }

        Schema::create('ec_wallet_topups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('wallet_id');
            $table->string('code')->unique();
            $table->bigInteger('amount');
            $table->string('currency_code', 3);
            $table->bigInteger('converted_amount');
            $table->string('wallet_currency_code', 3);
            $table->decimal('exchange_rate', 15, 8)->default(1);
            $table->string('status', 20)->default('pending');
            $table->string('payment_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('wallet_id');
            $table->index('status');
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_wallet_topups');
    }
};
