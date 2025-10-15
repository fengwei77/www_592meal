<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 建立 Super Admin 角色
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // 建立系統管理員
        $superAdmin = User::firstOrCreate(
            [
                'email' => 'admin@592meal.com',
            ],
            [
                'name' => '系統管理員',
                'password' => Hash::make('Admin@592meal2024'),
                'email_verified_at' => now(),
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
                'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
                'ip_whitelist_enabled' => true,
                'ip_whitelist' => json_encode([
                    '127.0.0.1',
                    '192.168.1.1',
                    '::1'
                ]),
                'is_active' => true,
            ]
        );

        // 分配角色
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('系統管理員帳號已建立');
        $this->command->info('Email: admin@592meal.com');
        $this->command->info('Password: Admin@592meal2024');
    }
}