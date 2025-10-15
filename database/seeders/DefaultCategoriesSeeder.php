<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Default Categories Seeder
 *
 * 在 Tenant Schema 中建立預設分類
 * 執行環境：必須已切換到 Tenant Schema
 */
class DefaultCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $categories = [
            ['name' => '主餐', 'sort_order' => 1],
            ['name' => '配菜', 'sort_order' => 2],
            ['name' => '飲料', 'sort_order' => 3],
            ['name' => '甜點', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
