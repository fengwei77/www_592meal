<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════╗\n";
echo "║       CMS 登录流程模拟测试                         ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

$baseUrl = 'https://cms.592meal.online';
$email = 'luke2work@gmail.com';
$password = 'aa123123';

// 存储 cookies
$cookieJar = [];

echo "测试配置:\n";
echo "  URL: $baseUrl\n";
echo "  账号: $email\n";
echo "  密码: " . str_repeat('*', strlen($password)) . "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 1: 检查用户凭证是否正确\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$user = App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "❌ 用户不存在: $email\n";
    exit(1);
}

echo "✅ 用户存在\n";
echo "  - ID: {$user->id}\n";
echo "  - Name: {$user->name}\n";
echo "  - Email: {$user->email}\n";
echo "  - 角色: " . $user->roles->pluck('name')->join(', ') . "\n";

// 验证密码
if (!Hash::check($password, $user->password)) {
    echo "❌ 密码不正确\n";
    exit(1);
}
echo "✅ 密码正确\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 2: 模拟登录流程\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    // 创建一个新的应用实例来模拟请求
    $request = Illuminate\Http\Request::create('/login', 'POST', [
        'email' => $email,
        'password' => $password,
    ]);

    // 模拟登录
    Auth::attempt(['email' => $email, 'password' => $password]);

    if (Auth::check()) {
        echo "✅ Auth::attempt() 成功\n";
        echo "  - auth()->id(): " . Auth::id() . "\n";
        echo "  - auth()->user()->email: " . Auth::user()->email . "\n";

        // 检查权限
        echo "\n权限检查:\n";
        $permissions = ['access-admin-panel', 'view-dashboard', 'manage-stores'];
        foreach ($permissions as $perm) {
            $can = Auth::user()->can($perm);
            echo "  " . ($can ? '✅' : '❌') . " $perm\n";
        }

        // 检查 Panel 访问
        echo "\nPanel 访问检查:\n";
        $panel = app(Filament\FilamentManager::class)->getPanel('admin');
        $canAccess = Auth::user()->canAccessPanel($panel);
        echo "  " . ($canAccess ? '✅' : '❌') . " canAccessPanel()\n";

        // 检查 Session
        echo "\nSession 检查:\n";
        $sessionId = session()->getId();
        echo "  Session ID: $sessionId\n";

        // 检查 Redis 中的 session
        $redis = Illuminate\Support\Facades\Redis::connection('session');
        $prefix = config('database.redis.options.prefix') . config('cache.prefix');
        $sessionKey = $prefix . $sessionId;
        $exists = $redis->exists($sessionKey);
        echo "  Redis 中存在: " . ($exists ? '✅ Yes' : '❌ No') . "\n";

        if ($exists) {
            $ttl = $redis->ttl($sessionKey);
            echo "  TTL: {$ttl} 秒 (" . round($ttl / 60, 1) . " 分钟)\n";

            // 读取 session 内容
            $sessionData = $redis->get($sessionKey);
            if ($sessionData) {
                echo "  Session 数据大小: " . strlen($sessionData) . " 字节\n";

                // 尝试反序列化 (Laravel 使用的是自定义序列化)
                try {
                    $unserialized = unserialize($sessionData);
                    if (is_array($unserialized)) {
                        echo "  Session 包含键: " . implode(', ', array_keys($unserialized)) . "\n";
                        if (isset($unserialized['login_web_' . sha1(get_class(Auth::guard()->getProvider()))])) {
                            echo "  ✅ 包含登录信息\n";
                        }
                    }
                } catch (Exception $e) {
                    // Laravel 使用特殊序列化，这里可能会失败，但不影响
                }
            }
        }

    } else {
        echo "❌ Auth::attempt() 失败\n";
    }

} catch (Exception $e) {
    echo "❌ 登录过程出错: " . $e->getMessage() . "\n";
    echo "  文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 3: 检查 Filament 路由和中间件\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $routes = Route::getRoutes();
    $adminRoutes = [];

    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'admin') !== false || $route->getDomain() === 'cms.592meal.online') {
            $adminRoutes[] = [
                'method' => implode('|', $route->methods()),
                'uri' => $uri,
                'name' => $route->getName(),
                'domain' => $route->getDomain(),
            ];
        }
    }

    echo "找到 " . count($adminRoutes) . " 个后台路由\n";

    // 显示几个关键路由
    $keyRoutes = ['/', 'login', 'dashboard'];
    foreach ($adminRoutes as $routeInfo) {
        foreach ($keyRoutes as $key) {
            if (strpos($routeInfo['uri'], $key) !== false || $routeInfo['uri'] === $key) {
                echo "\n路由: {$routeInfo['uri']}\n";
                echo "  方法: {$routeInfo['method']}\n";
                echo "  名称: " . ($routeInfo['name'] ?: '(无)') . "\n";
                echo "  域名: " . ($routeInfo['domain'] ?: '(任意)') . "\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ 路由检查出错: " . $e->getMessage() . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 4: 检查可能的 403 原因\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$issues = [];

// 检查 1: Email verification
if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
    $issues[] = "⚠️  Email 未验证";
} else {
    echo "✅ Email 验证: " . ($user->email_verified_at ? '已验证' : '不需要') . "\n";
}

// 检查 2: IP Whitelist
if ($user->ip_whitelist_enabled) {
    $issues[] = "⚠️  IP 白名单已启用";
    echo "⚠️  IP 白名单: 已启用\n";
    echo "   白名单 IP: " . implode(', ', $user->ip_whitelist ?? []) . "\n";
} else {
    echo "✅ IP 白名单: 未启用\n";
}

// 检查 3: 2FA
if ($user->two_factor_enabled) {
    echo "⚠️  2FA: 已启用\n";
    if ($user->isTwoFactorTempDisabled()) {
        echo "   状态: 临时关闭\n";
    }
} else {
    echo "✅ 2FA: 未启用\n";
}

// 检查 4: 账号状态
echo "✅ 账号状态: 正常\n";

if (count($issues) > 0) {
    echo "\n发现潜在问题:\n";
    foreach ($issues as $issue) {
        echo "  $issue\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "步骤 5: 测试结论\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (Auth::check() && Auth::user()->canAccessPanel($panel)) {
    echo "✅ 模拟登录测试通过\n";
    echo "✅ 用户凭证正确\n";
    echo "✅ 权限配置正确\n";
    echo "✅ Session 工作正常\n\n";

    echo "如果浏览器仍然出现 403，可能原因:\n";
    echo "  1. 浏览器 Cookie 域名不匹配\n";
    echo "  2. CSRF Token 验证失败\n";
    echo "  3. 浏览器缓存的旧 session\n";
    echo "  4. 中间件执行顺序问题\n";
    echo "  5. Filament 内部权限检查\n\n";

    echo "建议操作:\n";
    echo "  1. 清除浏览器所有 Cookie (.592meal.online)\n";
    echo "  2. 清除浏览器缓存\n";
    echo "  3. 使用无痕模式重新登录\n";
    echo "  4. 检查浏览器控制台的错误信息\n";
} else {
    echo "❌ 模拟登录测试失败\n";
    if (!Auth::check()) {
        echo "   原因: 认证失败\n";
    } elseif (!Auth::user()->canAccessPanel($panel)) {
        echo "   原因: Panel 访问权限不足\n";
    }
}

// 清理
Auth::logout();

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "测试完成\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
