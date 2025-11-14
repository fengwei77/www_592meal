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
        Schema::table('customers', function (Blueprint $table) {
            // 退單通知
            $table->boolean('notification_cancelled')->default(true)->after('notification_ready')
                ->comment('接收退單通知');

            // 相關城市併處理通知
            $table->boolean('notification_related_cities')->default(false)->after('notification_cancelled')
                ->comment('接收相關城市訂單處理通知');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'notification_cancelled',
                'notification_related_cities',
            ]);
        });
    }
};