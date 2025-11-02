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
        Schema::create('customer_order_locks', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id', 100)->unique()->comment('LINE用戶ID');
            $table->timestamp('locked_until')->comment('鎖定到期時間');
            $table->string('reason')->default('exceed_cancellation_limit')->comment('鎖定原因');
            $table->integer('cancellation_count')->default(0)->comment('取消次數');
            $table->timestamps();

            // 索引
            $table->index('locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_order_locks');
    }
};
