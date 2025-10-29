@extends('layouts.app')

@section('title', '推播通知設定')

@push('styles')
<style>
/* Toggle Switch Styles */
.toggle-checkbox {
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #3b82f6;
    transition: all 0.2s ease-in-out;
    border-color: #3b82f6;
}

.toggle-checkbox:checked {
    right: 0px;
    transform: translateX(24px);
    background-color: #ffffff;
    border-color: #3b82f6;
}

.toggle-checkbox:checked + .toggle-label {
    background-color: #3b82f6;
}

.toggle-checkbox:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* 確保在未選中狀態下背景是灰色 */
.toggle-checkbox:not(:checked) {
    background-color: #ffffff;
    border-color: #d1d5db;
    transform: translateX(0);
}

.toggle-checkbox:not(:checked) + .toggle-label {
    background-color: #d1d5db;
}

/* Toast 通知樣式 */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-left: 4px solid;
    min-width: 300px;
    animation: slideIn 0.3s ease-out;
}

.toast.success {
    border-left-color: #10b981;
    color: #065f46;
}

.toast.error {
    border-left-color: #ef4444;
    color: #991b1b;
}

.toast.info {
    border-left-color: #3b82f6;
    color: #1e40af;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <!-- 頁面標題 -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">推播通知設定</h1>
        <p class="mt-2 text-gray-600">管理您的訂單推播通知偏好與訂閱裝置</p>
    </div>

    <!-- 通知偏好設定 -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            通知偏好
        </h2>

        <div class="space-y-4">
            <!-- 訂單確認通知 -->
            <div class="flex items-start justify-between py-3 border-b border-gray-200">
                <div class="flex-1">
                    <label for="notification_confirmed" class="font-medium text-gray-900 block">
                        訂單確認通知
                    </label>
                    <p class="text-sm text-gray-600 mt-1">店家接受訂單時通知您</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox"
                               id="notification_confirmed"
                               name="notification_confirmed"
                               {{ $customer->notification_confirmed ? 'checked' : '' }}
                               class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-200 ease-in notification-toggle"
                               data-type="confirmed">
                        <label for="notification_confirmed" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-all duration-200 ease-in"></label>
                    </label>
                </div>
            </div>

            <!-- 製作中通知 -->
            <div class="flex items-start justify-between py-3 border-b border-gray-200">
                <div class="flex-1">
                    <label for="notification_preparing" class="font-medium text-gray-900 block">
                        製作中通知
                    </label>
                    <p class="text-sm text-gray-600 mt-1">訂單開始製作時通知您</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox"
                               id="notification_preparing"
                               name="notification_preparing"
                               {{ $customer->notification_preparing ? 'checked' : '' }}
                               class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-200 ease-in notification-toggle"
                               data-type="preparing">
                        <label for="notification_preparing" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-all duration-200 ease-in"></label>
                    </label>
                </div>
            </div>

            <!-- 完成通知 -->
            <div class="flex items-start justify-between py-3">
                <div class="flex-1">
                    <label for="notification_ready" class="font-medium text-gray-900 block">
                        完成通知
                    </label>
                    <p class="text-sm text-gray-600 mt-1">訂單完成可取餐時通知您</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox"
                               id="notification_ready"
                               name="notification_ready"
                               {{ $customer->notification_ready ? 'checked' : '' }}
                               class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-200 ease-in notification-toggle"
                               data-type="ready">
                        <label for="notification_ready" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-all duration-200 ease-in"></label>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- 推播訂閱管理 -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            訂閱裝置
        </h2>

        @if($hasActiveSubscriptions)
            <p class="text-gray-600 mb-4">以下是已啟用推播通知的裝置：</p>

            <div class="space-y-3">
                @foreach($subscriptions as $subscription)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center space-x-3">
                            <!-- 裝置圖示 -->
                            @if($subscription['device_type'] === 'mobile')
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            @else
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            @endif

                            <!-- 裝置資訊 -->
                            <div>
                                <div class="font-medium text-gray-900">{{ $subscription['user_agent'] }}</div>
                                <div class="text-sm text-gray-500">
                                    最後使用：{{ $subscription['last_used_at'] }}
                                    · 訂閱日期：{{ $subscription['created_at'] }}
                                </div>
                            </div>
                        </div>

                        <!-- 移除按鈕 -->
                        <button class="remove-subscription-btn text-red-600 hover:text-red-800 font-medium text-sm px-3 py-1 rounded hover:bg-red-50 transition"
                                data-subscription-id="{{ $subscription['id'] }}">
                            移除
                        </button>
                    </div>
                @endforeach
            </div>

            <!-- 操作按鈕 -->
            <div class="mt-6 flex space-x-3">
                <button id="sendTestBtn"
                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                    發送測試通知
                </button>
                <button id="removeAllBtn"
                        class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition font-medium">
                    取消所有訂閱
                </button>
            </div>
        @else
            <!-- 沒有訂閱時顯示 -->
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-gray-600 mb-4">您目前沒有啟用推播通知的裝置</p>
                <button id="enablePushBtn"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                    在此裝置上啟用推播
                </button>
            </div>
        @endif
    </div>

    <!-- 說明資訊 -->
    <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
        <h3 class="font-semibold text-blue-900 mb-2 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            關於推播通知
        </h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• 推播通知會在訂單狀態變更時即時通知您</li>
            <li>• 即使瀏覽器關閉也能收到通知（需要開啟權限）</li>
            <li>• 您可以在不同裝置上啟用推播通知</li>
            <li>• 推播通知需要 HTTPS 連線</li>
            <li>• 如果瀏覽器封鎖了推播權限，請到瀏覽器設定中手動開啟</li>
        </ul>
    </div>
</div>

<!-- Toast 通知容器 -->
<div class="toast-container" id="toast-container"></div>

@endsection

@push('scripts')
<script>
// 等待 DOM 完全載入後再定義函數
document.addEventListener('DOMContentLoaded', function() {
    // 確保 axios 已載入並設定 CSRF Token
    if (typeof axios !== 'undefined') {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    } else {
        console.error('axios 未載入，請確認 app.js 是否正確編譯');
    }
    // 確保推播通知相關函數在頁面載入時立即可用
    window.enablePushNotifications = async function() {
    if (!window.pushManager) {
        alert('您的瀏覽器不支援推播通知');
        return;
    }

    // 檢查是否為開發環境
    const isDevEnvironment = window.location.hostname === 'localhost' ||
                           window.location.hostname === '127.0.0.1' ||
                           window.location.hostname.includes('.test') ||
                           window.location.protocol === 'http:';

    try {
        const initialized = await window.pushManager.init();
        if (!initialized) {
            alert('推播通知初始化失敗，請確認您的瀏覽器支援此功能');
            return;
        }

        if (isDevEnvironment) {
            // 開發環境中的特殊處理
            const customerId = {{ $customer->id }};

            // 模擬權限請求
            const permission = await window.pushManager.requestPermission();

            if (permission) {
                // 顯示開發環境的特殊說明
                const result = confirm(
                    '推播通知模擬成功！\n\n⚠️ 這是在開發環境中的模擬：\n' +
                    '• 實際的推播通知功能需要 HTTPS 連線\n' +
                    '• 開發環境不支持 Service Worker\n' +
                    '• 在正式環境中將正常工作\n\n' +
                    '要繼續模擬設定嗎？'
                );

                if (result) {
                    // 模擬訂閱成功
                    await simulateSubscription(customerId);
                }
            } else {
                alert('推播通知權限被拒絕');
            }
        } else {
            // 生產環境的正常流程
            const customerId = {{ $customer->id }};
            const subscribed = await window.pushManager.subscribe(customerId);

            if (subscribed) {
                showToast('推播通知已啟用！', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert('推播通知啟用失敗，請確認已授予推播權限');
            }
        }
    } catch (error) {
        console.error('啟用推播失敗:', error);
        alert('啟用推播通知時發生錯誤：' + error.message);
    }
    };

    // 模擬訂閱功能（開發環境）
    async function simulateSubscription(customerId) {
        try {
            // 模擬 API 請求
            const response = await axios.post('/customer/notifications/simulate-subscription', {
                customer_id: customerId,
                device_info: navigator.userAgent,
                platform: navigator.platform,
                is_simulation: true
            });

            if (response.data.success) {
                showToast('推播通知模擬訂閱成功！', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('模擬訂閱失敗', 'error');
            }
        } catch (error) {
            console.error('模擬訂閱失敗:', error);
            showToast('模擬訂閱失敗：' + (error.response?.data?.message || '網路錯誤'), 'error');
        }
    }

    window.removeSubscription = async function(subscriptionId) {
    if (!confirm('確定要移除此裝置的推播訂閱嗎？')) {
        return;
    }

    try {
        const response = await axios.delete(`/customer/notifications/subscriptions/${subscriptionId}`);

        if (response.data.success) {
            showToast('訂閱已移除', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('移除失敗，請稍後再試', 'error');
        }
    } catch (error) {
        console.error('移除訂閱失敗:', error);
        showToast('移除失敗：' + (error.response?.data?.message || '網路錯誤'), 'error');
    }
    };

    window.removeAllSubscriptions = async function() {
    if (!confirm('確定要取消所有裝置的推播訂閱嗎？此操作無法復原。')) {
        return;
    }

    try {
        const response = await axios.post('/customer/notifications/subscriptions/remove-all');

        if (response.data.success) {
            showToast(response.data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('取消訂閱失敗，請稍後再試', 'error');
        }
    } catch (error) {
        console.error('取消所有訂閱失敗:', error);
        showToast('取消訂閱失敗：' + (error.response?.data?.message || '網路錯誤'), 'error');
    }
    };

    window.sendTestNotification = async function() {
    try {
        const response = await axios.post('/customer/notifications/test');

        if (response.data.success) {
            showToast('測試通知已發送，請檢查您的通知', 'success');
        } else {
            showToast(response.data.message || '發送失敗', 'error');
        }
    } catch (error) {
        console.error('發送測試通知失敗:', error);
        showToast('發送失敗：' + (error.response?.data?.message || '網路錯誤'), 'error');
    }
    };

    // 更新通知偏好
    document.querySelectorAll('.notification-toggle').forEach(toggle => {
    toggle.addEventListener('change', async function() {
        const type = this.dataset.type;
        const enabled = this.checked;

        try {
            const response = await axios.post('/customer/notifications/preferences', {
                [`notification_${type}`]: enabled
            });

            if (response.data.success) {
                showToast('設定已更新', 'success');
            } else {
                showToast('更新失敗，請稍後再試', 'error');
                // 還原開關狀態
                this.checked = !enabled;
            }
        } catch (error) {
            console.error('更新偏好失敗:', error);
            showToast('更新失敗：' + (error.response?.data?.message || '網路錯誤'), 'error');
            // 還原開關狀態
            this.checked = !enabled;
        }
        });
    });

    // 顯示 Toast 訊息
    function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) {
        alert(message);
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    // 自動移除 Toast
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
        }, 3000);
    }

    // 添加按鈕事件監聽器
    const enablePushBtn = document.getElementById('enablePushBtn');
    if (enablePushBtn) {
        enablePushBtn.addEventListener('click', window.enablePushNotifications);
    }

    const sendTestBtn = document.getElementById('sendTestBtn');
    if (sendTestBtn) {
        sendTestBtn.addEventListener('click', window.sendTestNotification);
    }

    const removeAllBtn = document.getElementById('removeAllBtn');
    if (removeAllBtn) {
        removeAllBtn.addEventListener('click', window.removeAllSubscriptions);
    }

    // 移除訂閱按鈕
    document.querySelectorAll('.remove-subscription-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const subscriptionId = this.dataset.subscriptionId;
            window.removeSubscription(subscriptionId);
        });
    });
}); // 結束 DOMContentLoaded
</script>
@endpush
