<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('lark_webhook_events')) {
            Schema::create('lark_webhook_events', function (Blueprint $table): void {
                $table->id();
                $table->string('event_id')->nullable()->index();
                $table->string('event_type')->nullable()->index();
                $table->string('schema_version', 20)->nullable();
                $table->string('app_id')->nullable();
                $table->string('tenant_key')->nullable();
                $table->string('status', 20)->default('received')->index();
                $table->text('message')->nullable();
                $table->longText('payload')->nullable();
                $table->text('headers')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->dateTime('event_created_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lark_webhook_events');
    }
};
