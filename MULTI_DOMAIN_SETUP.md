# 多網域配置說明（安全架構）

## 🔒 概述

本專案採用**前後台網域完全分離**架構，符合安全最佳實踐。

### 🌐 網域配置

| 用途 | 網域 | 路徑 | 說明 |
|------|------|------|------|
| **前台** | `oh592meal.test` | `/` | 顧客訂餐介面 |
| **後台** | `cms.oh592meal.test` | `/` | Filament 管理後台（整個網域專用） |

### ⚠️ 重要安全規則

1. ❌ **禁止：** `oh592meal.test/admin` - 前台網域不可訪問任何 /admin 路徑
2. ✅ **正確：** `cms.oh592meal.test` - 後台網域的根路徑就是登入頁
3. 🛡️ **隱藏：** 沒有 `/admin` 路徑暴露，降低掃描攻擊風險

---

## 🔧 Apache VirtualHost 設定

### 檔案位置
`D:/laragon/etc/apache2/sites-enabled/cms.oh592meal.test.conf`

### ⚠️ 關鍵：DocumentRoot 必須指向 public 目錄

```apache
define ROOT "D:/laragon/www/oh592meal/www/public"
define SITE "cms.oh592meal.test"

<VirtualHost *:80>
    DocumentRoot "${ROOT}"
    ServerName ${SITE}
    ServerAlias *.${SITE}
    <Directory "${ROOT}">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:443>
    DocumentRoot "${ROOT}"
    ServerName ${SITE}
    ServerAlias *.${SITE}
    <Directory "${ROOT}">
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile      D:/laragon/etc/ssl/laragon.crt
    SSLCertificateKeyFile   D:/laragon/etc/ssl/laragon.key
</VirtualHost>
```

**⚠️ 常見錯誤：**
- ❌ `D:/laragon/www/cms.oh592meal` (錯誤的空目錄)
- ✅ `D:/laragon/www/oh592meal/www/public` (正確的 Laravel public 目錄)

---

## 🌐 Laravel 環境變數設定

### .env 配置

```env
# 前台網域（顧客端）
APP_URL=https://oh592meal.test

# 後台網域（管理端）- 完整網域，無 /admin 路徑
ADMIN_URL=https://cms.oh592meal.test
```

### config/app.php

```php
'url' => env('APP_URL', 'http://localhost'),
'admin_url' => env('ADMIN_URL', env('APP_URL')),
```

---

## 🎯 Filament Panel 配置

### app/Providers/Filament/AdminPanelProvider.php

**關鍵變更：** `->path('/')` 而非 `->path('admin')`

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('/') // 🔒 根路徑，無 /admin 暴露
        ->login()
        // ... 其他配置
}
```

**效果：**
- ✅ `cms.oh592meal.test` → Filament 登入頁
- ✅ `cms.oh592meal.test/users` → 用戶管理
- ✅ `cms.oh592meal.test/security-settings` → 安全設定

---

## 🛡️ 網域檢查中介層

### CheckAdminDomain 中介層

**位置：** `app/Http/Middleware/CheckAdminDomain.php`

**三層安全規則：**

#### 規則 1：前台網域禁止 /admin 路徑
```php
if ($currentHost === $frontDomain && $request->is('admin*')) {
    abort(404, '此頁面不存在'); // ⚠️ 返回 404，不重定向
}
```

**為什麼返回 404？**
- ❌ 重定向：會暴露後台網域位置
- ✅ 404 錯誤：讓攻擊者以為沒有後台

#### 規則 2：防止跨網域訪問 Filament
```php
if ($currentHost !== $adminDomain) {
    $routeName = $request->route()?->getName();
    if ($routeName && str_starts_with($routeName, 'filament.')) {
        abort(403, '禁止訪問');
    }
}
```

#### 規則 3：後台網域直通
```php
if ($currentHost === $adminDomain) {
    return $next($request); // Filament 會接管所有路由
}
```

### 註冊位置

**bootstrap/app.php:**

```php
->withMiddleware(function (Middleware $middleware): void {
    // ⚠️ 測試環境停用，避免干擾單元測試
    if (!app()->environment('testing')) {
        $middleware->web(append: [
            \App\Http\Middleware\CheckAdminDomain::class,
        ]);
    }
})
```

---

## 🚀 使用方式

### 訪問前台（顧客端）

```
https://oh592meal.test
```

### 訪問後台（管理端）

```
✅ https://cms.oh592meal.test          ← Filament 登入頁
✅ https://cms.oh592meal.test/users    ← 用戶管理
✅ https://cms.oh592meal.test/security-settings
```

### ❌ 禁止訪問

```
❌ https://oh592meal.test/admin        → 404 錯誤
❌ https://oh592meal.test/admin/users  → 404 錯誤
❌ https://cms.oh592meal.test/admin    → 404 錯誤（無此路徑）
```

---

## 🔒 安全優勢

### 1. **完全網域隔離**
- 後台完全獨立網域，前台無法訪問
- 降低 CSRF 和 XSS 攻擊風險

### 2. **隱藏後台入口**
- 沒有 `/admin` 路徑暴露
- 掃描工具無法發現後台位置
- 降低暴力破解風險

### 3. **多層安全防護**
- **第一層：** CheckAdminDomain 中介層（網域隔離）
- **第二層：** CheckIpWhitelist 中介層（IP 白名單）
- **第三層：** Google2FAProvider（雙因素認證）
- **第四層：** Filament 內建權限系統

### 4. **符合安全標準**
- ✅ OWASP Top 10 最佳實踐
- ✅ 最小權限原則
- ✅ 深度防禦策略

---

## 🧪 測試環境

### 自動停用網域檢查

測試環境中，`CheckAdminDomain` 中介層會自動停用：

```php
// bootstrap/app.php
if (!app()->environment('testing')) {
    $middleware->web(append: [
        \App\Http\Middleware\CheckAdminDomain::class,
    ]);
}
```

### 測試驗證

所有測試通過 ✅：

```bash
php artisan test

