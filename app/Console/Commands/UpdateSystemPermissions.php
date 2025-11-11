<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Update System Permissions Command
 *
 * 更新系統管理相關權限
 */
class UpdateSystemPermissions extends Command
{
    protected $signature = 'system:update-permissions';

    protected $description = 'Update system management permissions for Super Admin';

    public function handle()
    {
        $this->info('Updating system management permissions...');

        $superAdmin = Role::where('name', 'super_admin')->first();

        if (!$superAdmin) {
            $this->error('Super Admin role not found!');
            return 1;
        }

        $permissions = [
            'access_system_management',
            'view_system_statistics',
            'manage_orders',
            'manual_payment_processing',
        ];

        foreach ($permissions as $permName) {
            $permission = Permission::firstOrCreate(['name' => $permName]);
            $superAdmin->givePermissionTo($permission);
            $this->line("✓ Permission '{$permName}' assigned to Super Admin");
        }

        $this->info('System management permissions updated successfully!');
        return 0;
    }
}