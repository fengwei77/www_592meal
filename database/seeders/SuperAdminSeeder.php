<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SuperAdminSeeder
 *
 * 建立預設的超級管理員帳號
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 檢查是否已存在 super admin
        if (User::role('super_admin')->exists()) {
            $this->command->warn('⚠ Super Admin already exists, skipping...');
            return;
        }

        // 建立超級管理員
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@592meal.com',
            'password' => Hash::make('password'), // ⚠ 正式環境請務必修改密碼
            'email_verified_at' => now(),
        ]);

        // 指派 super_admin 角色
        $admin->assignRole('super_admin');

        $this->command->info('✅ Super Admin created successfully!');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('  Email: admin@592meal.com');
        $this->command->info('  Password: password');
        $this->command->warn('');
        $this->command->warn('⚠ WARNING: Please change the password in production!');
    }
}
