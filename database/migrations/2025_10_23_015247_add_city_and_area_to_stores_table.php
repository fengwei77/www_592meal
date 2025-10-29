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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address')->comment('縣市');
            $table->string('area')->nullable()->after('city')->comment('區域');
            $table->boolean('is_featured')->default(false)->after('is_active')->comment('是否為推薦店家');

            // 添加索引 (如果不存在)
            if (!Schema::hasIndex('stores', 'idx_city_area')) {
                $table->index(['city', 'area'], 'idx_city_area');
            }
            if (!Schema::hasIndex('stores', 'idx_coordinates')) {
                $table->index(['latitude', 'longitude'], 'idx_coordinates');
            }
            // idx_featured 可能已存在，不重複創建
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasIndex('stores', 'idx_city_area')) {
                $table->dropIndex('idx_city_area');
            }
            if (Schema::hasIndex('stores', 'idx_coordinates')) {
                $table->dropIndex('idx_coordinates');
            }

            $table->dropColumn(['city', 'area', 'is_featured']);
        });
    }
};
