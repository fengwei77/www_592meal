import './bootstrap';
import './PushNotificationManager';

// 設定 VAPID 公鑰為全域變數
window.vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;

// 初始化推播管理器（登入用戶）
document.addEventListener('DOMContentLoaded', async () => {
    // 建立推播管理器實例並傳入 VAPID 公鑰
    if (window.vapidPublicKey) {
        window.pushManager = new PushNotificationManager();
        window.pushManager.vapidPublicKey = window.vapidPublicKey;
    }

    // 等待一點時間確保所有腳本都載入完成
    setTimeout(async () => {
        if (window.currentUser && window.pushManager) {
            try {
                await window.pushManager.init();
            } catch (error) {
                console.warn('[Push] 推播管理器初始化失敗:', error);
            }
        }
    }, 100);
});
