<?php

/**
 * Filament Navigation & Menu Permission Tests
 *
 * 此測試檔案驗證：
 * 1. 選單項目根據權限正確顯示/隱藏
 * 2. Super Admin 可以看到所有選單
 * 3. Store Owner 只能看到有權限的選單
 * 4. 無權限使用者看不到任何選單
 *
 * 使用 Pest + HasResourcePermissions Trait 測試
 */

use App\Filament\Resources\Menu\MenuCategoryResource;
use App\Filament\Resources\Menu\MenuItemResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\Stores\StoreResource;
use App\Filament\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // 建立所有必要權限
    $permissions = [
        'view_users',
        'view_roles',
        'view_permissions',
        'view_store',
        'view_menu_categories',
        'view_menu_items',
    ];

    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    // 建立角色
    $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $storeOwner = Role::firstOrCreate(['name' => 'Store Owner', 'guard_name' => 'web']);

    // Super Admin 擁有所有權限
    $superAdmin->syncPermissions(Permission::all());

    // Store Owner 只有特定權限（不包括系統管理權限）
    $storeOwner->syncPermissions([
        'view_store',
        'view_menu_categories',
        'view_menu_items',
    ]);
});

// ========================================
// Super Admin Navigation Tests
// ========================================

describe('Super Admin Navigation', function () {
    test('Super Admin can see all navigation items', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin);

        // Super Admin 應該可以看到所有 Resource
        expect(UserResource::canViewAny())->toBeTrue()
            ->and(RoleResource::canViewAny())->toBeTrue()
            ->and(PermissionResource::canViewAny())->toBeTrue()
            ->and(StoreResource::canViewAny())->toBeTrue()
            ->and(MenuCategoryResource::canViewAny())->toBeTrue()
            ->and(MenuItemResource::canViewAny())->toBeTrue();
    });

    test('Super Admin bypasses all permission checks', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin);

        // 即使沒有特定權限，Super Admin 也能看到
        $superAdmin->revokePermissionTo('view_users');

        expect(UserResource::canViewAny())->toBeTrue()
            ->and(RoleResource::canViewAny())->toBeTrue();
    });
});

// ========================================
// Store Owner Navigation Tests
// ========================================

describe('Store Owner Navigation', function () {
    test('Store Owner can see only permitted navigation items', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');
        Store::factory()->create(['user_id' => $storeOwner->id]);

        $this->actingAs($storeOwner);

        // Store Owner 可以看到的選單
        expect(StoreResource::canViewAny())->toBeTrue()
            ->and(MenuCategoryResource::canViewAny())->toBeTrue()
            ->and(MenuItemResource::canViewAny())->toBeTrue();

        // Store Owner 看不到的選單（系統管理）
        expect(UserResource::canViewAny())->toBeFalse()
            ->and(RoleResource::canViewAny())->toBeFalse()
            ->and(PermissionResource::canViewAny())->toBeFalse();
    });

    test('Store Owner cannot see system management menus', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $this->actingAs($storeOwner);

        // 系統管理選單應該完全隱藏
        expect(UserResource::canViewAny())->toBeFalse()
            ->and(RoleResource::canViewAny())->toBeFalse()
            ->and(PermissionResource::canViewAny())->toBeFalse();
    });

    test('Store Owner loses menu access when role permissions are updated', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        // 從角色移除權限
        $role = Role::findByName('Store Owner');
        $role->revokePermissionTo('view_menu_categories');

        $this->actingAs($storeOwner);

        // 角色移除權限後，選單應該隱藏
        expect(MenuCategoryResource::canViewAny())->toBeFalse();

        // 其他有權限的選單仍然可見
        expect(StoreResource::canViewAny())->toBeTrue()
            ->and(MenuItemResource::canViewAny())->toBeTrue();

        // 恢復權限供後續測試使用
        $role->givePermissionTo('view_menu_categories');
    });
});

// ========================================
// Unauthorized User Tests
// ========================================

describe('Unauthorized User Navigation', function () {
    test('User without any role cannot see any navigation', function () {
        $user = User::factory()->create(); // 無角色

        $this->actingAs($user);

        // 所有選單都應該隱藏
        expect(UserResource::canViewAny())->toBeFalse()
            ->and(RoleResource::canViewAny())->toBeFalse()
            ->and(PermissionResource::canViewAny())->toBeFalse()
            ->and(StoreResource::canViewAny())->toBeFalse()
            ->and(MenuCategoryResource::canViewAny())->toBeFalse()
            ->and(MenuItemResource::canViewAny())->toBeFalse();
    });

    test('Guest user cannot see any navigation', function () {
        // 未登入狀態
        expect(UserResource::canViewAny())->toBeFalse()
            ->and(RoleResource::canViewAny())->toBeFalse()
            ->and(PermissionResource::canViewAny())->toBeFalse()
            ->and(StoreResource::canViewAny())->toBeFalse()
            ->and(MenuCategoryResource::canViewAny())->toBeFalse()
            ->and(MenuItemResource::canViewAny())->toBeFalse();
    });
});

// ========================================
// Permission-Based Navigation Tests
// ========================================

