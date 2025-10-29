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
        Schema::table('orders', function (Blueprint $table) {
            // 取消原因類型：rejected(店家退單), abandoned(客人棄單), customer_cancelled(客人取消)
            $table->string('cancellation_type', 50)->nullable()->after('status');
            // 取消原因說明
            $table->text('cancellation_reason')->nullable()->after('cancellation_type');
            // 取消時間
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_type', 'cancellation_reason', 'cancelled_at']);
        });
    }
};
