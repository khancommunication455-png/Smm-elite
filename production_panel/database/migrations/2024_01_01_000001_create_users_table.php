<?php
// ============================================================
// MIGRATION 1 — users
// File: database/migrations/2024_01_01_000001_create_users_table.php
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->decimal('funds', 14, 6)->default(0);
            $t->string('referral_code', 16)->nullable()->unique();
            $t->unsignedBigInteger('referred_by')->nullable();
            $t->string('telegram_user_id')->nullable();
            $t->enum('status', ['active','banned'])->default('active');
            $t->boolean('is_admin')->default(false);
            $t->rememberToken();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
