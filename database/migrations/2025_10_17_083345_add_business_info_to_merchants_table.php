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
            // 服務模式（stores 表中已有 business_hours, latitude, longitude）
            $table->enum('service_mode', ['pickup', 'onsite', 'hybrid'])
                  ->default('pickup')
                  ->after('business_hours')
                  ->comment('服務模式：pickup=店址取餐, onsite=駐點服務, hybrid=混合模式');

            // 索引
            $table->index('service_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('service_mode');
            $table->dropIndex(['service_mode']);
        });
    }
};
