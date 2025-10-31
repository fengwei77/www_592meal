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
            $this->info('Super Admin Login: https://cms.oh592meal.test');
            $this->info('Frontend: https://oh592meal.test');
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

        $this->info('  ✅ Created ' . count($roles) . ' roles');
        $this->info('  ✅ Created ' . count($permissions) . ' permissions');
    }

    /**
     * 創建 Super Admin 用戶
     */
    private function createSuperAdmin()
    {
        // 檢查是否已存在 Super Admin
        $existingSuperAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->first();

        if ($existingSuperAdmin) {
            $this->info('  ⚠️  Super Admin already exists');
            $this->info('    Email: ' . $existingSuperAdmin->email);
            return;
        }

        // 創建 Super Admin 用戶
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@oh592meal.test',
            'password' => Hash::make('admin123456'),
            'email_verified_at' => now(),
        ]);

        // 分配角色
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $user->assignRole($superAdminRole);

        // 分配所有權限
        $permissions = Permission::all();
        $user->givePermissionTo($permissions);

        $this->info('  ✅ Created Super Admin user');
        $this->info('    Email: admin@oh592meal.test');
        $this->info('    Password: admin123456');
        $this->info('    ');
        $this->info('    ⚠️  Please change the password after first login!');
    }
}