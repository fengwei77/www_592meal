# 超級管理員配置說明

## 環境變數配置

在 `.env` 檔案中添加以下配置來自定義超級管理員帳號：

```env
# Super Admin Configuration (for setup:system command)
SUPER_ADMIN_NAME=Super Admin
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=admin123456
```

## 不同環境的配置範例

### 開發環境 (.env)
```env
SUPER_ADMIN_NAME=Dev Admin
SUPER_ADMIN_EMAIL=dev@example.com
SUPER_ADMIN_PASSWORD=dev123456
```

### 正式環境 (.env)
```env
SUPER_ADMIN_NAME=Production Admin
SUPER_ADMIN_EMAIL=admin@yourdomain.com
SUPER_ADMIN_PASSWORD=your_secure_password_here
```

### 測試環境 (.env.testing)
```env
SUPER_ADMIN_NAME=Test Admin
SUPER_ADMIN_EMAIL=test@example.com
SUPER_ADMIN_PASSWORD=test123456
```

## 使用方法

1. 修改 `.env` 檔案中的 `SUPER_ADMIN_*` 變數
2. 執行系統初始化指令：
   ```bash
   php artisan setup:system
   ```
3. 系統會自動使用 `.env` 中配置的帳號密碼

## 安全建議

- 正式環境請使用強密碼
- 首次登入後請立即修改密碼
- 不要在版本控制中提交包含真實密碼的 `.env` 檔案
- 建議定期更換超級管理員密碼

## 配置優先級

1. 環境變數 (.env)
2. 配置檔案預設值 (config/super_admin.php)
3. 程式碼硬編碼預設值

## 故障排除

如果配置沒有生效，請檢查：

1. `.env` 檔案是否正確配置
2. 執行 `php artisan config:clear` 清除配置快取
3. 確認環境變數名稱正確無誤
4. 檢查配置檔案是否正確載入