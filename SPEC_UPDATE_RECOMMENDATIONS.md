# 規格文件更新建議

**日期**: 2025-10-09
**版本**: v1.1
**目的**: 記錄因安全系統實作而需更新的規格文件

---

## ⚠️ 重要說明

**目前專案狀態**: 專案中**尚未建立** `docs/` 目錄及相關規格文件。

本文件提供兩種方案：

### 方案 A：建立完整規格文件（推薦）
如果您需要完整的技術規格文件，可以參考本文件的建議結構建立 `docs/` 目錄及相關文件。

### 方案 B：使用現有文檔（目前狀態）
目前專案已有以下完整文檔，可以直接使用：
- `README.md` - 專案總覽與安裝指南
- `PROJECT_STATUS.md` - 專案進度報告
- `SECURITY_README.md` - 安全系統總覽
- `SECURITY_SETTINGS_GUIDE.md` - 使用指南
- `IMPLEMENTATION_SUMMARY.md` - 技術實作總結
- `CODE_REVIEW_REPORT.md` - Code Review 報告
- `CHANGELOG.md` - 版本變更記錄

**建議**: 對於中小型專案，方案 B 的現有文檔已經足夠完整。如果未來專案規模擴大或需要更正式的規格文件，再考慮採用方案 A。

---

## 📋 規格文件結構建議（方案 A）

如果決定建立完整規格文件，以下是推薦的目錄結構及內容：

```
docs/
├── architecture/              # 架構文件
│   ├── 01-overview.md        # 系統總覽
│   ├── 08-rest-api-spec.md   # REST API 規格
│   ├── 15-security.md        # 安全性規格 ⭐ 新增
│   └── architecture.md        # 架構總索引
├── prd/                       # 產品需求文件
│   └── 非功能需求.md          # 非功能需求
└── mvp-development-tasks.md   # MVP 開發任務

```

根據最新實作的安全設定系統（2FA + IP 白名單 + 權限管理），以下規格文件建議建立：

### 1. ⚠️ 必須更新（重要度：HIGH）

#### `docs/architecture/15-security.md` - 安全性規格

**需要新增的內容**:

##### 15.2.4 雙因素認證 (2FA) - Google Authenticator

```markdown
### 15.2.4 雙因素認證 (2FA)

**實作方式：** Google Authenticator (TOTP)

**設定流程：**

```php
// app/Filament/Pages/SecuritySettings.php
public function enableTwoFactor()
{
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();

    $user->two_factor_secret = encrypt($secret);
    $user->save();

    // 生成 QR Code
    $qrCodeUrl = $google2fa->getQRCodeUrl(
        config('app.name'),
        $user->email,
        $secret
    );
}
```

**登入驗證：**

```php
// app/Filament/Auth/Google2FAProvider.php
public function getChallengeFormComponents(Authenticatable $user): array
{
    return [
        TextInput::make('code')
            ->label('驗證碼')
            ->placeholder('請輸入 6 位數驗證碼')
            ->length(6)
            ->numeric()
            ->required()
    ];
}
```

**臨時關閉功能：**

- Super Admin 可臨時關閉店家 2FA（24小時）
- 自動恢復機制（Laravel Scheduler 每小時執行）
- 店家重新設定時立即恢復

**安全特性：**

- ✅ Secret 加密儲存（Laravel encryption）
- ✅ 時間窗口驗證（30 秒容錯）
- ✅ 三重恢復機制
- ✅ 完整審計日誌
```

##### 15.3.3 Spatie Permission 權限系統

```markdown
### 15.3.3 Spatie Permission 權限系統

**已實作角色：**

```php
// Super Admin
- 完整系統訪問權限
- 管理所有店家
- IP 白名單管理
- 2FA 啟用/停用控制
- 臨時關閉 2FA

// Store Owner
- 管理自己的店家
- 自主管理 2FA
- 無法查看 IP 白名單
- 無法管理其他店家
```

**設定檔：**

```php
// config/permission.php
return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],
];
```
```

##### 15.4.4 IP 白名單

