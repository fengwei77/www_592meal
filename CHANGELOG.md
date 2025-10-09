# 變更日誌 (Changelog)

## [v2.0] - 2025-10-09

### 🎉 重大變更

#### 安全設定系統重新設計
- **完全分離 IP 白名單和 2FA 的管理權限**
- IP 白名單：僅 Super Admin 可管理
- 2FA：店家可自主設定（需 Super Admin 啟用）

### ✨ 新增功能

#### 1. 2FA 完整設定流程
- ✅ 添加驗證碼輸入框（6位數）
- ✅ 實作驗證邏輯（使用 Google2FA）
- ✅ 即時驗證碼檢查
- ✅ 確認成功後自動更新狀態
- ✅ 店家可自行啟用/停用 2FA
- ✅ 完整的 QR Code 掃描流程

**文件**：
- `app/Filament/Pages/SecuritySettings.php` - 添加完整 2FA 流程
- `resources/views/filament/forms/components/two-factor-qr-code.blade.php` - QR Code 顯示

#### 2. Super Admin 臨時關閉 2FA 功能
- ✅ 用於緊急情況（店家遺失手機等）
- ✅ 臨時關閉後 24 小時自動恢復
- ✅ 店家重新設定 2FA 後立即恢復
- ✅ Super Admin 可手動立即恢復
- ✅ 狀態顯示剩餘時間

**使用情境**：
- 店家遺失手機
- 店家更換手機
- 店家無法取得驗證碼

**文件**：
- `app/Filament/Resources/UserResource/Pages/EditUser.php` - 添加臨時關閉/恢復按鈕
- `app/Filament/Resources/UserResource.php` - 更新狀態顯示

#### 3. 自動恢復機制
- ✅ 創建 scheduled command
- ✅ 每小時自動檢查過期的臨時關閉
- ✅ 自動恢復超過 24 小時的臨時關閉
- ✅ User Model 內建檢查邏輯

**文件**：
- `app/Console/Commands/RestoreExpiredTwoFactorDisable.php` - 新增
- `bootstrap/app.php` - 註冊 scheduled task
- `app/Models/User.php` - 添加自動檢查邏輯

#### 4. 數據庫結構更新
- ✅ 新增 `two_factor_temp_disabled_at` 欄位
- ✅ 支援臨時關閉功能

**文件**：
- `database/migrations/2025_10_09_100541_add_two_factor_temp_disabled_to_users_table.php` - 新增

### 🔄 修改內容

#### 安全設定頁面重新設計
**Before**：
- 店家可以看到 IP 白名單狀態
- 店家可以看到當前 IP
- 沒有驗證碼輸入框

**After**：
- ❌ 完全移除 IP 白名單部分（店家不應看到）
- ✅ 僅顯示 2FA 設定
- ✅ 添加驗證碼輸入框
- ✅ 添加啟用/確認/停用按鈕

**文件**：
- `app/Filament/Pages/SecuritySettings.php` - 完全重寫
- `resources/views/filament/pages/security-settings.blade.php` - 簡化

#### User Model 增強
**新增方法**：
- `isTwoFactorTempDisabled()` - 檢查是否臨時關閉
- `tempDisableTwoFactor()` - 臨時關閉 2FA
- `restoreTwoFactor()` - 恢復 2FA
- `hasTwoFactorEnabled()` - 更新邏輯（考慮臨時關閉狀態）
- `confirmTwoFactor()` - 更新邏輯（清除臨時關閉狀態）
- `disableTwoFactor()` - 更新邏輯（清除臨時關閉狀態）

**文件**：
- `app/Models/User.php`

#### UserResource 狀態顯示增強
- ✅ 顯示臨時關閉狀態
- ✅ 顯示剩餘恢復時間
- ✅ 顯示關閉和恢復時間

**文件**：
- `app/Filament/Resources/UserResource.php`

### 🐛 修正問題

#### 修正 Filament v4 相容性問題
- ✅ 修正 blade view 語法（移除舊的 form.actions component）
- ✅ 統一使用 HeaderActions
- ✅ 移除不必要的 form 標籤

**文件**：
- `resources/views/filament/pages/security-settings.blade.php`

### 📚 文檔更新

#### 新增文檔
- ✅ `SECURITY_SETTINGS_GUIDE.md` - 完整使用指南
- ✅ `CHANGELOG.md` - 變更日誌（本文件）
- ✅ `CODE_REVIEW_REPORT.md` - Code Review 報告

#### 更新文檔
- ✅ 測試文件（包含 2FA 完整流程測試）
- ✅ README（如有）

### 🔧 技術改進

#### Laravel 12 優化
- ✅ 使用 Laravel 12 的 scheduled task 語法
- ✅ 使用 `withSchedule()` 註冊排程任務
- ✅ 符合 Laravel 12 最佳實踐

#### 安全性提升
- ✅ 2FA secret 加密儲存
- ✅ 臨時關閉有時間限制（24小時）
- ✅ 三重恢復機制（自動/手動/店家重設）
- ✅ 完整的日誌和通知

### 📦 依賴套件

無新增套件，使用現有套件：
- `pragmarx/google2fa-laravel` - 2FA 實作
- `spatie/laravel-permission` - 權限管理
- `filament/filament` - v4.1.6

---

## [v1.0] - 2025-10-09（早期版本）

### ✨ 初始功能

#### 1. IP 白名單功能
- ✅ Super Admin 可為店家啟用 IP 白名單
- ✅ 可設定多個允許的 IP
- ✅ Middleware 層級檢查
- ✅ 日誌記錄

**文件**：
- `app/Http/Middleware/CheckIpWhitelist.php` - 新增
- `app/Providers/Filament/AdminPanelProvider.php` - 註冊 middleware
- `database/migrations/2025_10_09_022035_add_security_fields_to_users_table.php` - 新增

