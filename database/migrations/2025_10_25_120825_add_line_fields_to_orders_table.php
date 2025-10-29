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
            // 將電話改為非必填
            $table->string('customer_phone', 20)->nullable()->change();

            // 添加 LINE 相關欄位
            $table->string('line_user_id', 100)->nullable()->after('customer_email');
            $table->string('line_display_name', 100)->nullable()->after('line_user_id');
            $table->string('line_picture_url', 500)->nullable()->after('line_display_name');

            // 添加索引
            $table->index('line_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 移除索引
            $table->dropIndex(['line_user_id']);

            // 移除 LINE 欄位
            $table->dropColumn(['line_user_id', 'line_display_name', 'line_picture_url']);

            // 將電話改回必填
            $table->string('customer_phone', 20)->nullable(false)->change();
        });
    }
};
