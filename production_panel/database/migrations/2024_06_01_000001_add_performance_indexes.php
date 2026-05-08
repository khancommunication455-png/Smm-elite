<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Performance Indexes
 *
 * FIXES MEDIUM-1: Adds missing indexes on high-query columns.
 * Without these, every dashboard query and admin list is a full table scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── orders ──────────────────────────────────────────────────────────
        Schema::table('orders', function (Blueprint $table) {
            // Dashboard: WHERE user_id = ? AND status IN (...)
            $table->index(['user_id', 'status'], 'orders_user_id_status_idx');

            // Sync command: WHERE api_order_id IS NOT NULL
            $table->index('api_order_id', 'orders_api_order_id_idx');

            // Admin panel filter: WHERE status = ?
            $table->index('status', 'orders_status_idx');

            // Weekly/monthly aggregation: WHERE created_at >= ?
            $table->index('created_at', 'orders_created_at_idx');
        });

        // ── transactions ────────────────────────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            // User transaction history: WHERE user_id = ? ORDER BY created_at DESC
            $table->index(['user_id', 'status'], 'txns_user_id_status_idx');

            // Admin: WHERE status = ? AND type = ?
            $table->index(['status', 'type'], 'txns_status_type_idx');

            // Revenue aggregation: WHERE type = 'deposit' AND status = 'completed'
            $table->index(['type', 'status'], 'txns_type_status_idx');
        });

        // ── services ────────────────────────────────────────────────────────
        Schema::table('services', function (Blueprint $table) {
            // Service listing: WHERE status = 'active' ORDER BY category_id
            $table->index(['status', 'category_id'], 'services_status_category_idx');
        });

        // ── tickets ─────────────────────────────────────────────────────────
        Schema::table('tickets', function (Blueprint $table) {
            // User ticket list: WHERE user_id = ? ORDER BY created_at DESC
            $table->index(['user_id', 'status'], 'tickets_user_id_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_status_idx');
            $table->dropIndex('orders_api_order_id_idx');
            $table->dropIndex('orders_status_idx');
            $table->dropIndex('orders_created_at_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('txns_user_id_status_idx');
            $table->dropIndex('txns_status_type_idx');
            $table->dropIndex('txns_type_status_idx');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_status_category_idx');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_user_id_status_idx');
        });
    }
};
