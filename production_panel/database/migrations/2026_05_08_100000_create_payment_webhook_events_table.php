<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 32);
            $table->string('gateway_event_id', 191);
            $table->string('event_type', 191);
            $table->string('status', 32)->default('pending');
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            // Idempotency key: one durable row per provider event prevents replay/double-credit attacks.
            $table->unique(['gateway', 'gateway_event_id'], 'payment_webhook_events_gateway_event_unique');
            $table->index(['status', 'created_at'], 'payment_webhook_events_status_created_idx');
            $table->index(['gateway', 'event_type', 'created_at'], 'payment_webhook_events_gateway_type_created_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX transactions_deposit_reference_unique ON transactions (reference) WHERE reference IS NOT NULL AND type = 'deposit'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS transactions_deposit_reference_unique');
        }

        Schema::dropIfExists('payment_webhook_events');
    }
};
