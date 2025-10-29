<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StoreOwnerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 定義所有系統權限（包含 Store Owner 和 Super Admin 專屬權限）
        $allPermissions = [
            // 系統管理（僅 Super Admin）
            ['name' => 'view_users', 'description' => '查看使用者管理', 'super_admin_only' => true],
            ['name' => 'view_roles', 'description' => '查看角色管理', 'super_admin_only' => true],
            ['name' => 'view_permissions', 'description' => '查看權限管理', 'super_admin_only' => true],

            // 店家管理（Store Owner 可存取自己的，Super Admin 可存取所有）
            ['name' => 'view_store', 'description' => '查看自己的店家資料'],
            ['name' => 'edit_store', 'description' => '編輯自己的店家資料'],

            // 菜單分類管理
            ['name' => 'view_menu_categories', 'description' => '查看菜單分類'],
            ['name' => 'create_menu_categories', 'description' => '建立菜單分類'],
            ['name' => 'edit_menu_categories', 'description' => '編輯菜單分類'],
            ['name' => 'delete_menu_categories', 'description' => '刪除菜單分類'],

            // 菜單項目管理
            ['name' => 'view_menu_items', 'description' => '查看菜單項目'],
            ['name' => 'create_menu_items', 'description' => '建立菜單項目'],
            ['name' => 'edit_menu_items', 'description' => '編輯菜單項目'],
            ['name' => 'delete_menu_items', 'description' => '刪除菜單項目'],

            // 訂單管理（未來功能）
            ['name' => 'view_orders', 'description' => '查看訂單'],
            ['name' => 'manage_orders', 'description' => '管理訂單（接受、拒絕、完成）'],

            // 顧客管理
            ['name' => 'view_customers', 'description' => '查看顧客資料'],

            // 報表查看
            ['name' => 'view_reports', 'description' => '查看營運報表'],
        ];

        // 建立所有權限
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );

            $this->command->info("已建立權限：{$permission['name']} - {$permission['description']}");
        }

        // 建立或取得 Store Owner 角色
        $storeOwnerRole = Role::firstOrCreate(
            ['name' => 'Store Owner'],
            ['guard_name' => 'web']
        );

        // 篩選出 Store Owner 可用的權限（排除 super_admin_only）
        $storeOwnerPermissions = array_filter($allPermissions, function ($permission) {
            return !isset($permission['super_admin_only']) || !$permission['super_admin_only'];
        });
        $storeOwnerPermissionNames = array_column($storeOwnerPermissions, 'name');
        $storeOwnerRole->syncPermissions($storeOwnerPermissionNames);

        $this->command->info('✓ Store Owner 角色已建立並分配了 ' . count($storeOwnerPermissionNames) . ' 個權限');

        // 建立 Super Admin 角色（如果還不存在）
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['guard_name' => 'web']
        );

        // Super Admin 擁有所有權限
        $superAdminRole->syncPermissions(Permission::all());

        $this->command->info('✓ Super Admin 角色已更新，擁有所有權限');
    }
}
