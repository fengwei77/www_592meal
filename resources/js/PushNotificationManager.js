/**
 * PushNotificationManager
 * 管理瀏覽器推播通知的訂閱與權限
 */
class PushNotificationManager {
    constructor() {
        this.subscription = null;
        this.registration = null;
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        this.vapidPublicKey = window.vapidPublicKey || null;
    }

    /**
     * 初始化推播通知管理器
     * @returns {Promise<boolean>}
     */
    async init() {
        if (!this.isSupported) {
            console.warn('[Push] 瀏覽器不支援推播通知');
            return false;
        }

        if (!this.vapidPublicKey) {
            console.error('[Push] VAPID 公鑰未設定');
            return false;
        }

        try {
            // 檢查是否為開發環境
            const isDevEnvironment = window.location.hostname === 'localhost' ||
                                   window.location.hostname === '127.0.0.1' ||
                                   window.location.hostname.includes('.test') ||
                                   window.location.protocol === 'http:';

            if (isDevEnvironment) {
                console.warn('[Push] 開發環境偵測到，嘗試使用替代方案');

                // 在開發環境中，跳過 Service Worker 註冊，直接返回 true
                // 這樣用戶仍然可以使用其他功能，只是無法接收推播通知
                console.log('[Push] 開發環境模式，跳過 Service Worker 註冊');
                this.registration = null;
                return true;
            }

            // 生產環境才註冊 Service Worker
            console.log('[Push] 生產環境，註冊 Service Worker');
            this.registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });

            console.log('[Push] Service Worker 註冊成功');

            // 等待 Service Worker 啟動
            await navigator.serviceWorker.ready;

            // 檢查現有訂閱
            this.subscription = await this.registration.pushManager.getSubscription();

            if (this.subscription) {
                console.log('[Push] 已有現存訂閱');
            }

