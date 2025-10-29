@extends('layouts.app')

@section('title', '推播通知調試')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">推播通知調試頁面</h1>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">系統狀態檢查</h2>

        <div class="space-y-4">
            <div>
                <strong>用戶登入狀態:</strong>
                @if(session('line_logged_in'))
                    <span class="text-green-600">✅ 已登入</span>
                @else
                    <span class="text-red-600">❌ 未登入</span>
                @endif
            </div>

            <div>
                <strong>顧客記錄:</strong>
                @if(isset($customer))
                    <span class="text-green-600">✅ 存在 (ID: {{ $customer->id }})</span>
                @else
                    <span class="text-red-600">❌ 不存在</span>
                @endif
            </div>

            <div>
                <strong>JavaScript 載入狀態:</strong>
                <span id="js-status" class="text-yellow-600">檢查中...</span>
            </div>

            <div>
                <strong>PushNotificationManager:</strong>
                <span id="push-manager-status" class="text-yellow-600">檢查中...</span>
            </div>

            <div>
                <strong>VAPID 公鑰:</strong>
                <span id="vapid-status" class="text-yellow-600">檢查中...</span>
            </div>

            <div>
                <strong>Service Worker:</strong>
                <span id="sw-status" class="text-yellow-600">檢查中...</span>
            </div>

            <div>
                <strong>推播權限:</strong>
                <span id="permission-status" class="text-yellow-600">檢查中...</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">操作測試</h2>

        <div class="space-y-4">
            <button onclick="testPushManager()"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                測試 PushNotificationManager
            </button>

            <button onclick="requestPermission()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                請求推播權限
            </button>

            <button onclick="subscribeToPush()"
                    class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                訂閱推播通知
            </button>

            <button onclick="sendTestNotification()"
                    class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 transition">
                發送測試通知
            </button>
        </div>
    </div>

    <div class="bg-blue-50 rounded-lg p-6">
        <h3 class="font-semibold text-blue-900 mb-2">調試日誌</h3>
        <div id="debug-log" class="text-sm text-gray-700 font-mono bg-white p-4 rounded border border-blue-200 h-64 overflow-y-auto">
            等待操作...
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function log(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const logElement = document.getElementById('debug-log');
    const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'blue';
    logElement.innerHTML += `<div style="color: ${color}">[${timestamp}] ${message}</div>`;
    logElement.scrollTop = logElement.scrollHeight;
    console.log(`[Debug] ${message}`);
}

// 檢查系統狀態
document.addEventListener('DOMContentLoaded', function() {
    log('頁面載入完成', 'success');

    // 檢查用戶資訊
    if (window.currentUser) {
        log('用戶資訊已載入: ' + JSON.stringify(window.currentUser), 'success');
        document.getElementById('js-status').innerHTML = '<span class="text-green-600">✅ 已載入用戶資訊</span>';
    } else {
        log('用戶資訊未載入', 'error');
        document.getElementById('js-status').innerHTML = '<span class="text-red-600">❌ 未載入用戶資訊</span>';
    }

    // 檢查 PushNotificationManager
    if (window.pushManager) {
        log('PushNotificationManager 已載入', 'success');
        document.getElementById('push-manager-status').innerHTML = '<span class="text-green-600">✅ 已載入</span>';
    } else {
        log('PushNotificationManager 未載入', 'error');
        document.getElementById('push-manager-status').innerHTML = '<span class="text-red-600">❌ 未載入</span>';
    }

    // 檢查 VAPID 公鑰
    if (window.vapidPublicKey) {
        log('VAPID 公鑰已設定: ' + window.vapidPublicKey.substring(0, 20) + '...', 'success');
        document.getElementById('vapid-status').innerHTML = '<span class="text-green-600">✅ 已設定</span>';
    } else {
        log('VAPID 公鑰未設定', 'error');
        document.getElementById('vapid-status').innerHTML = '<span class="text-red-600">❌ 未設定</span>';
    }

    // 檢查推播支援
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        log('瀏覽器支援推播通知', 'success');
        checkServiceWorker();
        checkPermission();
    } else {
        log('瀏覽器不支援推播通知', 'error');
        document.getElementById('sw-status').innerHTML = '<span class="text-red-600">❌ 不支援</span>';
        document.getElementById('permission-status').innerHTML = '<span class="text-red-600">❌ 不支援</span>';
    }
});

async function checkServiceWorker() {
    try {
        const registration = await navigator.serviceWorker.getRegistration();
        if (registration) {
            log('Service Worker 已註冊: ' + registration.scope, 'success');
            document.getElementById('sw-status').innerHTML = '<span class="text-green-600">✅ 已註冊</span>';
        } else {
            log('Service Worker 未註冊', 'error');
            document.getElementById('sw-status').innerHTML = '<span class="text-yellow-600">⚠️ 未註冊</span>';
        }
    } catch (error) {
        log('檢查 Service Worker 錯誤: ' + error.message, 'error');
        document.getElementById('sw-status').innerHTML = '<span class="text-red-600">❌ 錯誤</span>';
    }
}

function checkPermission() {
    const permission = Notification.permission;
    log('推播權限狀態: ' + permission, 'info');

    let statusText = '';
    let statusClass = '';

    switch(permission) {
        case 'granted':
            statusText = '✅ 已授權';
            statusClass = 'text-green-600';
            break;
        case 'denied':
            statusText = '❌ 已拒絕';
            statusClass = 'text-red-600';
            break;
        case 'default':
            statusText = '⚠️ 未設定';
            statusClass = 'text-yellow-600';
            break;
    }

    document.getElementById('permission-status').innerHTML = `<span class="${statusClass}">${statusText}</span>`;
}

async function testPushManager() {
    log('開始測試 PushNotificationManager...', 'info');

    if (!window.pushManager) {
        log('PushNotificationManager 未載入', 'error');
        return;
    }

    try {
        const initialized = await window.pushManager.init();
        if (initialized) {
            log('PushNotificationManager 初始化成功', 'success');
        } else {
            log('PushNotificationManager 初始化失敗', 'error');
        }
    } catch (error) {
        log('PushNotificationManager 初始化錯誤: ' + error.message, 'error');
    }
}

async function requestPermission() {
    log('請求推播權限...', 'info');

    try {
        const permission = await Notification.requestPermission();
        log('權限請求結果: ' + permission, 'success');
        checkPermission();
    } catch (error) {
        log('請求權限錯誤: ' + error.message, 'error');
    }
}

async function subscribeToPush() {
    log('開始訂閱推播通知...', 'info');

    if (!window.currentUser) {
        log('用戶未登入，無法訂閱', 'error');
        return;
    }

    if (!window.pushManager) {
        log('PushNotificationManager 未載入', 'error');
        return;
    }

    try {
        const subscribed = await window.pushManager.subscribe(window.currentUser.id);
        if (subscribed) {
            log('推播訂閱成功', 'success');
        } else {
            log('推播訂閱失敗', 'error');
        }
    } catch (error) {
        log('訂閱錯誤: ' + error.message, 'error');
    }
}

async function sendTestNotification() {
    log('發送測試通知...', 'info');

    try {
        const response = await axios.post('/customer/notifications/test');
        if (response.data.success) {
            log('測試通知發送成功', 'success');
        } else {
            log('測試通知發送失敗: ' + (response.data.message || '未知錯誤'), 'error');
        }
    } catch (error) {
        log('發送測試通知錯誤: ' + (error.response?.data?.message || error.message), 'error');
    }
}
</script>
@endpush