```markdown
### 15.4.4 IP 白名單

**實作方式：** Middleware 層級攔截

**Middleware 實作：**

```php
// app/Http/Middleware/CheckIpWhitelist.php
public function handle(Request $request, Closure $next)
{
    $user = Auth::user();

    if (!$user) {
        return $next($request);
    }

    // 檢查是否啟用 IP 白名單
    if (!$user->ip_whitelist_enabled) {
        return $next($request);
    }

    $currentIp = $request->ip();

    // 驗證 IP
    if (!$user->isIpAllowed($currentIp)) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('error', 'IP 位址不在白名單中');
    }

    return $next($request);
}
```

**User Model 方法：**

```php
// app/Models/User.php
public function isIpAllowed(string $ip): bool
{
    if (!$this->ip_whitelist_enabled) {
        return true;
    }

    if (empty($this->ip_whitelist)) {
        return false;
    }

    return in_array($ip, $this->ip_whitelist);
}
```

**管理介面：**

- Super Admin 在 Filament UserResource 中管理
- 支援多 IP 設定（TagsInput）
- 店家無法查看或修改
```

---

#### `docs/architecture/08-rest-api-spec.md` - REST API 規格

**需要新增的內容**:

##### 8.5.7 Security Settings API（新增章節）

```markdown
### 8.5.7 Security Settings API

#### GET /api/user/security-settings - 取得安全設定

**Headers:** `Authorization: Bearer {token}`

**回應 (200 OK):**

```json
{
  "success": true,
  "data": {
    "two_factor_enabled": true,
    "two_factor_confirmed": true,
    "two_factor_confirmed_at": "2025-10-09T10:30:00Z",
    "ip_whitelist_enabled": false,
    "can_manage_2fa": true,
    "can_view_ip_whitelist": false
  }
}
```

---

#### POST /api/user/2fa/enable - 啟用 2FA

**Headers:** `Authorization: Bearer {token}`

**回應 (200 OK):**

```json
{
  "success": true,
  "data": {
    "qr_code_url": "data:image/svg+xml;base64,...",
    "secret": "BASE32ENCODEDSECRET",
    "backup_codes": [
      "12345678",
      "87654321"
    ]
  }
}
```

---

#### POST /api/user/2fa/confirm - 確認 2FA

**Headers:** `Authorization: Bearer {token}`

**請求:**

```json
{
  "code": "123456"
}
```

**回應 (200 OK):**

```json
{
  "success": true,
  "message": "2FA confirmed successfully"
}
```

**回應 (422 Unprocessable Entity):**

```json
{
  "success": false,
  "error": {
    "code": "INVALID_CODE",
    "message": "驗證碼錯誤"
  }
}
```

---

#### DELETE /api/user/2fa - 停用 2FA

**Headers:** `Authorization: Bearer {token}`

**回應 (200 OK):**

```json
{
  "success": true,
  "message": "2FA disabled successfully"
}
```
```

---

#### `docs/architecture.md` - 總體架構文件

**需要更新的章節**:

##### Section 15 連結更新

```markdown
#### [Section 15: Security 安全性](./architecture/15-security.md)
- 認證與授權
  - LINE Login OAuth 2.0
  - Laravel Sanctum (API Token)
  - **雙因素認證 (2FA) - Google Authenticator** ⭐ NEW
  - **Spatie Permission 權限系統** ⭐ NEW
- CSRF Protection
- XSS Prevention
- SQL Injection Prevention
- **IP 白名單** ⭐ NEW
- Rate Limiting
- 敏感資料加密
- Multi-tenancy 隔離
- 安全稽核
```

##### 關鍵技術決策新增

