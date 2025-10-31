<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin
                            {--email= : The email address for the super admin}
                            {--password= : The password for the super admin}
                            {--name= : The name for the super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user for fresh installations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Creating Super Admin User for 592Meal System');
        $this->info('===========================================');

        // 檢查是否已有 Super Admin
        $existingSuperAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->first();

        if ($existingSuperAdmin) {
            $this->error('❌ Super Admin user already exists!');
            $this->info('Email: ' . $existingSuperAdmin->email);
            $this->info('Name: ' . $existingSuperAdmin->name);

            if ($this->confirm('Do you want to create an additional super admin?')) {
                return $this->createSuperAdmin();
            }

            return 0;
        }

        return $this->createSuperAdmin();
    }

    /**
     * 創建 Super Admin 用戶
     */
    private function createSuperAdmin()
    {
        $this->info('📝 Please provide Super Admin details:');
        $this->info('------------------------------------');

        // 獲取用戶輸入
        $email = $this->option('email') ?: $this->ask('What is the email address?', 'admin@oh592meal.test');
        $name = $this->option('name') ?: $this->ask('What is the full name?', 'Super Admin');
        $password = $this->option('password') ?: $this->secret('What is the password?');

        // 驗證輸入
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'unique:users'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error('❌ ' . $error);
            }
            return 1;
        }

        try {
            // 確保角色和權限存在
            $this->ensureRolesAndPermissions();

            // 創建用戶
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            // 分配 Super Admin 角色
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $user->assignRole($superAdminRole);
                $this->info('✅ Assigned super_admin role');
            }

            // 分配所有權限
            $permissions = Permission::all();
            $user->givePermissionTo($permissions);
            $this->info('✅ Assigned all permissions (' . $permissions->count() . ' permissions)');

            $this->info('✅ Super Admin user created successfully!');
            $this->info('------------------------------------');
            $this->info('Email: ' . $email);
            $this->info('Name: ' . $name);
            $this->info('URL: https://cms.oh592meal.test');
            $this->info('Login and go to Dashboard to manage the system.');

        } catch (\Exception $e) {
            $this->error('❌ Error creating Super Admin user: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 確保必要的角色和權限存在
     */
    private function ensureRolesAndPermissions()
    {
        $this->info('🔧 Checking roles and permissions...');

        // 確保角色存在
        $roles = ['super_admin', 'store_owner', 'customer'];
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                Role::create(['name' => $roleName, 'guard_name' => 'web']);
                $this->info('✅ Created role: ' . $roleName);
            }
        }

        // 確保基本權限存在
        $permissions = [
            'access-admin-panel',
            'manage-stores',
            'manage-users',
            'manage-orders',
            'manage-menu-items',
            'view-reports',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if (!$permission) {
                Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
                $this->info('✅ Created permission: ' . $permissionName);
            }
        }

        $this->info('✅ Roles and permissions are ready');
    }
}