#### 2. 2FA 基本功能
- ✅ Super Admin 可為店家啟用 2FA
- ✅ QR Code 生成
- ✅ Google Authenticator 整合

**文件**：
- `app/Models/User.php` - 添加 2FA 方法
- `resources/views/filament/forms/components/two-factor-qr-code.blade.php` - 新增

#### 3. Filament Resources
- ✅ UserResource - 店家管理
- ✅ SecuritySettings Page - 個人安全設定

**文件**：
- `app/Filament/Resources/UserResource.php` - 新增
- `app/Filament/Pages/SecuritySettings.php` - 新增

#### 4. 測試文件
- ✅ SecuritySettingsTest - 6 tests
- ✅ IpWhitelistTest - 9 tests
- ✅ TwoFactorAuthTest - 9 tests
- ✅ Manual Testing Guide

**文件**：
- `tests/Feature/SecuritySettingsTest.php` - 新增
- `tests/Feature/IpWhitelistTest.php` - 新增
- `tests/Feature/TwoFactorAuthTest.php` - 新增
- `tests/MANUAL_TESTING_GUIDE.md` - 新增

### 🐛 修正問題

#### Filament v4 Type Compatibility
- ✅ 升級 Filament 從 v4.1.0 到 v4.1.6
- ✅ 修正 type declaration（`string | \BackedEnum | null`）
- ✅ 修正 Actions namespace
- ✅ 修正 Page actions handling

#### Migration 執行問題
- ✅ 暫時停用錯誤的 Resource 文件
- ✅ 成功執行 migration
- ✅ 更新後重新啟用並修正

### 🔒 安全考量

#### 已實作的安全措施
- ✅ 2FA secret 加密儲存
- ✅ Recovery codes 加密儲存
- ✅ IP 白名單在 middleware 層級執行
- ✅ Session 正確清除（IP 不符時）
- ✅ 日誌記錄（IP 拒絕、2FA 操作）
- ✅ 權限控制（Super Admin vs 店家）

---

## 文件對照表

### 核心功能文件

| 功能 | 文件路徑 | 版本 |
|------|---------|------|
| **User Model** | `app/Models/User.php` | v2.0 |
| **店家管理** | `app/Filament/Resources/UserResource.php` | v2.0 |
| **安全設定頁** | `app/Filament/Pages/SecuritySettings.php` | v2.0 |
| **IP 白名單 Middleware** | `app/Http/Middleware/CheckIpWhitelist.php` | v1.0 |
| **自動恢復 Command** | `app/Console/Commands/RestoreExpiredTwoFactorDisable.php` | v2.0 (新) |

### 資料庫遷移

| Migration | 版本 | 說明 |
|-----------|------|------|
| `2025_10_09_022035_add_security_fields_to_users_table.php` | v1.0 | 初始安全欄位 |
| `2025_10_09_100541_add_two_factor_temp_disabled_to_users_table.php` | v2.0 | 臨時關閉欄位 |

### 測試文件

| 測試文件 | 版本 | 測試數 |
|---------|------|--------|
| `tests/Feature/SecuritySettingsTest.php` | v1.0 | 6 |
| `tests/Feature/IpWhitelistTest.php` | v1.0 | 9 |
| `tests/Feature/TwoFactorAuthTest.php` | v1.0 | 9 |

### 文檔

| 文檔 | 版本 | 說明 |
|------|------|------|
| `SECURITY_SETTINGS_GUIDE.md` | v2.0 | 完整使用指南 |
| `CHANGELOG.md` | v2.0 | 本文件 |
| `CODE_REVIEW_REPORT.md` | v2.0 | Code Review 報告 |
| `tests/MANUAL_TESTING_GUIDE.md` | v1.0 | 手動測試指南 |
| `tests/README_TESTING.md` | v1.0 | 測試說明 |

---

## 升級指南

### 從 v1.0 升級到 v2.0

#### 1. 執行資料庫遷移
```bash
php artisan migrate
```

#### 2. 清除快取
```bash
php artisan optimize:clear
```

#### 3. 設定 Scheduled Task（生產環境）
在 crontab 中添加：
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

#### 4. 測試功能
- 測試 2FA 完整流程（啟用 → 掃描 → 確認）
- 測試臨時關閉功能
- 測試自動恢復 command：
  ```bash
  php artisan two-factor:restore-expired
  ```

#### 5. 注意事項
- ⚠️ 店家將無法再看到 IP 白名單設定（設計變更）
- ⚠️ 現有的 2FA 設定不受影響
- ⚠️ 需要通知店家新的 2FA 設定流程

---

## 已知問題

### v2.0
- 無已知問題

### v1.0
- ✅ 已修正：Blade view 使用舊的 Filament v3 語法

---

## 未來計劃

### 短期（v2.1）
- [ ] 2FA 登入驗證流程（目前僅設定，未實作登入檢查）
- [ ] 2FA Recovery Codes 管理介面
- [ ] IP 範圍支援（CIDR notation）
- [ ] IP 白名單變更通知

### 中期（v3.0）
- [ ] 登入歷史記錄
- [ ] 異常登入警告
- [ ] 多種 2FA 方式（SMS、Email）
- [ ] 更詳細的安全日誌

### 長期
- [ ] 完整的審計系統
- [ ] 帳號活動時間軸
- [ ] 安全分析報表

---

## 貢獻者

- **Claude Code** - 初始開發與 v2.0 重構

---

## 授權

本專案版權歸 592meal 所有。

---

**文檔版本**: v2.0
**最後更新**: 2025-10-09
**Laravel 版本**: 12.32.5
**PHP 版本**: 8.4.12
**Filament 版本**: 4.1.6
