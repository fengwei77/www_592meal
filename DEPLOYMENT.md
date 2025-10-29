# 592Meal 雲端部署指南

## 快速部署

### 1. 系統需求
- Ubuntu 20.04+ 或 CentOS 8+
- Docker 20.10+
- Docker Compose 2.0+

### 2. 部署指令

```bash
# 克隆專案
git clone https://github.com/fengwei77/oh592meal.git
cd oh592meal

# 設定環境變數
cp .env.example .env
nano .env

# 產生金鑰
php artisan key:generate

# 啟動 Docker 服務
docker-compose up -d

# 初始化資料庫
docker-compose exec php bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:clear
php artisan cache:clear
exit

# 查看服務狀態
docker-compose ps
```

### 3. 訪問網站
- 前台: `http://your-domain.com`
- 後台: `http://your-domain.com/admin`
- 訂單管理: `http://your-domain.com/store/{store_slug}/manage/orders`

### 4. 重要 .env 設定

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=oh592meal_prod
DB_USERNAME=oh592meal
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
```

## 詳細說明

### Docker 服務
- **nginx**: Web Server
- **php**: PHP 8.4 + Laravel
- **postgres**: PostgreSQL 18
- **redis**: Redis 7.0
- **reverb**: WebSocket
- **queue**: Laravel Queue Worker
- **scheduler**: Laravel Task Scheduler

### 維護指令
```bash
# 查看日誌
docker-compose logs -f

# 重新啟動服務
docker-compose restart

# 更新程式碼
git pull origin master
docker-compose exec php composer install --optimize-autoloader --no-dev
docker-compose exec php php artisan migrate
```

### 備份
```bash
# 備份資料庫
docker-compose exec postgres pg_dump -U oh592meal oh592meal_prod > backup.sql

# 備份檔案
tar -czf storage_backup.tar.gz storage/app/public/
```

---

**GitHub**: https://github.com/fengwei77/oh592meal
**問題回報**: https://github.com/fengwei77/oh592meal/issues