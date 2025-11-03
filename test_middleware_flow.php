<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════╗\n";
echo "║       中间件和授权流程测试                         ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

$email = 'luke2work@gmail.com';
$password = 'aa123123';

// 登录
Auth::attempt(['email' => $email, 'password' => $password]);
$user = Auth::user();

if (!$user) {
    echo "❌ 登录失败\n";
    exit(1);
}

echo "✅ 登录成功: {$user->email}\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 1: 检查 Filament Panel 配置\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $filamentManager = app(Filament\FilamentManager::class);
    $panel = $filamentManager->getPanel('admin');

    echo "Panel ID: " . $panel->getId() . "\n";
    echo "Panel 域名: " . $panel->getDomain() . "\n";
    echo "Panel 路径: " . $panel->getPath() . "\n\n";

    echo "Panel 中间件:\n";
    foreach ($panel->getMiddleware() as $middleware) {
        echo "  - " . (is_string($middleware) ? $middleware : get_class($middleware)) . "\n";
    }

    echo "\nPanel 认证中间件:\n";
    foreach ($panel->getAuthMiddleware() as $middleware) {
        echo "  - " . (is_string($middleware) ? $middleware : get_class($middleware)) . "\n";
    }

    echo "\n✅ Panel 配置检查完成\n";
} catch (Exception $e) {
    echo "❌ Panel 检查失败: " . $e->getMessage() . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 2: 测试 Resource 访问权限\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$resources = [
    'App\\Filament\\Resources\\Stores\\StoreResource' => 'view_store',
    'App\\Filament\\Resources\\UserResource' => 'view_users',
    'App\\Filament\\Resources\\RoleResource' => 'view_roles',
    'App\\Filament\\Resources\\PermissionResource' => 'view_permissions',
    'App\\Filament\\Resources\\Menu\\MenuCategoryResource' => 'view_menu_categories',
    'App\\Filament\\Resources\\Menu\\MenuItemResource' => 'view_menu_items',
];

echo "测试用户: {$user->name} ({$user->email})\n";
echo "用户角色: " . $user->roles->pluck('name')->join(', ') . "\n\n";

foreach ($resources as $resourceClass => $permission) {
    if (!class_exists($resourceClass)) {
        echo "⚠️  {$resourceClass}: 类不存在\n";
        continue;
    }

    try {
        $canViewAny = $resourceClass::canViewAny();
        $hasPermission = $user->can($permission);
        $isSuperAdmin = $user->hasRole('super_admin');

        $status = $canViewAny ? '✅' : '❌';
        $resourceName = class_basename($resourceClass);

        echo "{$status} {$resourceName}:\n";
        echo "   canViewAny(): " . ($canViewAny ? 'true' : 'false') . "\n";
        echo "   has permission '{$permission}': " . ($hasPermission ? 'true' : 'false') . "\n";
        echo "   is super_admin: " . ($isSuperAdmin ? 'true' : 'false') . "\n\n";

    } catch (Exception $e) {
        echo "❌ {$resourceClass}: 检查失败 - " . $e->getMessage() . "\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 3: 检查 Gate 定义\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$gates = [
    'access-admin-panel',
    'manage-stores',
    'manage-users',
    'manage-orders',
    'manage-menu-items',
    'view-reports',
    'view-dashboard',
];

foreach ($gates as $gate) {
    $can = Gate::allows($gate);
    $status = $can ? '✅' : '❌';
    echo "{$status} Gate '{$gate}': " . ($can ? 'ALLOW' : 'DENY') . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 4: 检查 Policy\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// 检查 Store Policy
if (class_exists('App\\Models\\Store') && class_exists('App\\Policies\\StorePolicy')) {
    $store = App\Models\Store::first();

    if ($store) {
        echo "测试 Store (ID: {$store->id}, Name: {$store->name})\n\n";

        $policyMethods = ['viewAny', 'view', 'create', 'update', 'delete'];

        foreach ($policyMethods as $method) {
            try {
                if ($method === 'viewAny' || $method === 'create') {
                    $can = Gate::allows($method, App\Models\Store::class);
                } else {
                    $can = Gate::allows($method, $store);
                }

                $status = $can ? '✅' : '❌';
                echo "{$status} Policy::{$method}(): " . ($can ? 'ALLOW' : 'DENY') . "\n";

            } catch (Exception $e) {
                echo "❌ Policy::{$method}(): ERROR - " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "⚠️  没有找到 Store 记录\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 5: 测试完整的 HTTP 请求模拟\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    // 创建一个模拟的 HTTP 请求
    $request = Illuminate\Http\Request::create(
        'https://cms.592meal.online/',
        'GET',
        [],
        [], // cookies
        [], // files
        [
            'HTTP_HOST' => 'cms.592meal.online',
            'HTTPS' => 'on',
        ]
    );

    // 模拟 session
    $session = new Illuminate\Session\Store(
        'session',
        new Illuminate\Session\ArraySessionHandler(120),
        Illuminate\Support\Str::random(40)
    );
    $session->put('_token', Illuminate\Support\Str::random(40));
    $session->put('login_web_' . sha1(get_class(Auth::guard()->getProvider())), $user->id);
    $request->setLaravelSession($session);

    echo "✅ 模拟 HTTP 请求创建成功\n";
    echo "   URL: {$request->url()}\n";
    echo "   Host: {$request->getHost()}\n";
    echo "   Session ID: {$session->getId()}\n";
    echo "   Authenticated User: " . Auth::id() . "\n";

} catch (Exception $e) {
    echo "❌ HTTP 请求模拟失败: " . $e->getMessage() . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 6: 检查可能的访问障碍\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$issues = [];

// 检查 1: Email 验证
if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
    $issues[] = "❌ Email 未验证";
} else {
    echo "✅ Email 验证: 通过\n";
}

// 检查 2: canAccessPanel
$canAccessPanel = $user->canAccessPanel($panel);
if (!$canAccessPanel) {
    $issues[] = "❌ canAccessPanel() 返回 false";
} else {
    echo "✅ canAccessPanel(): 通过\n";
}

// 检查 3: 角色检查
$hasValidRole = $user->hasRole(['super_admin', 'store_owner']);
if (!$hasValidRole) {
    $issues[] = "❌ 没有有效的角色";
} else {
    echo "✅ 角色检查: 通过\n";
}

// 检查 4: access-admin-panel Gate
$canAccessAdminPanelGate = Gate::allows('access-admin-panel');
if (!$canAccessAdminPanelGate) {
    $issues[] = "❌ access-admin-panel Gate 拒绝";
} else {
    echo "✅ access-admin-panel Gate: 通过\n";
}

// 检查 5: 2FA 状态
if ($user->two_factor_enabled) {
    if ($user->isTwoFactorTempDisabled()) {
        echo "⚠️  2FA: 临时关闭中\n";
    } else {
        echo "⚠️  2FA: 已启用 (可能需要验证)\n";
    }
} else {
    echo "✅ 2FA: 未启用\n";
}

// 检查 6: IP 白名单
if ($user->ip_whitelist_enabled) {
    $issues[] = "⚠️  IP 白名单已启用";
    echo "⚠️  IP 白名单: 已启用\n";
} else {
    echo "✅ IP 白名单: 未启用\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "测试总结\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (count($issues) > 0) {
    echo "\n发现以下问题:\n";
    foreach ($issues as $issue) {
        echo "  {$issue}\n";
    }
    echo "\n";
} else {
    echo "\n✅ 所有检查都通过！\n\n";
    echo "如果浏览器仍然出现 403，问题可能在:\n";
    echo "  1. 浏览器 Cookie/Session 不匹配\n";
    echo "  2. CSRF Token 验证失败\n";
    echo "  3. 浏览器端的 JavaScript 错误\n";
    echo "  4. 需要启用浏览器开发者工具查看实际的请求/响应\n";
    echo "  5. 检查 storage/logs/laravel.log 中的错误日志\n";
}

// 清理
Auth::logout();

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "测试完成\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
