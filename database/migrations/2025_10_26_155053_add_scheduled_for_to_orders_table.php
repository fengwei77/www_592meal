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
            $table->dateTime('scheduled_for')->nullable()->after('notes')->comment('預訂日期時間');
            $table->boolean('is_scheduled_order')->default(false)->after('scheduled_for')->comment('是否為預訂單');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['scheduled_for', 'is_scheduled_order']);
        });
    }
};
