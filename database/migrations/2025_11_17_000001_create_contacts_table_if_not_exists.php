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
        if (!Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->text('message');
                $table->enum('status', ['new', 'read', 'replied'])->default('new');
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('phone_notification_sent_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index('store_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 只在表格確實存在且是由此遷移創建的情況下才刪除
        if (Schema::hasTable('contacts')) {
            Schema::dropIfExists('contacts');
        }
    }
};