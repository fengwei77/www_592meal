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
        Schema::create('store_customer_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade')->comment('店家ID');
            $table->string('line_user_id', 100)->index()->comment('LINE用戶ID');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade')->comment('客戶ID');
            $table->string('reason')->default('exceed_cancellation_limit')->comment('鎖定原因');
            $table->integer('cancellation_count')->default(0)->comment('該店家的取消次數');
            $table->timestamp('blocked_at')->useCurrent()->comment('鎖定時間');
            $table->string('blocked_by')->default('system')->comment('鎖定者');
            $table->text('notes')->nullable()->comment('備註');
            $table->timestamps();

            // 唯一索引：一個客戶在一個店家只能有一筆鎖定記錄
            $table->unique(['store_id', 'line_user_id']);

            // 索引優化查詢
            $table->index(['line_user_id', 'blocked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_customer_blocks');
    }
};
