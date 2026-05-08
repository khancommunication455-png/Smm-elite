<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->enum('tier', ['economy', 'standard', 'premium'])->nullable()->after('type');
            $table->unsignedInteger('min_time')->nullable()->after('tier'); // in hours
            $table->unsignedInteger('max_time')->nullable()->after('min_time'); // in hours
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['tier', 'min_time', 'max_time']);
        });
    }
};
