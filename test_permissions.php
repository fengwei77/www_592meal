<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "測試 UserResource 權限檢查...\n";
echo str_repeat('=', 60) . "\n\n";

// 登入 Super Admin
$admin = \App\Models\User::where('email', 'admin@592meal.com')->first();

if (!$admin) {
    echo "❌ 找不到管理員\n";
    exit(1);
}

echo "✅ 測試用戶: {$admin->name} ({$admin->email})\n";
echo "   角色: " . $admin->roles->pluck('name')->join(', ') . "\n\n";

// 模擬登入
auth()->login($admin);

echo "🔍 測試權限方法:\n\n";

// 1. 測試 hasRole
echo "1. \$admin->hasRole('super_admin'): ";
$hasSuperAdmin = $admin->hasRole('super_admin');
echo ($hasSuperAdmin ? "✅ true" : "❌ false") . "\n";

// 2. 測試 auth()->user()->hasRole
echo "2. auth()->user()->hasRole('super_admin'): ";
$authHasSuperAdmin = auth()->user()?->hasRole('super_admin');
echo ($authHasSuperAdmin ? "✅ true" : "❌ false") . "\n";

// 3. 測試 UserResource::canViewAny()
echo "3. UserResource::canViewAny(): ";
try {
    $canViewAny = \App\Filament\Resources\UserResource::canViewAny();
    echo ($canViewAny ? "✅ true" : "❌ false") . "\n";
} catch (\Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}

// 4. 測試 UserResource::canAccess()
echo "4. UserResource::canAccess(): ";
try {
    $canAccess = \App\Filament\Resources\UserResource::canAccess();
    echo ($canAccess ? "✅ true" : "❌ false") . "\n";
} catch (\Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}

// 5. 測試 SecuritySettings::canAccess()
echo "5. SecuritySettings::canAccess(): ";
try {
    $canAccessSecurity = \App\Filament\Pages\SecuritySettings::canAccess();
    echo ($canAccessSecurity ? "✅ true" : "❌ false") . "\n";
} catch (\Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "\n💡 如果所有測試都通過，但導航欄中看不到「店家管理」選單，\n";
echo "   可能需要檢查：\n";
echo "   1. 是否正確登入了 Super Admin 帳號\n";
echo "   2. 瀏覽器是否需要清除快取 (Ctrl+Shift+R)\n";
echo "   3. 檢查 UserResource 的 \$navigationLabel 設定\n";
