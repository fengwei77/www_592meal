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
            // 推播通知偏好設定
            $table->boolean('notification_confirmed')->default(true)->after('email')
                ->comment('接收訂單確認通知');
            $table->boolean('notification_preparing')->default(true)->after('notification_confirmed')
                ->comment('接收製作中通知');
            $table->boolean('notification_ready')->default(true)->after('notification_preparing')
                ->comment('接收完成通知');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'notification_confirmed',
                'notification_preparing',
                'notification_ready',
            ]);
        });
    }
};
