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
        Schema::table('contacts', function (Blueprint $table) {
            // 檢查表格是否存在，如果不存在則跳過
            if (Schema::hasTable('contacts')) {
                // 只添加不存在的欄位
                if (!Schema::hasColumn('contacts', 'phone_notification_sent_at')) {
                    $table->timestamp('phone_notification_sent_at')->nullable();
                }
                if (!Schema::hasColumn('contacts', 'store_id')) {
                    $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                }
                if (!Schema::hasColumn('contacts', 'ip_address')) {
                    $table->string('ip_address')->nullable();
                }
                if (!Schema::hasColumn('contacts', 'user_agent')) {
                    $table->text('user_agent')->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasTable('contacts')) {
                if (Schema::hasColumn('contacts', 'phone_notification_sent_at')) {
                    $table->dropColumn('phone_notification_sent_at');
                }
                if (Schema::hasColumn('contacts', 'store_id')) {
                    $table->dropForeign(['store_id']);
                    $table->dropColumn('store_id');
                }
                if (Schema::hasColumn('contacts', 'ip_address')) {
                    $table->dropColumn('ip_address');
                }
                if (Schema::hasColumn('contacts', 'user_agent')) {
                    $table->dropColumn('user_agent');
                }
            }
        });
    }
};