describe('Permission-Based Navigation', function () {
    test('UserResource requires view_users permission', function () {
        $user = User::factory()->create();

        // 沒有權限
        $this->actingAs($user);
        expect(UserResource::canViewAny())->toBeFalse();

        // 授予權限
        $user->givePermissionTo('view_users');
        expect(UserResource::canViewAny())->toBeTrue();
    });

    test('RoleResource requires view_roles permission', function () {
        $user = User::factory()->create();

        $this->actingAs($user);
        expect(RoleResource::canViewAny())->toBeFalse();

        $user->givePermissionTo('view_roles');
        expect(RoleResource::canViewAny())->toBeTrue();
    });

    test('PermissionResource requires view_permissions permission', function () {
        $user = User::factory()->create();

        $this->actingAs($user);
        expect(PermissionResource::canViewAny())->toBeFalse();

        $user->givePermissionTo('view_permissions');
        expect(PermissionResource::canViewAny())->toBeTrue();
    });

    test('StoreResource requires view_store permission', function () {
        $user = User::factory()->create();

        $this->actingAs($user);
        expect(StoreResource::canViewAny())->toBeFalse();

        $user->givePermissionTo('view_store');
        expect(StoreResource::canViewAny())->toBeTrue();
    });

    test('MenuCategoryResource requires view_menu_categories permission', function () {
        $user = User::factory()->create();

        $this->actingAs($user);
        expect(MenuCategoryResource::canViewAny())->toBeFalse();

        $user->givePermissionTo('view_menu_categories');
        expect(MenuCategoryResource::canViewAny())->toBeTrue();
    });

    test('MenuItemResource requires view_menu_items permission', function () {
        $user = User::factory()->create();

        $this->actingAs($user);
        expect(MenuItemResource::canViewAny())->toBeFalse();

        $user->givePermissionTo('view_menu_items');
        expect(MenuItemResource::canViewAny())->toBeTrue();
    });
});

// ========================================
// Navigation Group Tests
// ========================================

describe('Navigation Group Visibility', function () {
    test('System Management group is only visible to Super Admin', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        // Super Admin 可以看到系統管理群組
        $this->actingAs($superAdmin);
        expect(RoleResource::canViewAny())->toBeTrue()
            ->and(PermissionResource::canViewAny())->toBeTrue();

        // Store Owner 看不到系統管理群組
        $this->actingAs($storeOwner);
        expect(RoleResource::canViewAny())->toBeFalse()
            ->and(PermissionResource::canViewAny())->toBeFalse();
    });

    test('Menu Management group is visible to Store Owner', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $this->actingAs($storeOwner);

        // 菜單管理群組應該可見
        expect(MenuCategoryResource::canViewAny())->toBeTrue()
            ->and(MenuItemResource::canViewAny())->toBeTrue();
    });
});

// ========================================
// Dynamic Permission Changes Tests
// ========================================

describe('Dynamic Permission Changes', function () {
    test('Navigation updates after permission is granted', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 初始沒有權限
        expect(MenuCategoryResource::canViewAny())->toBeFalse();

        // 授予權限
        $user->givePermissionTo('view_menu_categories');

        // 選單應該立即可見
        expect(MenuCategoryResource::canViewAny())->toBeTrue();
    });

    test('Navigation updates after permission is revoked', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('view_menu_categories');

        $this->actingAs($user);

        // 有權限時可見
        expect(MenuCategoryResource::canViewAny())->toBeTrue();

        // 撤銷權限
        $user->revokePermissionTo('view_menu_categories');

        // 選單應該立即隱藏
        expect(MenuCategoryResource::canViewAny())->toBeFalse();
    });

    test('Navigation updates after role is changed', function () {
        $user = User::factory()->create();
        $user->assignRole('Store Owner');

        $this->actingAs($user);

        // Store Owner 可以看到菜單管理
        expect(MenuCategoryResource::canViewAny())->toBeTrue();

        // 移除角色
        $user->removeRole('Store Owner');

        // 選單應該隱藏
        expect(MenuCategoryResource::canViewAny())->toBeFalse();

        // 改為 Super Admin
        $user->assignRole('Super Admin');

        // 所有選單都應該可見
        expect(UserResource::canViewAny())->toBeTrue()
            ->and(RoleResource::canViewAny())->toBeTrue()
            ->and(MenuCategoryResource::canViewAny())->toBeTrue();
    });
});

// ========================================
// Multi-User Scenario Tests
// ========================================

describe('Multi-User Scenarios', function () {
    test('Different users see different navigation based on their roles', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $storeOwner1 = User::factory()->create();
        $storeOwner1->assignRole('Store Owner');

        $storeOwner2 = User::factory()->create();
        $storeOwner2->assignRole('Store Owner');

        $normalUser = User::factory()->create(); // 無角色

        // Super Admin 看到所有選單
        $this->actingAs($superAdmin);
        expect(UserResource::canViewAny())->toBeTrue()
            ->and(MenuCategoryResource::canViewAny())->toBeTrue();

        // Store Owner 1 看到菜單管理
        $this->actingAs($storeOwner1);
        expect(UserResource::canViewAny())->toBeFalse()
            ->and(MenuCategoryResource::canViewAny())->toBeTrue();

        // Store Owner 2 也看到菜單管理
        $this->actingAs($storeOwner2);
        expect(UserResource::canViewAny())->toBeFalse()
            ->and(MenuCategoryResource::canViewAny())->toBeTrue();

        // Normal User 什麼都看不到
        $this->actingAs($normalUser);
        expect(UserResource::canViewAny())->toBeFalse()
            ->and(MenuCategoryResource::canViewAny())->toBeFalse();
    });
});
