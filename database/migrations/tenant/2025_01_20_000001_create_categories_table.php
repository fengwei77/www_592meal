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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分類名稱');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            // 索引
            $table->index('sort_order', 'idx_categories_sort_order');
        });

        // 註解
        DB::statement("COMMENT ON TABLE categories IS '產品分類表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
