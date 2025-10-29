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
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                  ->constrained('stores')
                  ->onDelete('cascade')
                  ->comment('所屬店家');

            // 基本資訊
            $table->string('name')->comment('分類名稱');
            $table->text('description')->nullable()->comment('分類描述');
            $table->string('icon')->nullable()->comment('分類圖示 (emoji or icon class)');

            // 排序與狀態
            $table->integer('display_order')->default(0)->comment('顯示排序');
            $table->boolean('is_active')->default(true)->comment('是否啟用');

            $table->timestamps();

            // 索引
            $table->index(['store_id', 'is_active'], 'idx_store_active');
            $table->index('display_order', 'idx_display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_categories');
    }
};