Tests:    7 skipped, 59 passed (208 assertions)
Duration: 4.27s
```

---

## 📝 維護指南

### 清除快取

當修改網域設定後，務必執行：

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 重啟 Apache

修改 VirtualHost 設定後：

1. 在 Laragon 右鍵選單
2. Apache → Reload

### hosts 檔案設定

確保 `C:\Windows\System32\drivers\etc\hosts` 包含：

```
127.0.0.1 oh592meal.test
127.0.0.1 cms.oh592meal.test
```

**管理員權限：** 編輯 hosts 檔案需要以系統管理員身分執行記事本

---

## 🐛 常見問題排解

### 1. 訪問 cms.oh592meal.test 出現 404

**檢查項目：**
- ✅ VirtualHost DocumentRoot 是否指向 `D:/laragon/www/oh592meal/www/public`
- ✅ Apache 是否已重啟
- ✅ hosts 檔案是否正確設定
- ✅ Filament path 是否設定為 `'/'`

**驗證命令：**
```bash
php artisan route:list
# 應該看到 / 而非 /admin 路徑
```

### 2. 訪問 oh592meal.test/admin 沒有返回 404

**檢查項目：**
- ✅ CheckAdminDomain 中介層是否已註冊
- ✅ 是否在測試環境（testing 環境會停用中介層）

**驗證方法：**
```bash
# 清除快取
php artisan config:clear

# 檢查環境
php artisan env
# 應該顯示 local，不是 testing
```

### 3. SSL 憑證錯誤

**解決方法：**
- 使用 Laragon 內建 SSL 憑證管理工具
- 或暫時使用 HTTP (port 80) 進行測試

### 4. 無限重定向迴圈

**可能原因：**
- CheckAdminDomain 中介層邏輯錯誤
- APP_URL 或 ADMIN_URL 設定錯誤

**解決方法：**
```bash
php artisan config:clear
# 檢查 .env 中的 URL 設定
```

---

## 📚 相關檔案

| 檔案 | 用途 | 關鍵設定 |
|------|------|----------|
| `.env` | 環境變數 | `APP_URL`, `ADMIN_URL` |
| `config/app.php` | Admin URL 註冊 | `'admin_url' => env('ADMIN_URL')` |
| `app/Providers/Filament/AdminPanelProvider.php` | Filament 配置 | `->path('/')` |
| `app/Http/Middleware/CheckAdminDomain.php` | 網域檢查邏輯 | 三層安全規則 |
| `bootstrap/app.php` | 中介層註冊 | 測試環境停用 |

---

## 📋 路由結構

### 前台路由 (oh592meal.test)

```
GET  /                    → 首頁
GET  /login               → 顧客登入（LINE Login）
GET  /auth/line           → LINE 授權
GET  /auth/line/callback  → LINE 回調
GET  /dashboard           → 顧客儀表板
GET  /onboarding          → 店家註冊
```

### 後台路由 (cms.oh592meal.test)

```
GET  /                    → Filament 登入頁
GET  /users               → 用戶管理（super_admin）
GET  /users/create        → 創建用戶
GET  /users/{id}/edit     → 編輯用戶
GET  /security-settings   → 安全設定（所有已登入用戶）
```

**⚠️ 注意：** 沒有 `/admin` 前綴！

---

## ✅ 配置完成檢查清單

- [ ] Apache VirtualHost 設定完成
  - [ ] DocumentRoot 指向 `D:/laragon/www/oh592meal/www/public`
  - [ ] ServerName 設定為 `cms.oh592meal.test`
- [ ] hosts 檔案已更新
  - [ ] `127.0.0.1 oh592meal.test`
  - [ ] `127.0.0.1 cms.oh592meal.test`
- [ ] .env 檔案包含
  - [ ] `APP_URL=https://oh592meal.test`
  - [ ] `ADMIN_URL=https://cms.oh592meal.test`
