<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add System Management Fields to Orders Table
 *
 * 為訂單表添加系統管理所需的欄位
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 添加訂單系統管理欄位
        if (!Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
                $table->text('payment_notes')->nullable()->after('payment_status');
                $table->timestamp('paid_at')->nullable()->after('payment_status');
                $table->index(['user_id']);
                $table->index(['status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 注意：不刪除 orders 表的新欄位，避免影響現有數據
        // 如果需要移除新增的欄位，請在這裡添加相關的 drop column 語句
    }
};