```markdown
### 7. 安全性強化：多層防護機制

**決策：** 實作 2FA + IP 白名單 + 角色權限三層防護

**理由：**
- ✅ 符合 OWASP 安全標準
- ✅ 保護店家敏感資料
- ✅ 防止未授權訪問
- ✅ 符合金融級安全要求（LINE Pay 串接）

**實作內容：**
1. **Google Authenticator 2FA**
   - TOTP 標準實作
   - 臨時關閉功能（24小時）
   - 三重恢復機制

2. **IP 白名單**
   - Middleware 層級攔截
   - Super Admin 統一管理
   - 自動登出機制

3. **Spatie Permission**
   - Super Admin / Store Owner 角色
   - 細緻權限控制
   - Filament 整合
```

---

### 2. 建議更新（重要度：MEDIUM）

#### `docs/prd/非功能需求.md` - 非功能需求

**需要新增的內容**:

```markdown
### 6.1.5 雙因素認證 (2FA)

**需求：** 系統必須支援雙因素認證

**實作方式：**
- Google Authenticator (TOTP)
- 6 位數驗證碼
- 30 秒時間窗口

**覆蓋範圍：**
- ✅ Admin Panel 登入
- ⏳ Customer 登入（未來規劃）

**管理功能：**
- Super Admin 可啟用/停用店家 2FA
- Super Admin 可臨時關閉（24小時）
- 店家可自主管理自己的 2FA
```

```markdown
### 6.1.6 IP 白名單

**需求：** 限制特定 IP 訪問後台

**管理權限：**
- Super Admin 專屬功能
- 店家無法查看或修改

**安全機制：**
- Middleware 層級早期攔截
- IP 不符自動登出
- 支援多 IP 設定
```

---

#### `docs/mvp-development-tasks.md` - MVP 開發任務

**需要新增的完成項目**:

```markdown
## ✅ 已完成任務

### Phase 0: 安全系統 (已完成 - 2025-10-09)

- [x] **Task 0.1**: 實作 Google Authenticator 2FA
  - [x] Google2FA 套件整合
  - [x] QR Code 生成
  - [x] 驗證碼確認流程
  - [x] Filament 登入整合

- [x] **Task 0.2**: 實作 IP 白名單
  - [x] Middleware 實作
  - [x] User Model IP 驗證方法
  - [x] Filament 管理介面
  - [x] 自動登出機制

- [x] **Task 0.3**: 權限系統整合
  - [x] Spatie Permission 安裝
  - [x] Super Admin / Store Owner 角色
  - [x] 權限分離邏輯
  - [x] Filament Policy 整合

- [x] **Task 0.4**: 臨時關閉 2FA
  - [x] 臨時關閉功能
  - [x] 24 小時自動恢復
  - [x] Laravel Scheduler 排程
  - [x] 手動恢復功能

- [x] **Task 0.5**: 測試與文檔
  - [x] 24 個自動化測試
  - [x] 27 個手動測試案例
  - [x] 6 份完整文檔
  - [x] Code Review
```

---

### 3. 可選更新（重要度：LOW）

#### `docs/development-setup.md` - 開發環境配置

**需要新增的內容**:

```markdown
### 4.5 安全功能設定

#### 2FA 測試環境

```bash
# 安裝 Google2FA
composer require pragmarx/google2fa-laravel

# 執行 Migration
php artisan migrate

# 建立測試用 Super Admin
php artisan db:seed --class=SuperAdminSeeder

# 清除快取
php artisan optimize:clear
```

#### 權限系統設定

```bash
# 安裝 Spatie Permission
composer require spatie/laravel-permission

# 發布設定檔
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# 執行 Migration
php artisan migrate

# 建立角色和權限
php artisan db:seed --class=RolePermissionSeeder
```
```

---

## 🎯 更新優先順序建議

### 立即更新（本週內）
1. ✅ **`PROJECT_STATUS.md`** - 已建立並記錄完整進度
2. ⚠️ **`docs/architecture/15-security.md`** - 新增 2FA、IP 白名單、Spatie Permission 章節

### 短期更新（本月內）
3. **`docs/architecture/08-rest-api-spec.md`** - 新增 Security Settings API
4. **`docs/architecture.md`** - 更新索引和技術決策
5. **`docs/prd/非功能需求.md`** - 新增安全需求

