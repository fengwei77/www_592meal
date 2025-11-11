<?php

/**
 * 清理失效的推播訂閱
 * 這個腳本可以通過瀏覽器訪問來清理失效的推播訂閱
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

try {
    echo "開始清理失效的推播訂閱...\n";

    // 查找所有超過 30 天未使用的訂閱
    $expiredSubscriptions = PushSubscription::where('last_used_at', '<', now()->subDays(30))
        ->where('is_active', true)
        ->get();

    echo "找到 {$expiredSubscriptions->count()} 個過期的訂閱\n";

    foreach ($expiredSubscriptions as $subscription) {
        $subscription->update(['is_active' => false]);
        echo "已停用過期訂閱 ID: {$subscription->id}, 客戶: {$subscription->customer_id}\n";
    }

    // 查找所有以 simulation:// 開頭的模擬訂閱
    $simulationSubscriptions = PushSubscription::where('endpoint', 'like', 'simulation://%')
        ->where('is_active', true)
        ->get();

    echo "找到 {$simulationSubscriptions->count()} 個模擬訂閱\n";

    foreach ($simulationSubscriptions as $subscription) {
        $subscription->update(['is_active' => false]);
        echo "已停用模擬訂閱 ID: {$subscription->id}, 客戶: {$subscription->customer_id}\n";
    }

    // 統計當前的訂閱狀態
    $totalSubscriptions = PushSubscription::count();
    $activeSubscriptions = PushSubscription::where('is_active', true)->count();
    $inactiveSubscriptions = $totalSubscriptions - $activeSubscriptions;

    echo "\n清理完成！\n";
    echo "總訂閱數: {$totalSubscriptions}\n";
    echo "啟用訂閱數: {$activeSubscriptions}\n";
    echo "停用訂閱數: {$inactiveSubscriptions}\n";

    Log::info('手動清理失效推播訂閱完成', [
        'expired_count' => $expiredSubscriptions->count(),
        'simulation_count' => $simulationSubscriptions->count(),
        'total_after_cleanup' => $activeSubscriptions
    ]);

} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    Log::error('清理失效推播訂閱失敗', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n執行完成！\n";