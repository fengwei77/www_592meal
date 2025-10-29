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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                  ->constrained('stores')
                  ->onDelete('cascade')
                  ->comment('所屬店家');
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('menu_categories')
                  ->onDelete('set null')
                  ->comment('所屬分類');

            // 基本資訊
            $table->string('name')->comment('餐點名稱');
            $table->text('description')->nullable()->comment('餐點描述');
            $table->decimal('price', 10, 2)->comment('餐點價格');

            // 狀態
            $table->boolean('is_active')->default(true)->comment('是否上架');
            $table->boolean('is_featured')->default(false)->comment('是否推薦');
            $table->boolean('is_sold_out')->default(false)->comment('是否售完');

            // 排序
            $table->integer('display_order')->default(0)->comment('顯示排序');

            // 軟刪除
            $table->softDeletes();

            $table->timestamps();

            // 索引
            $table->index(['store_id', 'category_id'], 'idx_store_category');
            $table->index('is_active', 'idx_active');
            $table->index('is_featured', 'idx_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
