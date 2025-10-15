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
            // For Story 1.1 (LINE Login)
            $table->string('line_id')->unique()->nullable()->after('email');
            $table->text('avatar_url')->nullable()->after('line_id');

            // For Story 1.2 (Merchant Registration)
            $table->string('user_type')->default('consumer')->after('id');
            $table->integer('max_stores')->default(1)->after('user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['line_id', 'avatar_url', 'user_type', 'max_stores']);
        });
    }
};
