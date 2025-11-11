<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create website_login_logs table
 *
 * 記錄網站前台登入日誌
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            // 索引
            $table->index(['created_at', 'success']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_login_logs');
    }
};