<?php

// 測試 SendBackendLoginUrl 類是否可以正確載入

require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "測試 SendBackendLoginUrl 類載入...\n";

    // 測試完整命名空間
    $notification = new \App\Notifications\SendBackendLoginUrl();
    echo "✅ SendBackendLoginUrl 類載入成功！\n";
    echo "類名：" . get_class($notification) . "\n";

    // 檢查類的方法
    if (method_exists($notification, 'via')) {
        echo "✅ via() 方法存在\n";
    }

    if (method_exists($notification, 'toMail')) {
        echo "✅ toMail() 方法存在\n";
    }

    echo "\n測試完成！類可以正常載入。\n";

} catch (Exception $e) {
    echo "❌ 錯誤：" . $e->getMessage() . "\n";
    echo "堆疊追蹤：\n" . $e->getTraceAsString() . "\n";
}