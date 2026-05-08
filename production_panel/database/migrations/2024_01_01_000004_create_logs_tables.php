<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('gateway'); // stripe, paypal, manual
            $table->string('status'); // pending, completed, failed
            $table->decimal('amount', 14, 6);
            $table->string('reference')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->index(['user_id', 'gateway', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('provider_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_provider_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('action'); // add_order, get_status, get_balance, etc
            $table->string('status'); // success, failed
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('response_time', 8, 3)->nullable();
            $table->timestamps();
            $table->foreign('api_provider_id')->references('id')->on('api_providers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index(['api_provider_id', 'status', 'created_at']);
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_logs');
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('logs');
    }
};
