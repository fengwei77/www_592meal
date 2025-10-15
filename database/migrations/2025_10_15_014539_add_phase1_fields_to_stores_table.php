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
            // Phase 1 基本資訊欄位
            $table->text('description')->nullable()->after('address')->comment('店家描述');
            $table->enum('store_type', ['restaurant', 'cafe', 'snack', 'bar', 'bakery', 'other'])->default('other')->after('description')->comment('店家類型');

            // 地理位置欄位
            $table->decimal('latitude', 10, 8)->nullable()->after('store_type')->comment('緯度');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('經度');

            // 營業時間欄位
            $table->json('business_hours')->nullable()->after('longitude')->comment('營業時間 JSON');

            // 圖片欄位
            $table->string('logo_url')->nullable()->after('business_hours')->comment('店家 Logo URL');
            $table->string('cover_image_url')->nullable()->after('logo_url')->comment('封面圖片 URL');

            // 社群媒體連結
            $table->json('social_links')->nullable()->after('cover_image_url')->comment('社群媒體連結 JSON');

            // 新增索引
            $table->index(['store_type']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // 移除 Phase 1 新增的欄位
            $table->dropIndex(['store_type']);
            $table->dropIndex(['latitude', 'longitude']);

            $table->dropColumn([
                'description',
                'store_type',
                'latitude',
                'longitude',
                'business_hours',
                'logo_url',
                'cover_image_url',
                'social_links',
            ]);
        });
    }
};
