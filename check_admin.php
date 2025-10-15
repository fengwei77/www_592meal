<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "檢查管理員帳號狀態...\n";
echo str_repeat('=', 50) . "\n\n";

// 檢查用戶
$admin = \App\Models\User::where('email', 'admin@592meal.com')->first();

if (!$admin) {
    echo "❌ 找不到用戶 admin@592meal.com\n";
    echo "   需要執行 Seeder: php artisan db:seed --class=SuperAdminSeeder\n";
    exit(1);
}

echo "✅ 用戶資料:\n";
echo "   姓名: {$admin->name}\n";
echo "   Email: {$admin->email}\n";
echo "   ID: {$admin->id}\n\n";

// 檢查角色
echo "📋 角色資訊:\n";
$roles = $admin->roles;
if ($roles->isEmpty()) {
    echo "   ❌ 沒有分配任何角色\n";
} else {
    foreach ($roles as $role) {
        echo "   ✅ {$role->name}\n";
    }
}

echo "\n";
echo "🔍 權限檢查:\n";
echo "   Has super_admin role: " . ($admin->hasRole('super_admin') ? '✅ YES' : '❌ NO') . "\n";
echo "   Can access UserResource: " . ($admin->hasRole('super_admin') ? '✅ YES' : '❌ NO') . "\n";

echo "\n";
echo "📊 系統中所有角色:\n";
$allRoles = \Spatie\Permission\Models\Role::all();
if ($allRoles->isEmpty()) {
    echo "   ❌ 沒有任何角色（需要執行 migration 和 seeder）\n";
} else {
    foreach ($allRoles as $role) {
        $userCount = \App\Models\User::role($role->name)->count();
        echo "   - {$role->name} ({$userCount} 位用戶)\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
