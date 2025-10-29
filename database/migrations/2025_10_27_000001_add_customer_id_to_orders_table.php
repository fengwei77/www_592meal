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
        Schema::table('orders', function (Blueprint $table) {
            // 添加 customer_id 外鍵（可為 null，因為支援訪客訂單）
            $table->foreignId('customer_id')
                ->nullable()
                ->after('store_id')
                ->constrained('customers')
                ->onDelete('set null');

            // 添加索引以加快查詢
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 移除外鍵約束
            $table->dropForeign(['customer_id']);

            // 移除索引
            $table->dropIndex(['customer_id']);

            // 移除欄位
            $table->dropColumn('customer_id');
        });
    }
};
