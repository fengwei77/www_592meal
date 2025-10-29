/**
 * 592Meal Service Worker
 * 處理推播通知和離線快取
 */

const CACHE_NAME = '592meal-v1';
const CACHE_URLS = [
    '/',
];

/**
 * Service Worker 安裝事件
 */
self.addEventListener('install', event => {
    console.log('[Service Worker] 正在安裝...');

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] 快取已開啟');
                return cache.addAll(CACHE_URLS);
            })
            .catch(error => {
                console.error('[Service Worker] 快取失敗:', error);
            })
    );

    // 強制跳過等待，立即啟動新的 Service Worker
    self.skipWaiting();
});

/**
 * Service Worker 啟動事件
 */
self.addEventListener('activate', event => {
    console.log('[Service Worker] 正在啟動...');

    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    // 清除舊版本的快取
                    if (cacheName !== CACHE_NAME) {
                        console.log('[Service Worker] 刪除舊快取:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );

    // 確保 Service Worker 立即接管所有頁面
    return self.clients.claim();
});

/**
 * 推播通知事件處理
 */
self.addEventListener('push', event => {
    console.log('[Service Worker] 收到推播通知');

    if (!event.data) {
        console.warn('[Service Worker] 推播通知沒有資料');
        return;
    }

    try {
        const data = event.data.json();
        console.log('[Service Worker] 推播資料:', data);

        const options = {
            body: data.body || '您有新的訂單更新',
            icon: data.icon || '/images/icon-192x192.png',
            badge: data.badge || '/images/badge-72x72.png',
            vibrate: [100, 50, 100],
            data: data.data || {},
            actions: [
                {
                    action: 'view',
                    title: '查看訂單',
                    icon: '/images/view.png'
                },
                {
                    action: 'close',
                    title: '關閉',
                    icon: '/images/close.png'
                }
            ],
            requireInteraction: true,
            silent: false,
            tag: data.tag || 'default',
            renotify: true
        };

        event.waitUntil(
            self.registration.showNotification(data.title || '592Meal 通知', options)
        );
    } catch (error) {
        console.error('[Service Worker] 解析推播資料失敗:', error);
    }
});

/**
 * 通知點擊事件處理
 */
self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] 通知被點擊:', event.action);

    event.notification.close();

    if (event.action === 'close') {
        // 用戶點擊關閉按鈕
        return;
    }

    // 處理「查看訂單」動作或點擊通知本身
    if (event.action === 'view' || !event.action) {
        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true })
                .then(clientList => {
                    const orderId = event.notification.data.order_id;
                    const url = orderId ? `/orders/${orderId}` : '/orders';

                    // 檢查是否已有開啟的視窗
                    for (const client of clientList) {
                        if (client.url.includes('/orders') && 'focus' in client) {
                            return client.focus().then(client => {
                                // 可選：發送訊息給頁面更新內容
                                if ('postMessage' in client) {
                                    client.postMessage({
                                        type: 'NAVIGATE_TO_ORDER',
                                        orderId: orderId
                                    });
                                }
                                return client;
                            });
                        }
                    }

                    // 沒有開啟的視窗，開啟新視窗
                    if (clients.openWindow) {
                        return clients.openWindow(url);
                    }
                })
                .catch(error => {
                    console.error('[Service Worker] 開啟視窗失敗:', error);
                })
        );
    }
});

/**
 * 通知關閉事件處理
 */
self.addEventListener('notificationclose', event => {
    console.log('[Service Worker] 通知被關閉:', event.notification.tag);

    // 可選：記錄用戶關閉通知的行為
    // fetch('/api/push/notification-closed', {
    //     method: 'POST',
    //     body: JSON.stringify({
    //         tag: event.notification.tag,
    //         timestamp: Date.now()
    //     })
    // });
});

/**
 * Fetch 事件處理（可選的離線支援）
 */
self.addEventListener('fetch', event => {
    // 暫時不處理 fetch 事件，避免影響正常請求
    // 可以在需要離線功能時再實作
    return;
});

/**
 * 訊息事件處理
 */
self.addEventListener('message', event => {
    console.log('[Service Worker] 收到訊息:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('[Service Worker] 腳本已載入');
