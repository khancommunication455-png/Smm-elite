<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('icon')->default('list_alt');
            $t->string('color')->default('#adc6ff');
            $t->enum('status',['active','inactive'])->default('active');
            $t->timestamps();
        });

        Schema::create('api_providers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('url');
            $t->string('api_key');
            $t->decimal('percentage_increase',8,2)->default(0);
            $t->enum('status',['active','inactive'])->default('active');
            $t->timestamps();
        });

        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->foreignId('category_id')->constrained()->onDelete('cascade');
            $t->foreignId('api_provider_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('api_service_id')->nullable();
            $t->decimal('rate',12,6);
            $t->unsignedBigInteger('min')->default(10);
            $t->unsignedBigInteger('max')->default(100000);
            $t->enum('status',['active','inactive'])->default('active');
            $t->enum('type',['api','manual'])->default('api');
            $t->timestamps();
            $t->unique(['api_provider_id','api_service_id']);
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->foreignId('service_id')->constrained()->onDelete('cascade');
            $t->string('link');
            $t->unsignedBigInteger('quantity');
            $t->decimal('total',14,6);
            $t->enum('status',['pending','in progress','completed','partial','cancelled','refunded','error'])->default('pending');
            $t->unsignedBigInteger('remains')->default(0);
            $t->unsignedBigInteger('start_count')->default(0);
            $t->unsignedBigInteger('api_order_id')->nullable();
            $t->timestamps();
        });

        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->decimal('amount',14,6);
            $t->enum('type',['deposit','deduction','referral_bonus','refund'])->default('deposit');
            $t->string('description')->nullable();
            $t->enum('status',['pending','completed','failed'])->default('pending');
            $t->string('reference')->nullable();
            $t->timestamps();
        });

        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->string('subject');
            $t->text('message');
            $t->string('category')->default('other');
            $t->unsignedBigInteger('order_id')->nullable();
            $t->enum('status',['open','pending','closed'])->default('open');
            $t->timestamps();
        });

        Schema::create('ticket_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->text('message');
            $t->boolean('is_admin')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('services');
        Schema::dropIfExists('api_providers');
        Schema::dropIfExists('categories');
    }
};
