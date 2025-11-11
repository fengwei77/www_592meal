<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Login Logs Tables
 *
 * 創建登入日誌所需的資料表
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 網站登入日誌表
        if (!Schema::hasTable('website_login_logs')) {
            Schema::create('website_login_logs', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->boolean('success')->default(false);
                $table->string('failure_reason')->nullable();
                $table->timestamps();

                $table->index(['created_at', 'success']);
            });
        }

        // 後台登入日誌表
        if (!Schema::hasTable('admin_login_logs')) {
            Schema::create('admin_login_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('email');
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->boolean('success')->default(false);
                $table->string('failure_reason')->nullable();
                $table->timestamps();

                $table->index(['created_at', 'success']);
                $table->index('user_id');
            });
        }

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
        Schema::dropIfExists('website_login_logs');
        Schema::dropIfExists('admin_login_logs');

        // 注意：不刪除 orders 表的新欄位，避免影響現有數據
    }
};