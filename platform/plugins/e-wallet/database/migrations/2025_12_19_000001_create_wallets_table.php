<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ec_wallets')) {
            return;
        }

        Schema::create('ec_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->unique();
            $table->bigInteger('balance')->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_wallets');
    }
};
