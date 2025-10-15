# Bootstrap App 錯誤修復

## 🐛 錯誤描述

**錯誤訊息：**
```
Fatal error: Uncaught ReflectionException: Class "env" does not exist
```

**發生位置：** `bootstrap/app.php:16`

**錯誤原因：**
在 `bootstrap/app.php` 中使用了 `app()->environment('testing')`，但在這個階段 Laravel 應用容器還未完全初始化，導致無法解析 `env` 類別。

---

## ✅ 修復方案

### 修改前（錯誤）

```php
->withMiddleware(function (Middleware $middleware): void {
    // 在測試環境中不檢查網域，避免干擾測試
    if (!app()->environment('testing')) {  // ❌ app() 容器尚未初始化
        $middleware->web(append: [
            \App\Http\Middleware\CheckAdminDomain::class,
        ]);
    }
})
```

### 修改後（正確）

```php
->withMiddleware(function (Middleware $middleware): void {
    // 在測試環境中不檢查網域，避免干擾測試
    // 直接檢查環境變數，避免過早使用 app() 容器
    $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';

    if ($env !== 'testing') {  // ✅ 直接讀取環境變數
        $middleware->web(append: [
            \App\Http\Middleware\CheckAdminDomain::class,
        ]);
    }
})
```

---

## 🔍 技術細節

### 為什麼不能使用 app() 輔助函數？

在 `bootstrap/app.php` 的 `withMiddleware()` 閉包中：

1. **應用容器尚未完全啟動** - Laravel 還在初始化階段
2. **服務提供者未載入** - 許多核心服務（包括 env）尚未註冊
3. **依賴注入不可用** - 容器綁定尚未建立

### 正確的環境檢查方式

在 bootstrap 階段應該使用：

```php
// ✅ 方法 1: 直接讀取超全域變數
$env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';

// ✅ 方法 2: 使用 getenv() 函數
$env = getenv('APP_ENV') ?: 'production';

// ❌ 方法 3: 使用 app() 容器（在 bootstrap 階段不可用）
$env = app()->environment(); // 會拋出錯誤
```

---

## 🧪 驗證修復

### 1. 清除快取

```bash
php artisan optimize:clear
```

### 2. 運行測試

```bash
php artisan test

# 結果：
# Tests:    7 skipped, 59 passed (208 assertions)
# Duration: 4.27s
```

### 3. 瀏覽器測試

```
✅ https://oh592meal.test - 前台正常運作
✅ https://cms.oh592meal.test - 後台正常運作
❌ https://oh592meal.test/admin - 正確返回 404
```

---

## 📚 相關知識

### Laravel 啟動順序

```
1. public/index.php
   ↓
2. bootstrap/app.php
   ├─ Application::configure()
   ├─ withRouting()
   ├─ withMiddleware() ← 我們在這裡（容器未完全初始化）
   ├─ withSchedule()
   └─ withExceptions()
   ↓
3. ServiceProvider::register()
   ↓
4. ServiceProvider::boot()
   ↓
5. 應用程式完全啟動 ← app() 輔助函數才可用
```

### 環境變數讀取優先順序

Laravel 12 中環境變數的讀取順序：

1. `$_ENV` - PHP 超全域變數
2. `$_SERVER` - 伺服器和執行環境資訊
3. `getenv()` - PHP 環境變數函數
4. `.env` 檔案（透過 Dotenv 載入）

---

## ⚠️ 注意事項

### 在 bootstrap/app.php 中應該避免的操作

```php
// ❌ 使用 app() 輔助函數
app('config')
app()->make('something')
app()->environment()

// ❌ 使用 Facade
Config::get('app.env')
Log::info('something')
DB::table('users')->get()

// ❌ 使用需要容器的輔助函數
config('app.env')
logger('message')
cache('key')

// ✅ 可以安全使用
$_ENV['APP_ENV']
$_SERVER['APP_ENV']
getenv('APP_ENV')
dirname(__DIR__)
__DIR__ . '/path'
```

### 測試環境檢查的最佳實踐

```php
// ✅ 推薦：直接讀取環境變數
$env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
$isTesting = $env === 'testing';

// ✅ 也可行：使用 getenv()
$isTesting = getenv('APP_ENV') === 'testing';

// ❌ 避免：在 bootstrap 階段使用 app()
$isTesting = app()->environment('testing');
```

---

## 📝 變更歷史

| 日期 | 版本 | 變更內容 |
|------|------|----------|
| 2025-10-10 | 1.0 | 初次修復：將 `app()->environment()` 改為直接讀取環境變數 |

---

**修復者：** Claude Code
**測試狀態：** ✅ 所有測試通過
**部署狀態：** ✅ 可安全部署
