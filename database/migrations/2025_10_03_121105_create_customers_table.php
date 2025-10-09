<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('line_id')->unique();
            $table->text('avatar_url')->nullable();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->rememberToken();
            $table->timestamps();

            // 索引
            $table->index('line_id');
            $table->index('phone');
            $table->index('created_at');
        });

        // 新增註解 (僅 PostgreSQL)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("COMMENT ON TABLE customers IS '顧客表 (LINE Login 用戶)'");
            DB::statement("COMMENT ON COLUMN customers.line_id IS 'LINE 用戶 ID'");
            DB::statement("COMMENT ON COLUMN customers.avatar_url IS 'LINE 頭像 URL'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
