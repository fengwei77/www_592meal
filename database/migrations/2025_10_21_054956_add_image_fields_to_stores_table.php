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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('store_logo')->nullable()->after('service_mode');
            $table->string('store_cover_image')->nullable()->after('store_logo');
            $table->json('store_photos')->nullable()->after('store_cover_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['store_logo', 'store_cover_image', 'store_photos']);
        });
    }
};
