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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->comment('店名');
            $table->string('subdomain', 100)->unique()->comment('子網域');
            $table->string('phone', 20)->nullable()->comment('電話');
            $table->text('address')->nullable()->comment('地址');
            $table->json('settings')->default('{}')->comment('店家設定');
            $table->json('line_pay_settings')->default('{}')->comment('LINE Pay 設定');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();

            // 索引
            $table->index('user_id', 'idx_stores_user_id');
            $table->index('is_active', 'idx_stores_is_active');
            $table->index('created_at', 'idx_stores_created_at');
        });

        // PostgreSQL 特定功能：全文搜尋索引和資料表註解
        // 只在 PostgreSQL 環境下執行，避免測試環境 (SQLite) 報錯
        if (DB::getDriverName() === 'pgsql') {
            // 為全文搜尋建立 GIN 索引
            DB::statement("CREATE INDEX idx_stores_search ON stores USING GIN(to_tsvector('english', name || ' ' || COALESCE(address, '')))");

            // 資料表註解
            DB::statement("COMMENT ON TABLE stores IS '店家資訊表（租戶）'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