            return true;
        } catch (error) {
            console.error('[Push] 初始化失敗:', error);

            // 如果是 SSL 錯誤且在開發環境，提供友好的錯誤訊息
            if (error.message.includes('SSL certificate error') ||
                error.message.includes('network error') ||
                error.message.includes('fetch')) {

                if (window.location.hostname.includes('.test') ||
                    window.location.hostname === 'localhost' ||
                    window.location.hostname === '127.0.0.1') {

                    console.warn('[Push] 開發環境網路錯誤，這是正常的。推播通知功能將受限，但其他功能正常。');
                    this.registration = null;
                    return true; // 返回 true 以免阻止其他功能
                }
            }

            return false;
        }
    }

    /**
     * 檢查推播權限狀態
     * @returns {Promise<string>} 'granted', 'denied', 或 'default'
     */
    async checkPermission() {
        if (!this.isSupported) {
            return 'unsupported';
        }

        return Notification.permission;
    }

    /**
     * 請求推播權限
     * @returns {Promise<boolean>}
     */
    async requestPermission() {
        try {
            const permission = await Notification.requestPermission();

            if (permission === 'granted') {
                console.log('[Push] 推播權限已授予');
                return true;
            } else if (permission === 'denied') {
                console.warn('[Push] 推播權限被拒絕');
                return false;
            } else {
                console.log('[Push] 推播權限取消');
                return false;
            }
        } catch (error) {
            console.error('[Push] 請求權限失敗:', error);
            return false;
        }
    }

    /**
     * 訂閱推播通知
     * @param {number} customerId - 顧客 ID
     * @returns {Promise<boolean>}
     */
    async subscribe(customerId) {
        // 檢查是否為開發環境
        const isDevEnvironment = window.location.hostname === 'localhost' ||
                               window.location.hostname === '127.0.0.1' ||
                               window.location.hostname.includes('.test') ||
                               window.location.protocol === 'http:';

        if (!this.registration) {
            if (isDevEnvironment) {
                console.warn('[Push] 開發環境無法訂閱推播通知（Service Worker 未註冊）');

                // 在開發環境中，顯示友好的訊息但不阻止功能
                // 仍然嘗試請求權限以提供更好的用戶體驗
                const permission = await this.requestPermission();
                if (permission) {
                    alert('推播通知功能在開發環境中受限。\n\n這是正常的，因為：\n• 開發環境不支持 Service Worker\n• 推播通知需要 HTTPS 連線\n\n在正式環境中將正常工作。');
                }
                return false;
            } else {
                console.error('[Push] Service Worker 尚未註冊');
                return false;
            }
        }

        // 先檢查權限
        const permission = await this.checkPermission();
        if (permission !== 'granted') {
            const granted = await this.requestPermission();
            if (!granted) {
                return false;
            }
        }

        try {
            // 建立新的推播訂閱
            this.subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlB64ToUint8Array(this.vapidPublicKey)
            });

            console.log('[Push] 推播訂閱成功:', this.subscription);

            // 將訂閱資訊發送到伺服器
            const response = await axios.post('/api/push/subscribe', {
                customer_id: customerId,
                subscription: this.subscription.toJSON()
            });

            if (response.data.success) {
                console.log('[Push] 伺服器訂閱記錄成功');
                return true;
            } else {
                console.error('[Push] 伺服器訂閱記錄失敗');
                return false;
            }
        } catch (error) {
            console.error('[Push] 訂閱失敗:', error);

            if (error.response) {
                console.error('[Push] 伺服器回應:', error.response.data);
            }

            return false;
        }
    }

    /**
     * 取消訂閱推播通知
     * @returns {Promise<boolean>}
     */
    async unsubscribe() {
        if (!this.subscription) {
            console.warn('[Push] 沒有現存訂閱');
            return true;
        }

        try {
            const endpoint = this.subscription.endpoint;

            // 取消瀏覽器訂閱
            const success = await this.subscription.unsubscribe();

            if (success) {
                console.log('[Push] 瀏覽器訂閱已取消');

                // 通知伺服器取消訂閱
                await axios.post('/api/push/unsubscribe', {
                    endpoint: endpoint
                });

                this.subscription = null;
                return true;
            } else {
                console.error('[Push] 取消訂閱失敗');
                return false;
            }
        } catch (error) {
            console.error('[Push] 取消訂閱時發生錯誤:', error);
            return false;
        }
    }

    /**
     * 檢查是否已訂閱
     * @returns {boolean}
     */
    isSubscribed() {
        return this.subscription !== null;
    }

    /**
     * 取得當前訂閱資訊
     * @returns {PushSubscription|null}
     */
    getSubscription() {
        return this.subscription;
    }

    /**
     * 將 Base64 URL 安全字串轉換為 Uint8Array
     * @param {string} base64String
     * @returns {Uint8Array}
     * @private
     */
    urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    /**
     * 顯示測試通知
     * @param {string} title
     * @param {string} body
     * @returns {Promise<void>}
     */
    async showTestNotification(title = '測試通知', body = '這是一則測試推播通知') {
        if (!this.registration) {
            console.error('[Push] Service Worker 尚未註冊');
            return;
        }

        const permission = await this.checkPermission();
        if (permission !== 'granted') {
            console.warn('[Push] 沒有推播權限');
            return;
        }

        try {
            await this.registration.showNotification(title, {
                body: body,
                icon: '/images/icon-192x192.png',
                badge: '/images/badge-72x72.png',
                vibrate: [100, 50, 100],
                data: {
                    dateOfArrival: Date.now(),
                    primaryKey: 1
                },
                actions: [
                    {
                        action: 'explore',
                        title: '查看',
                    },
                    {
                        action: 'close',
                        title: '關閉',
                    },
                ]
            });

            console.log('[Push] 測試通知已顯示');
        } catch (error) {
            console.error('[Push] 顯示測試通知失敗:', error);
        }
    }
}

// 建立全域實例
window.PushNotificationManager = PushNotificationManager;

// 自動初始化（可選）
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.pushManager = new PushNotificationManager();
    });
} else {
    window.pushManager = new PushNotificationManager();
}

export default PushNotificationManager;
