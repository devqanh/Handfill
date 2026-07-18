<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ec_wallet_withdrawals')) {
            return;
        }

        Schema::create('ec_wallet_withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->index();
            $table->foreignId('customer_id')->index();
            $table->bigInteger('amount');
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 60)->default('pending');
            $table->string('payment_channel', 120)->nullable();
            $table->text('payment_details')->nullable();
            $table->text('bank_info')->nullable();
            $table->text('notes')->nullable();
            $table->string('transaction_id', 120)->nullable();
            $table->foreignId('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_wallet_withdrawals');
    }
};