### 長期更新（有需要時）
6. **`docs/mvp-development-tasks.md`** - 標記安全系統為已完成
7. **`docs/development-setup.md`** - 新增安全功能設定指南

---

## 📝 更新模板

### 文件更新 Header 建議格式

```markdown
---
**設計版本**：v2.0 (Updated)
**最後更新**：2025-10-09
**更新內容**：新增雙因素認證、IP 白名單、Spatie Permission 章節
**架構師**：Winston (Backend Architect)
**實作者**：Claude Code (AI Assistant)
---
```

### Changelog 格式

```markdown
## 變更歷史

### v2.0 (2025-10-09) - 安全系統強化
- ✅ 新增 Section 15.2.4: 雙因素認證 (2FA)
- ✅ 新增 Section 15.3.3: Spatie Permission 權限系統
- ✅ 新增 Section 15.4.4: IP 白名單
- ✅ 新增 Section 8.5.7: Security Settings API
- ✅ 更新關鍵技術決策（新增第 7 項）

### v1.0 (2025-01-20) - 初始版本
- 完整 15 個章節架構
- 基礎安全性規劃
```

---

## ✅ 實作完成功能總結

為了方便更新規格文件，以下是已完成功能的完整清單：

### 雙因素認證 (2FA)

**技術棧**:
- `pragmarx/google2fa-laravel` ^3.0
- Google Authenticator TOTP 標準
- Filament v4.1.6 Multi-Factor Authentication Provider

**核心檔案**:
- `app/Filament/Auth/Google2FAProvider.php` - 登入驗證 Provider
- `app/Filament/Pages/SecuritySettings.php` - 店家設定頁面
- `app/Models/User.php` - 2FA 相關方法
- `app/Console/Commands/RestoreExpiredTwoFactorDisable.php` - 自動恢復

**功能特性**:
- ✅ QR Code 生成和掃描
- ✅ 6 位數驗證碼確認
- ✅ 登入時 2FA 驗證
- ✅ 臨時關閉（24 小時自動恢復）
- ✅ 三重恢復機制
- ✅ Secret 加密儲存

### IP 白名單

**核心檔案**:
- `app/Http/Middleware/CheckIpWhitelist.php` - IP 檢查中介層
- `app/Filament/Resources/UserResource.php` - 管理介面
- `app/Models/User.php` - IP 驗證方法

**功能特性**:
- ✅ Middleware 層級攔截
- ✅ 多 IP 支援
- ✅ Super Admin 專屬管理
- ✅ 自動登出機制
- ✅ IP 陣列儲存（JSON）

### 權限系統

**技術棧**:
- `spatie/laravel-permission` ^6.9

**核心檔案**:
- `config/permission.php` - 權限設定
- `database/seeders/RolePermissionSeeder.php` - 角色建立
- `database/seeders/SuperAdminSeeder.php` - Super Admin 建立

**角色定義**:
```php
// Super Admin
- 管理所有店家
- IP 白名單管理
- 2FA 啟用/停用
- 臨時關閉 2FA
- 完整系統訪問

// Store Owner
- 管理自己的店家
- 自主管理 2FA
- 無法查看 IP 白名單
- 無法管理其他店家
```

### 資料庫結構

**新增欄位**:
```sql
-- users 表
ip_whitelist_enabled        BOOLEAN
ip_whitelist                JSON
two_factor_enabled          BOOLEAN
two_factor_secret           TEXT (encrypted)
two_factor_recovery_codes   TEXT (encrypted)
two_factor_confirmed_at     TIMESTAMP
two_factor_temp_disabled_at TIMESTAMP
```

**新增表**:
```
- roles
- permissions
- model_has_permissions
- model_has_roles
- role_has_permissions
```

---

## 🔗 相關文檔

- [PROJECT_STATUS.md](../PROJECT_STATUS.md) - 專案進度報告（已完成）
- [SECURITY_README.md](../SECURITY_README.md) - 安全系統總覽
- [SECURITY_SETTINGS_GUIDE.md](../SECURITY_SETTINGS_GUIDE.md) - 使用指南
- [IMPLEMENTATION_SUMMARY.md](../IMPLEMENTATION_SUMMARY.md) - 技術實作總結

