<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null')->comment('分類 ID');
            $table->string('name')->comment('產品名稱');
            $table->text('description')->nullable()->comment('產品描述');
            $table->decimal('price', 10, 2)->comment('價格');
            $table->text('image_url')->nullable()->comment('圖片 URL');
            $table->boolean('is_available')->default(true)->comment('是否可訂購');
            $table->integer('stock')->nullable()->comment('庫存數量（NULL = 不限量）');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            // 索引
            $table->index('category_id', 'idx_products_category_id');
            $table->index('is_available', 'idx_products_is_available');
            $table->index('sort_order', 'idx_products_sort_order');
        });

        // 複合索引（常用查詢：可用產品依排序）
        DB::statement("CREATE INDEX idx_products_available_sort ON products(is_available, sort_order) WHERE is_available = TRUE");

        // 約束
        DB::statement("ALTER TABLE products ADD CONSTRAINT chk_products_price CHECK (price >= 0)");
        DB::statement("ALTER TABLE products ADD CONSTRAINT chk_products_stock CHECK (stock IS NULL OR stock >= 0)");

        // 註解
        DB::statement("COMMENT ON TABLE products IS '產品/菜單表'");
        DB::statement("COMMENT ON COLUMN products.stock IS '庫存（NULL = 不限量）'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
