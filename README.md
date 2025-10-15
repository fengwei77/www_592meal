# 592meal 線上訂餐系統

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![Filament](https://img.shields.io/badge/Filament-4.1-orange.svg)](https://filamentphp.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**592meal** 是一個專為餐廳訂餐設計的線上訂餐系統，提供完整的訂單管理、店家管理、用戶認證及支付整合功能。

---

## 🚀 專案特色

### ✅ 已完成功能

#### 🔐 安全系統 (v2.0)
- **雙因素認證 (2FA)**
  - Google Authenticator 整合 (TOTP 標準)
  - QR Code 掃描設定
  - 完整的登入驗證流程
  - 臨時關閉功能（24 小時自動恢復）
  - 三重恢復機制（自動/手動/重新設定）

- **IP 白名單**
  - Middleware 層級早期攔截
  - Super Admin 統一管理
  - 多 IP 位址支援
  - IP 不符自動登出機制

- **角色權限系統**
  - Spatie Permission 整合
  - Super Admin 角色（完整權限）
  - Store Owner 角色（限制權限）
  - 細緻的訪問控制

#### 👥 用戶管理
- Filament 後台管理介面
- 用戶 CRUD 操作
- 角色分配管理
- 密碼管理

#### 🔑 LINE 登入整合 (80%)
- LINE Login API 整合
- OAuth 2.0 授權流程
- 用戶資料同步
- LINE 綁定/解綁功能

### 🔄 開發中功能
- 前端介面優化 (60%)
- LINE 登入測試與優化 (80%)

### ⏳ 規劃中功能
- 訂單系統
- 餐點管理
- 店家管理
- 支付系統整合 (LINE Pay)
- 通知系統

---

## 📋 系統需求

- **PHP**: 8.4+
- **Composer**: 2.x
- **Node.js**: 18.x+
- **MySQL**: 8.0+
- **Web Server**: Nginx / Apache

---

## 🛠️ 安裝指南

### 1. Clone 專案

```bash
git clone https://github.com/fengwei77/oh592meal.git
cd oh592meal/www
```

### 2. 安裝依賴

```bash
# 安裝 PHP 依賴
composer install

# 安裝前端依賴
npm install
```

### 3. 環境設定

```bash
# 複製環境設定檔
cp .env.example .env

# 生成應用程式金鑰
php artisan key:generate
```

### 4. 資料庫設定

編輯 `.env` 檔案，設定資料庫連線資訊：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oh592meal
DB_USERNAME=root
DB_PASSWORD=
```

### 5. 執行 Migration & Seeder

```bash
# 執行資料庫遷移
php artisan migrate

# 建立角色和權限
php artisan db:seed --class=RolePermissionSeeder

# 建立 Super Admin 帳號
php artisan db:seed --class=SuperAdminSeeder
```

### 6. 編譯前端資源

```bash
npm run dev
# 或生產環境
npm run build
```

### 7. 設定排程任務

將以下內容加入 Cron Job（用於 2FA 自動恢復）：

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

或手動執行排程命令：

```bash
php artisan two-factor:restore-expired
```

### 8. 啟動開發伺服器

```bash
php artisan serve
```

訪問 `http://localhost:8000/admin` 進入後台管理介面。

---

## 👤 預設帳號

### Super Admin
- **Email**: `admin@592meal.com`
- **Password**: `password`
- **權限**: 完整系統訪問、IP 白名單管理、2FA 控制

### Store Owner（需自行建立）
- **權限**: 管理自己的店家、自主管理 2FA

---

## 🔐 安全功能使用說明

### 雙因素認證 (2FA) 設定

#### 店家自行設定 2FA

1. 登入後台
2. 進入「安全設定」頁面
3. 點擊「啟用我的 2FA」
4. 使用 Google Authenticator 掃描 QR Code
5. 輸入 6 位數驗證碼
6. 點擊「確認 2FA」完成設定

#### Super Admin 臨時關閉店家 2FA

1. 進入「用戶管理」
2. 編輯目標店家
3. 點擊「臨時關閉 2FA (24小時)」
4. 系統將在 24 小時後自動恢復

**三重恢復機制**：
- ⏰ 自動恢復：24 小時後自動啟用（Laravel Scheduler）
- 👨‍💼 手動恢復：Super Admin 可立即恢復
- 🔄 重新設定：店家重新設定 2FA 時立即恢復

### IP 白名單設定

**僅 Super Admin 可管理**

1. 進入「用戶管理」
2. 編輯目標店家
3. 啟用「IP 白名單」
4. 輸入允許的 IP 位址（可輸入多個）
5. 儲存

**注意**: 店家無法查看或修改 IP 白名單設定。

---

## 🧪 測試

### 執行自動化測試

```bash
# 執行所有測試
php artisan test

# 執行特定測試
php artisan test tests/Feature/SecuritySettingsTest.php
php artisan test tests/Feature/IpWhitelistTest.php
php artisan test tests/Feature/TwoFactorAuthTest.php
```

### 測試報告

- ✅ **24 個自動化測試** (SecuritySettingsTest, IpWhitelistTest, TwoFactorAuthTest)
- ✅ **27 個手動測試案例** (詳見 `tests/MANUAL_TESTING_GUIDE.md`)

---

## 📚 文檔

> 📋 **快速導航**: 查看 [文檔索引](DOCUMENTATION_INDEX.md) 取得所有文檔的完整導覽

### 使用指南
- [安全設定使用指南](SECURITY_SETTINGS_GUIDE.md) - 2FA 和 IP 白名單完整操作說明
- [安全系統總覽](SECURITY_README.md) - 安全功能架構與技術說明

### 技術文檔
- [技術實作總結](IMPLEMENTATION_SUMMARY.md) - 安全系統實作細節
- [Code Review 報告](CODE_REVIEW_REPORT.md) - 程式碼審查結果
- [專案進度報告](PROJECT_STATUS.md) - 完整專案進度與統計
- [變更記錄](CHANGELOG.md) - 版本更新歷史

### 測試文檔
- [測試說明](tests/README_TESTING.md) - 測試架構與執行方式
- [手動測試指南](tests/MANUAL_TESTING_GUIDE.md) - 27 個手動測試案例

### 規劃與索引
- [文檔索引](DOCUMENTATION_INDEX.md) - 所有文檔的完整導覽與使用指南
- [規格文件更新建議](SPEC_UPDATE_RECOMMENDATIONS.md) - 規格文件更新指引

---

## 🏗️ 技術架構

### 後端技術
- **Framework**: Laravel 12.32.5
- **PHP**: 8.4.13
- **Admin Panel**: Filament 4.1.6
- **權限管理**: Spatie Laravel Permission 6.9
- **雙因素認證**: PragmaRX Google2FA Laravel 3.0
- **LINE Login**: Laravel Socialite

### 前端技術
- **Template Engine**: Blade
- **CSS Framework**: Tailwind CSS
- **JavaScript**: Alpine.js (Filament)
- **Livewire**: Filament 整合

### 資料庫
- **Database**: MySQL 8.0
- **ORM**: Eloquent

### 開發工具
- **Development Environment**: Laragon (Windows)
- **Version Control**: Git
- **Testing**: PHPUnit
- **Code Quality**: PHPStan, Laravel Pint

---

## 📊 專案統計

### 程式碼統計
```
總行數: ~6,500+ 行
├── PHP 後端: ~4,000 行
├── Blade 視圖: ~800 行
├── 測試程式: ~1,200 行
└── 文檔: ~6,000 行
```

### 核心功能模組
- ✅ 安全設定系統（100%）
- ✅ 用戶管理（100%）
- ✅ 權限角色系統（100%）
- 🔄 LINE 登入整合（80%）
- 🔄 前端介面（60%）
- ⏳ 訂單系統（0%）
- ⏳ 餐點管理（0%）
- ⏳ 支付整合（0%）

---

## 🗺️ 專案結構

```
oh592meal/www/
├── app/
│   ├── Console/Commands/          # Artisan 命令
│   │   └── RestoreExpiredTwoFactorDisable.php
│   ├── Filament/
│   │   ├── Auth/                  # Filament 認證
│   │   │   └── Google2FAProvider.php
│   │   ├── Pages/                 # Filament 頁面
│   │   │   └── SecuritySettings.php
│   │   └── Resources/             # Filament 資源
│   │       └── UserResource.php
│   ├── Http/
│   │   ├── Controllers/           # 控制器
│   │   └── Middleware/            # 中介層
│   │       └── CheckIpWhitelist.php
│   ├── Models/                    # Eloquent 模型
│   │   ├── User.php
│   │   └── Customer.php
│   └── Providers/                 # 服務提供者
│       └── Filament/AdminPanelProvider.php
├── bootstrap/
│   └── app.php                    # Laravel 12 排程設定
├── config/                        # 設定檔
│   └── permission.php
├── database/
│   ├── migrations/                # 資料庫遷移
│   └── seeders/                   # 資料填充
│       ├── RolePermissionSeeder.php
│       └── SuperAdminSeeder.php
├── resources/
│   └── views/
│       └── filament/              # Filament 視圖
├── tests/
│   └── Feature/                   # 功能測試
│       ├── SecuritySettingsTest.php
│       ├── IpWhitelistTest.php
│       └── TwoFactorAuthTest.php
└── routes/                        # 路由定義
    ├── web.php
    └── console.php
```

---

## 🤝 貢獻

歡迎提交 Pull Request 或開 Issue 提出建議！

### 開發流程

1. Fork 本專案
2. 建立功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 開啟 Pull Request

---

## 📝 版本歷史

### v2.0 (2025-10-09) - 安全系統完整實作
- ✅ 雙因素認證 (2FA) - Google Authenticator
- ✅ IP 白名單功能
- ✅ 臨時關閉 2FA（24 小時自動恢復）
- ✅ Spatie Permission 權限系統
- ✅ 完整測試系統（24 自動化測試 + 27 手動測試）
- ✅ 完整文檔系統（8 份文件）

### v1.0 (2025-10-03) - 基礎系統
- ✅ Laravel 12 專案初始化
- ✅ Filament 後台整合
- ✅ LINE 登入基礎功能
- ✅ 用戶管理系統
- ✅ 基礎前端介面

詳細變更記錄請參閱 [CHANGELOG.md](CHANGELOG.md)

---

## 🔐 安全性

如果您發現任何安全性問題，請通過以下方式聯繫我們，而不是使用公開的 Issue Tracker：

- **Email**: security@592meal.com

我們會盡快處理所有安全性問題。

### 已實作的安全措施
- ✅ 2FA 雙因素認證
- ✅ IP 白名單限制
- ✅ 密碼加密儲存
- ✅ CSRF 保護
- ✅ SQL Injection 防護（Eloquent ORM）
- ✅ XSS 防護（Blade escape）
- ✅ Session 管理
- ✅ 角色權限控制

---

## 📄 授權

本專案採用 MIT 授權 - 詳見 [LICENSE](LICENSE) 檔案

---

## 📞 聯絡資訊

- **Repository**: https://github.com/fengwei77/oh592meal
- **Issues**: https://github.com/fengwei77/oh592meal/issues
- **Website**: https://oh592meal.test (開發環境)

---

## 🙏 致謝

- [Laravel](https://laravel.com) - PHP 框架
- [Filament](https://filamentphp.com) - 後台管理面板
- [Spatie](https://spatie.be) - Laravel Permission 套件
- [PragmaRX](https://github.com/antonioribeiro/google2fa) - Google2FA 套件

---

**最後更新**: 2025-10-09
**專案狀態**: 🚀 積極開發中
**下一個里程碑**: 完成訂單系統（預計 2025-11-09）