---

## 📊 文檔完整度評估

### 目前專案文檔狀態（方案 B）

| 文檔類型 | 檔案名稱 | 完整度 | 說明 |
|---------|---------|--------|------|
| 專案總覽 | `README.md` | ✅ 100% | 已更新至 v2.0，包含安全功能 |
| 進度報告 | `PROJECT_STATUS.md` | ✅ 100% | 完整記錄開發進度與統計 |
| 安全總覽 | `SECURITY_README.md` | ✅ 100% | 安全系統架構說明 |
| 使用指南 | `SECURITY_SETTINGS_GUIDE.md` | ✅ 100% | 2FA 和 IP 白名單操作手冊 |
| 技術實作 | `IMPLEMENTATION_SUMMARY.md` | ✅ 100% | 技術細節與程式碼說明 |
| Code Review | `CODE_REVIEW_REPORT.md` | ✅ 100% | 程式碼審查報告 |
| 版本記錄 | `CHANGELOG.md` | ✅ 100% | 版本變更歷史 |
| 規格建議 | `SPEC_UPDATE_RECOMMENDATIONS.md` | ✅ 100% | 本文件 |
| 測試文檔 | `tests/README_TESTING.md` | ✅ 100% | 測試說明 |
| 手動測試 | `tests/MANUAL_TESTING_GUIDE.md` | ✅ 100% | 27 個測試案例 |

**總計**: 10 份完整文檔，涵蓋專案各個方面。

### 規格文件缺口（方案 A）

如果需要建立正式規格文件，目前缺少：

| 文件類型 | 優先級 | 估計工作量 | 說明 |
|---------|--------|-----------|------|
| 架構規格 | HIGH | 4-6 小時 | `docs/architecture/15-security.md` |
| API 規格 | MEDIUM | 2-3 小時 | `docs/architecture/08-rest-api-spec.md` |
| 非功能需求 | MEDIUM | 1-2 小時 | `docs/prd/非功能需求.md` |
| 架構總索引 | LOW | 1 小時 | `docs/architecture.md` |
| MVP 任務 | LOW | 30 分鐘 | `docs/mvp-development-tasks.md` |

---

## 🎯 建議行動方案

### 對於中小型專案（推薦）
✅ **繼續使用方案 B（現有文檔）**

**理由**：
- 已有 10 份完整文檔，涵蓋所有必要資訊
- 文檔結構清晰，易於維護
- 適合敏捷開發流程
- 避免過度文檔化

**後續行動**：
1. 持續更新 `README.md` 和 `PROJECT_STATUS.md`
2. 新功能開發時更新 `CHANGELOG.md`
3. 重大架構變更時更新相關文檔

### 對於大型專案或企業環境
⚠️ **考慮建立方案 A（正式規格文件）**

**適用情況**：
- 需要正式的技術審查流程
- 多團隊協作開發
- 需要符合企業文檔標準
- 計劃將專案交付給其他團隊維護

**後續行動**：
1. 建立 `docs/` 目錄結構
2. 依照本文件建議建立各規格文件
3. 將現有文檔內容整合到規格文件中
4. 建立文檔維護流程

---

## 📝 結論

**目前狀態**: 592meal 專案已具備完整的文檔系統（方案 B），足以支援中小型專案開發。

**建議**:
1. ✅ **短期**：繼續使用現有文檔系統
2. 📋 **中期**：當專案擴展到一定規模後，考慮建立正式規格文件
3. 🔄 **長期**：定期審查文檔完整度，根據需求調整

**更新頻率建議**：
- 每次重大功能發布後更新 `README.md`
- 每週更新 `PROJECT_STATUS.md`
- 每次提交後更新 `CHANGELOG.md`
- 每個里程碑後進行完整文檔審查

---

**建立日期**: 2025-10-09
**最後更新**: 2025-10-09
**文檔版本**: v1.1
**狀態**: ✅ 完成（已更新說明）
