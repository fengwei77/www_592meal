<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * 角色權限系統測試
 *
 * 測試 spatie/laravel-permission 整合與權限控制
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 執行 seeders
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /**
     * 測試 roles 和 permissions 是否正確建立
     */
    public function test_roles_and_permissions_are_seeded_correctly(): void
    {
        // 檢查 roles 是否存在
        $this->assertTrue(Role::where('name', 'super_admin')->exists());
        $this->assertTrue(Role::where('name', 'store_owner')->exists());

        // 檢查 permissions 數量
        $this->assertGreaterThan(0, Permission::count());

        // 檢查特定權限是否存在
        $this->assertTrue(Permission::where('name', 'manage_products')->exists());
        $this->assertTrue(Permission::where('name', 'approve_line_pay')->exists());
    }

    /**
     * 測試 super_admin 擁有所有權限
     */
    public function test_super_admin_has_all_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Super Admin 應該擁有所有權限
        $allPermissions = Permission::all()->pluck('name')->toArray();

        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $superAdmin->hasPermissionTo($permission),
                "Super Admin should have '{$permission}' permission"
            );
        }
    }

    /**
     * 測試 store_owner 擁有正確的權限
     */
    public function test_store_owner_has_correct_permissions(): void
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('store_owner');

        // Store Owner 應該有的權限
        $allowedPermissions = [
            'manage_products',
            'view_products',
            'manage_orders',
            'view_orders',
            'manage_line_pay_settings',
        ];

        foreach ($allowedPermissions as $permission) {
            $this->assertTrue(
                $storeOwner->hasPermissionTo($permission),
                "Store Owner should have '{$permission}' permission"
            );
        }

        // Store Owner 不應該有的權限
        $deniedPermissions = [
            'approve_line_pay',
            'reject_line_pay',
            'view_line_pay_approvals',
        ];

        foreach ($deniedPermissions as $permission) {
            $this->assertFalse(
                $storeOwner->hasPermissionTo($permission),
                "Store Owner should NOT have '{$permission}' permission"
            );
        }
    }

    /**
     * 測試 User 可以被指派角色
     */
    public function test_user_can_be_assigned_role(): void
    {
        $user = User::factory()->create();

        $user->assignRole('store_owner');

        $this->assertTrue($user->hasRole('store_owner'));
        $this->assertFalse($user->hasRole('super_admin'));
    }

    /**
     * 測試 User 可以直接被指派權限（不透過角色）
     */
    public function test_user_can_be_assigned_direct_permission(): void
    {
        $user = User::factory()->create();

        $user->givePermissionTo('manage_products');

        $this->assertTrue($user->hasPermissionTo('manage_products'));
        $this->assertFalse($user->hasPermissionTo('approve_line_pay'));
    }

    /**
     * 測試角色可以被移除
     */
    public function test_user_role_can_be_removed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('store_owner');

        $this->assertTrue($user->hasRole('store_owner'));

        $user->removeRole('store_owner');

        $this->assertFalse($user->hasRole('store_owner'));
    }

    /**
     * 測試權限可以被移除
     */
    public function test_user_permission_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage_products');

        $this->assertTrue($user->hasPermissionTo('manage_products'));

        $user->revokePermissionTo('manage_products');

        $this->assertFalse($user->hasPermissionTo('manage_products'));
    }

    /**
     * 測試 Super Admin Seeder
     */
    public function test_super_admin_seeder_creates_admin_user(): void
    {
        $this->seed(\Database\Seeders\SuperAdminSeeder::class);

        // 檢查 super admin 是否建立
        $admin = User::where('email', 'admin@592meal.com')->first();

        $this->assertNotNull($admin);
        $this->assertEquals('Super Admin', $admin->name);
        $this->assertTrue($admin->hasRole('super_admin'));
    }

    /**
     * 測試多角色指派
     */
    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create();

        $user->assignRole(['super_admin', 'store_owner']);

        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->hasRole('store_owner'));
        $this->assertTrue($user->hasAnyRole(['super_admin', 'store_owner']));
    }

    /**
     * 測試權限快取
     */
    public function test_permission_cache_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('store_owner');

        // 第一次檢查（應該建立快取）
        $this->assertTrue($user->hasPermissionTo('manage_products'));

        // 新增權限（應該清除快取）
        $user->givePermissionTo('approve_line_pay');

        // 第二次檢查（應該從新快取讀取）
        $this->assertTrue($user->hasPermissionTo('approve_line_pay'));
    }
}
