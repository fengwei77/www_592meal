<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * RolePermissionSeeder
 *
 * 建立系統的基礎角色與權限
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 重置快取
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================================
        // 建立權限 (Permissions)
        // ============================================

        $permissions = [
            // 產品管理
            'manage_products',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',

            // 分類管理
            'manage_categories',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',

            // 菜單管理
            'manage_menu_categories',
            'view_menu_categories',
            'create_menu_categories',
            'edit_menu_categories',
            'delete_menu_categories',

            'manage_menu_items',
            'view_menu_items',
            'create_menu_items',
            'edit_menu_items',
            'delete_menu_items',
            // 訂單管理
            'manage_orders',
            'view_orders',
            'create_orders',
            'edit_orders',
            'cancel_orders',

            // 顧客管理
            'view_customers',
            'manage_customers',

            // LINE Pay 設定
            'manage_line_pay_settings',
            'view_line_pay_settings',

            // LINE Pay 審核 (僅 Super Admin)
            'view_line_pay_approvals',
            'approve_line_pay',
            'reject_line_pay',

            // 報表
            'view_reports',
            'export_reports',

            // 系統設定
            'manage_settings',
            'view_settings',

            // 系統管理 (僅 Super Admin)
            'access_system_management',
            'view_system_statistics',
            'manage_orders',
            'manual_payment_processing',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ============================================
        // 建立角色 (Roles) 並指派權限
        // ============================================

        // 1. Super Admin (超級管理員)
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all()); // 所有權限

        // 2. Store Owner (店家)
        $storeOwner = Role::create(['name' => 'store_owner']);
        $storeOwner->givePermissionTo([
            // 產品管理
            'manage_products',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',

            // 分類管理
            'manage_categories',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',

            // 菜單管理
            'manage_menu_categories',
            'view_menu_categories',
            'create_menu_categories',
            'edit_menu_categories',
            'delete_menu_categories',

            'manage_menu_items',
            'view_menu_items',
            'create_menu_items',
            'edit_menu_items',
            'delete_menu_items',

            // 訂單管理
            'manage_orders',
            'view_orders',
            'create_orders',
            'edit_orders',
            'cancel_orders',

            // 顧客管理
            'view_customers',
            'manage_customers',

            // LINE Pay 設定（可以設定但不能審核）
            'manage_line_pay_settings',
            'view_line_pay_settings',

            // 報表
            'view_reports',
            'export_reports',

            // 設定
            'view_settings',
        ]);

        $this->command->info('✅ Roles and Permissions created successfully!');
        $this->command->info('');
        $this->command->info('Roles:');
        $this->command->info('  - super_admin (所有權限)');
        $this->command->info('  - store_owner (店家權限)');
        $this->command->info('');
        $this->command->info('Permissions: ' . count($permissions) . ' permissions created');
    }
}
