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
        Schema::table('users', function (Blueprint $table) {
            $table->datetime('trial_ends_at')->nullable()->after('email')->comment('試用期結束時間');
            $table->datetime('subscription_ends_at')->nullable()->after('trial_ends_at')->comment('訂閱結束時間');
            $table->boolean('is_trial_used')->default(false)->after('subscription_ends_at')->comment('是否已使用試用期');
            $table->datetime('last_subscription_reminder_at')->nullable()->after('is_trial_used')->comment('最後訂閱提醒時間');

            // 索引
            $table->index('trial_ends_at');
            $table->index('subscription_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['trial_ends_at']);
            $table->dropIndex(['subscription_ends_at']);
            $table->dropColumn([
                'trial_ends_at',
                'subscription_ends_at',
                'is_trial_used',
                'last_subscription_reminder_at'
            ]);
        });
    }
};