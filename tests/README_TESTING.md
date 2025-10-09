# 安全設定系統測試文件

## 📚 測試文件說明

本目錄包含安全設定系統的完整測試套件，包括自動化測試和手動測試指南。

---

## 📁 文件結構

```
tests/
├── Feature/
│   ├── SecuritySettingsTest.php      # 安全設定功能測試
│   ├── IpWhitelistTest.php           # IP 白名單測試
│   └── TwoFactorAuthTest.php         # 2FA 測試
├── MANUAL_TESTING_GUIDE.md           # 手動測試指南
├── run-security-tests.bat            # 測試執行腳本（Windows）
└── README_TESTING.md                 # 本文件
```

---

## 🚀 快速開始

### 方法 1：使用測試腳本（推薦）

**Windows 環境：**
```bash
# 在 tests 目錄中執行
run-security-tests.bat
```

這個腳本會自動：
1. 清除所有快取
2. 重新建立測試資料庫
3. 執行所有安全設定相關測試
4. 顯示測試結果

### 方法 2：手動執行測試

**執行所有安全設定測試：**
```bash
cd D:\laragon\www\oh592meal\www

# 清除快取
php artisan optimize:clear

# 執行所有功能測試
php vendor/bin/phpunit tests/Feature/SecuritySettingsTest.php --testdox
php vendor/bin/phpunit tests/Feature/IpWhitelistTest.php --testdox
php vendor/bin/phpunit tests/Feature/TwoFactorAuthTest.php --testdox
```

**執行特定測試：**
```bash
# 只執行 IP 白名單測試
php vendor/bin/phpunit tests/Feature/IpWhitelistTest.php

# 執行單一測試方法
php vendor/bin/phpunit --filter 用戶可以檢查IP是否在白名單中 tests/Feature/IpWhitelistTest.php
```

**使用 Artisan 執行：**
```bash
php artisan test --filter SecuritySettings
php artisan test --filter IpWhitelist
php artisan test --filter TwoFactorAuth
```

---

## 📋 測試涵蓋範圍

### 1. SecuritySettingsTest.php
測試整體安全設定功能和權限控制

**涵蓋項目：**
- ✅ Super Admin 權限驗證
- ✅ 店家權限驗證
- ✅ 頁面訪問控制
- ✅ IP 白名單啟用/停用
- ✅ 2FA 啟用/停用
- ✅ 店家端設定限制

**測試數量：** 6 個測試

### 2. IpWhitelistTest.php
測試 IP 白名單的所有功能

**涵蓋項目：**
- ✅ IP 驗證邏輯
- ✅ 白名單啟用/停用行為
- ✅ IP 新增/移除功能
- ✅ 中介層攔截機制
- ✅ 白名單為空的處理

**測試數量：** 9 個測試

### 3. TwoFactorAuthTest.php
測試 2FA 雙因素認證功能

**涵蓋項目：**
- ✅ 密鑰生成
- ✅ 2FA 啟用/停用
- ✅ QR Code 驗證
- ✅ 驗證碼驗證
- ✅ 恢復碼生成
- ✅ 2FA 狀態管理
- ✅ 管理員控制功能

**測試數量：** 9 個測試

**總計：24 個自動化測試**

---

## 🧪 測試環境配置

### 資料庫設定

在 `.env.testing` 文件中配置測試資料庫：

```env
APP_ENV=testing
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oh592meal_testing
DB_USERNAME=root
DB_PASSWORD=
```

### 建立測試資料庫

```sql
CREATE DATABASE IF NOT EXISTS oh592meal_testing;
```

---

## 📊 測試報告

### 查看詳細測試報告

**使用 --testdox 格式：**
```bash
php vendor/bin/phpunit tests/Feature/SecuritySettingsTest.php --testdox
```

輸出範例：
```
SecuritySettings (Tests\Feature\SecuritySettingsTest)
 ✔ Super admin 可以訪問用戶管理頁面
 ✔ 一般店家無法訪問用戶管理頁面
 ✔ 所有已登入用戶都可以訪問安全設定頁面
 ✔ 未登入用戶無法訪問安全設定頁面
```

**生成 HTML 報告：**
```bash
php vendor/bin/phpunit --coverage-html coverage
```

報告會生成在 `coverage/` 目錄中。

---

## 🔍 手動測試

完整的手動測試步驟請參考：
**[MANUAL_TESTING_GUIDE.md](./MANUAL_TESTING_GUIDE.md)**

手動測試包含：
- 27 個詳細測試案例
- UI 功能測試
- 跨 IP 測試場景
- Google Authenticator 整合測試
- CLI 命令測試
- 進階場景測試

---

## 🐛 測試失敗時的調試

### 1. 查看詳細錯誤訊息
```bash
php vendor/bin/phpunit tests/Feature/IpWhitelistTest.php --verbose
```

### 2. 檢查日誌
```bash
tail -f storage/logs/laravel.log
```

### 3. 重新建立測試環境
```bash
php artisan migrate:fresh --seed --env=testing
php artisan optimize:clear
```

### 4. 檢查資料庫狀態
```bash
php artisan tinker

# 查看用戶資料
>>> User::all();

# 查看角色
>>> Role::all();

# 查看特定用戶的安全設定
>>> User::find(1)->only(['ip_whitelist_enabled', 'ip_whitelist', 'two_factor_enabled']);
```

---

## 📈 持續整合 (CI/CD)

### GitHub Actions 範例

創建 `.github/workflows/tests.yml`：

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'

      - name: Install dependencies
        run: composer install

      - name: Run tests
        run: |
          php artisan migrate:fresh --seed --env=testing
          php vendor/bin/phpunit tests/Feature/SecuritySettingsTest.php
          php vendor/bin/phpunit tests/Feature/IpWhitelistTest.php
          php vendor/bin/phpunit tests/Feature/TwoFactorAuthTest.php
```

---

## 📝 新增測試

### 新增功能測試

1. 在 `tests/Feature/` 創建新的測試文件
2. 繼承 `Tests\TestCase`
3. 使用 `RefreshDatabase` trait
4. 撰寫測試方法（以 `test_` 開頭或使用 `@test` 註解）

範例：
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 新功能可以正常運作()
    {
        // Arrange（準備）

        // Act（執行）

        // Assert（驗證）
        $this->assertTrue(true);
    }
}
```

---

## 🎯 測試最佳實踐

1. **每個測試只測一件事**
   - 測試應該專注且獨立

2. **使用描述性的測試名稱**
   - 使用繁體中文或英文清楚描述測試內容

3. **遵循 AAA 模式**
   - Arrange（準備）：設定測試數據
   - Act（執行）：執行要測試的動作
   - Assert（驗證）：驗證結果

4. **使用 RefreshDatabase**
   - 確保每個測試都有乾淨的資料庫狀態

5. **測試邊界條件**
   - 測試空值、null、極端值等情況

---

## 📞 支援

如果測試遇到問題：

1. 查看 [MANUAL_TESTING_GUIDE.md](./MANUAL_TESTING_GUIDE.md)
2. 檢查測試文件中的註解
3. 查看 Laravel 測試文檔：https://laravel.com/docs/11.x/testing

---

## 📅 更新記錄

| 日期 | 版本 | 更新內容 |
|------|------|---------|
| 2025-10-09 | 1.0.0 | 初始版本 - 建立所有測試文件 |

---

**最後更新：** 2025-10-09
**維護者：** 開發團隊
