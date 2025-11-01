<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the 592Meal system with initial data and super admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 592Meal System Setup');
        $this->info('===================');

        try {
            // 1. 創建初始數據
            $this->info('📊 Creating initial data...');
            $this->createInitialData();

            // 2. 創建 Super Admin
            $this->info('👤 Creating Super Admin...');
            $this->createSuperAdmin();

            $this->info('✅ System setup completed successfully!');
            $this->info('=================================');
            $this->info('Super Admin Login: ' . config('app.admin_url'));
            $this->info('Frontend: ' . config('app.url'));
            $this->info('');

            // 顯示當前配置的超級管理員資訊
            $superAdminConfig = $this->getSuperAdminConfig();
            $this->info('Default Super Admin Credentials:');
            $this->info('  Name: ' . $superAdminConfig['name']);
            $this->info('  Email: ' . $superAdminConfig['email']);
            $this->info('  Password: ' . $superAdminConfig['password']);
            $this->info('');
            $this->info('⚠️  These credentials are read from .env file');
            $this->info('⚠️  To change credentials, modify SUPER_ADMIN_* variables in .env');
            $this->info('');

            $this->info('Next steps:');
            $this->info('1. Login to admin panel');
            $this->info('2. Create or manage stores');
            $this->info('3. Set up menu items');
            $this->info('4. Test the ordering system');

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 創建初始數據
     */
    private function createInitialData()
    {
        // 創建角色
        $roles = [
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'store_owner', 'guard_name' => 'web'],
            ['name' => 'customer', 'guard_name' => 'web'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
        }

        // 創建權限
        $permissions = [
            'access-admin-panel',
            'manage-stores',
            'manage-users',
            'manage-orders',
            'manage-menu-items',
            'view-reports',
            'create-stores',
            'edit-stores',
            'delete-stores',
            'create-menu-items',
            'edit-menu-items',
            'delete-menu-items',
            'view-orders',
            'process-orders',
            'view-dashboard',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName], ['guard_name' => 'web']);
        }

        // 為角色分配權限（重要：確保 role_has_permissions 表正確建立）
        $this->assignPermissionsToRoles();

        $this->info('  ✅ Created ' . count($roles) . ' roles');
        $this->info('  ✅ Created ' . count($permissions) . ' permissions');
        $this->info('  ✅ Assigned permissions to roles');
    }

    /**
     * 為角色分配權限
     */
    private function assignPermissionsToRoles()
    {
        // 為超級管理員分配所有權限
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);

        // 為店家擁有者分配部分權限
        $storeOwnerRole = Role::where('name', 'store_owner')->first();
        $storeOwnerPermissions = Permission::whereIn('name', [
            'manage-stores', 'manage-orders', 'manage-menu-items',
            'create-menu-items', 'edit-menu-items', 'delete-menu-items',
            'view-orders', 'process-orders', 'view-dashboard'
        ])->get();
        $storeOwnerRole->syncPermissions($storeOwnerPermissions);

        // 為顧客分配基本權限（如果需要）
        $customerRole = Role::where('name', 'customer')->first();
        $customerPermissions = Permission::whereIn('name', [
            'view-orders', 'view-dashboard'
        ])->get();
        $customerRole->syncPermissions($customerPermissions);
    }

    /**
     * 創建或修復 Super Admin 用戶
     */
    private function createSuperAdmin()
    {
        // 從環境變數獲取配置
        $superAdminConfig = $this->getSuperAdminConfig();

        // 檢查是否已存在 Super Admin（根據 email）
        $existingSuperAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->first();

        if ($existingSuperAdmin) {
            $this->info('  ⚠️  Super Admin already exists');
            $this->info('    Email: ' . $existingSuperAdmin->email);

            // 修復現有用戶的權限（重要：確保權限正確分配）
            $this->repairSuperAdminPermissions($existingSuperAdmin);
            return;
        }

        // 創建 Super Admin 用戶
        $user = User::create([
            'name' => $superAdminConfig['name'],
            'email' => $superAdminConfig['email'],
            'password' => Hash::make($superAdminConfig['password']),
            'email_verified_at' => now(),
        ]);

        // 分配角色
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $user->assignRole($superAdminRole);

        // 分配所有權限
        $permissions = Permission::all();
        $user->givePermissionTo($permissions);

        $this->info('  ✅ Created Super Admin user');
        $this->info('    Name: ' . $superAdminConfig['name']);
        $this->info('    Email: ' . $superAdminConfig['email']);
        $this->info('    Password: ' . $superAdminConfig['password']);
        $this->info('    ');
        $this->info('    ⚠️  Please change the password after first login!');
    }

    /**
     * 從環境變數獲取超級管理員配置
     */
    private function getSuperAdminConfig(): array
    {
        return [
            'name' => config('super_admin.name', 'Super Admin'),
            'email' => config('super_admin.email', 'admin@example.com'),
            'password' => config('super_admin.password', 'admin123456'),
        ];
    }

    /**
     * 修復現有超級管理員的權限
     */
    private function repairSuperAdminPermissions($user)
    {
        // 確保角色權限正確
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $allPermissions = Permission::all();

        // 重新同步角色權限
        $superAdminRole->syncPermissions($allPermissions);

        // 確保用戶有正確的角色
        if (!$user->hasRole('super_admin')) {
            $user->assignRole($superAdminRole);
        }

        // 直接分配所有權限給用戶（雙重保障）
        $user->syncPermissions($allPermissions);

        $this->info('  ✅ Repaired permissions for existing Super Admin');
        $this->info('    Role permissions: ' . $superAdminRole->permissions->count());
        $this->info('    User permissions: ' . $user->permissions->count());
    }
}