<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add system fields to orders table
 *
 * 為系統管理功能添加必要欄位
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
            $table->text('payment_notes')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->index(['user_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'payment_notes', 'paid_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status', 'created_at']);
        });
    }
};