- [ ] Filament 配置
  - [ ] `->path('/')` 設定正確
- [ ] CheckAdminDomain 中介層
  - [ ] 已創建並註冊
  - [ ] 測試環境自動停用
- [ ] Laravel 快取已清除
  - [ ] `php artisan config:clear`
  - [ ] `php artisan route:clear`
  - [ ] `php artisan cache:clear`
- [ ] Apache 已重啟
- [ ] 測試驗證
  - [ ] ✅ `cms.oh592meal.test` 可訪問 Filament
  - [ ] ✅ `oh592meal.test` 可訪問前台
  - [ ] ❌ `oh592meal.test/admin` 返回 404
  - [ ] ✅ 所有單元測試通過

---

## 🔐 安全檢查清單

### 網域隔離
- [ ] 前台網域無法訪問 `/admin` 路徑
- [ ] 後台網域無 `/admin` 路徑暴露
- [ ] 跨網域訪問被正確阻擋

### 認證與授權
- [ ] Super Admin 可以訪問用戶管理
- [ ] Store Owner 無法訪問用戶管理
- [ ] 所有用戶可以訪問安全設定
- [ ] 未登入用戶被重定向到登入頁

### IP 白名單
- [ ] CheckIpWhitelist 中介層已註冊
- [ ] IP 白名單功能正常運作
- [ ] 白名單外的 IP 被正確阻擋

### 雙因素認證
- [ ] Google2FAProvider 已整合
- [ ] QR Code 生成正常
- [ ] 驗證碼驗證正確
- [ ] 恢復碼機制可用

---

## 📊 架構圖

```
┌─────────────────────────────────────────────────────────────┐
│                    網際網路請求                              │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────┐
        │   Apache VirtualHost        │
        │   (Port 80 / 443)           │
        └─────────────┬───────────────┘
                      │
          ┌───────────┴───────────┐
          │                       │
          ▼                       ▼
┌──────────────────┐    ┌──────────────────┐
│ oh592meal.test   │    │cms.oh592meal.test│
│ (前台)           │    │ (後台)           │
└────────┬─────────┘    └────────┬─────────┘
         │                       │
         │                       │
         ▼                       ▼
┌──────────────────┐    ┌──────────────────┐
│CheckAdminDomain  │    │CheckAdminDomain  │
│ ❌ 阻擋 /admin   │    │ ✅ 允許通過      │
└────────┬─────────┘    └────────┬─────────┘
         │                       │
         │                       ▼
         │              ┌──────────────────┐
         │              │CheckIpWhitelist  │
         │              │ 🔒 IP 白名單檢查  │
         │              └────────┬─────────┘
         │                       │
         │                       ▼
         │              ┌──────────────────┐
         │              │Google2FAProvider │
         │              │ 🔐 2FA 驗證       │
         │              └────────┬─────────┘
         │                       │
         ▼                       ▼
┌──────────────────┐    ┌──────────────────┐
│   前台路由       │    │  Filament Panel  │
│   (顧客介面)     │    │  (管理介面)       │
└──────────────────┘    └──────────────────┘
```

---

**最後更新：** 2025-10-10
**維護者：** 592Meal 開發團隊
**架構版本：** 2.0 (安全加強版)
