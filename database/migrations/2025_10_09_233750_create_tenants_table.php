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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained('stores')->onDelete('cascade')->comment('店家 ID');
            $table->string('schema_name', 100)->unique()->comment('PostgreSQL Schema 名稱');
            $table->json('metadata')->default('{}')->comment('租戶元資料');
            $table->timestamps();

            // 索引
            $table->index('schema_name', 'idx_tenants_schema_name');
        });

        // PostgreSQL 特定功能：資料表註解
        // 只在 PostgreSQL 環境下執行，避免測試環境 (SQLite) 報錯
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("COMMENT ON TABLE tenants IS '租戶 Schema 元資料'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
