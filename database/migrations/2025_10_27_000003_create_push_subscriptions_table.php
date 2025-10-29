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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->text('endpoint')->comment('推播訂閱端點');
            $table->string('p256dh_key', 255)->comment('加密公鑰');
            $table->string('auth_key', 255)->comment('認證密鑰');
            $table->string('user_agent', 500)->nullable()->comment('用戶瀏覽器資訊');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamp('last_used_at')->nullable()->comment('最後使用時間');
            $table->timestamps();

            // 索引
            $table->index('customer_id');
            $table->index('is_active');
            $table->index('last_used_at');

            // 唯一性約束：同一個用戶的同一個端點只能有一筆記錄
            $table->unique(['customer_id', 'endpoint'], 'customer_endpoint_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
