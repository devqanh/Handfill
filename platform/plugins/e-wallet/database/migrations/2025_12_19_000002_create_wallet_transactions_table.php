<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ec_wallet_transactions')) {
            return;
        }

        Schema::create('ec_wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id');
            $table->foreignId('customer_id');
            $table->string('type', 50);
            $table->string('status', 20)->default('completed');
            $table->bigInteger('amount');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('reference_type')->nullable();
            $table->foreignId('reference_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->foreignId('created_by')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('customer_id');
            $table->index('type');
            $table->index('status');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_wallet_transactions');
    }
};
