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
        Schema::create('order_cancellation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id', 100)->index()->comment('LINE用戶ID');
            $table->foreignId('order_id')->constrained()->onDelete('cascade')->comment('訂單ID');
            $table->timestamp('cancelled_at')->useCurrent()->comment('取消時間');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->timestamps();

            // 索引以優化查詢
            $table->index(['line_user_id', 'cancelled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cancellation_logs');
    }
};
