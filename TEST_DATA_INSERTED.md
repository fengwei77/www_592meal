# ✅ 测试数据已成功插入

**时间**: 2025-11-02
**状态**: 完成

## 📊 数据统计

- **总用户数**: 4
- **Super Admin**: 2
- **Store Owner**: 2
- **总店家数**: 4

## 👥 测试账号

### 1️⃣ Super Admin (现有账号)
```
Email: luke2work@gmail.com
密码: aa123123
角色: super_admin
店家: 0
```

### 2️⃣ Super Admin (新建账号) ⭐ **推荐使用**
```
Email: admin@592meal.com
密码: admin123
角色: super_admin
店家: 1 (測試餐廳 - 管理員)
```

### 3️⃣ Store Owner A
```
Email: owner1@592meal.com
密码: owner123
角色: store_owner
店家: 2 (美味小吃店 A, 咖啡廳 A)
```

### 4️⃣ Store Owner B
```
Email: owner2@592meal.com
密码: owner123
角色: store_owner
店家: 1 (美味小吃店 B)
```

## 🏪 店家数据

### 店家 1: 測試餐廳 - 管理員
- **ID**: 2
- **Subdomain**: test-admin
- **Slug**: test-restaurant-admin
- **店主**: admin@592meal.com
- **类型**: 餐廳 (restaurant)
- **地址**: 台北市信義區信義路五段7號
- **电话**: 02-1234-5678
- **服务模式**: 混合模式 (hybrid)
- **营业时间**: 周一至周五 10:00-21:00, 周六日 11:00-22:00
- **店员密码**: staff123
- **前台网址**: https://app.592meal.online/store/test-restaurant-admin

### 店家 2: 美味小吃店 A
- **ID**: 3
- **Subdomain**: snack-a
- **Slug**: delicious-snack-a
- **店主**: owner1@592meal.com
- **类型**: 小吃店 (snack)
- **地址**: 台北市大安區忠孝東路四段223號
- **电话**: 02-2345-6789
- **服务模式**: 店址取餐 (pickup)
- **营业时间**: 周一至周五 09:00-20:00, 周日 10:00-18:00, 周六公休
- **店员密码**: staff123
- **前台网址**: https://app.592meal.online/store/delicious-snack-a

### 店家 3: 咖啡廳 A
- **ID**: 4
- **Subdomain**: coffee-a
- **Slug**: coffee-shop-a
- **店主**: owner1@592meal.com
- **类型**: 咖啡廳 (cafe)
- **地址**: 台北市中山區南京東路二段100號
- **电话**: 02-3456-7890
- **服务模式**: 店址取餐 (pickup)
- **营业时间**: 周一至周四 08:00-22:00, 周五 08:00-23:00, 周六日 09:00-22:00/23:00
- **店员密码**: staff123
- **前台网址**: https://app.592meal.online/store/coffee-shop-a

### 店家 4: 美味小吃店 B
- **ID**: 5
- **Subdomain**: snack-b
- **Slug**: delicious-snack-b
- **店主**: owner2@592meal.com
- **类型**: 餐廳 (restaurant)
- **地址**: 台北市松山區南京東路五段188號
- **电话**: 02-4567-8901
- **服务模式**: 駐點服務 (onsite)
- **营业时间**: 周一至周六 11:00-20:00, 周日公休
- **店员密码**: staff123
- **前台网址**: https://app.592meal.online/store/delicious-snack-b

## 🔐 登录测试

### 后台登录 (CMS)
```
URL: https://cms.592meal.online
推荐账号: admin@592meal.com
密码: admin123
```

### 预期行为

#### ✅ 权限检查已禁用的情况下
由于权限检查已临时禁用（详见 `PERMISSION_BYPASS_ENABLED.md`），所有已登录用户应该可以：
- 成功登录后台
- 访问 Dashboard
- 查看所有 Resources（店家、用户、角色等）
- 创建/编辑/删除任何数据

#### 测试场景

**场景 A: 使用 admin@592meal.com 登录**
- 应该能够看到所有 4 个店家
- 应该能够访问所有管理功能
- 应该能够查看/编辑所有用户

**场景 B: 使用 owner1@592meal.com 登录**
- 应该能够看到所有 4 个店家（因为权限检查已禁用）
- 正常情况下只应该看到自己的 2 个店家
- 这用于测试权限系统是否工作

**场景 C: 使用 owner2@592meal.com 登录**
- 应该能够看到所有 4 个店家（因为权限检查已禁用）
- 正常情况下只应该看到自己的 1 个店家

## ⚠️ 重要提醒

### 1. 权限检查已临时禁用
当前所有权限检查都已临时禁用，用于排查 403 错误。详细信息请查看：
- `PERMISSION_BYPASS_ENABLED.md`

已修改的文件：
- `app/Models/User.php`
- `app/Filament/Traits/HasResourcePermissions.php`
- `app/Providers/AppServiceProvider.php`
- `app/Filament/Resources/Stores/StoreResource.php`

### 2. 如何恢复权限检查
测试完成后，使用 Git 还原：
```bash
cd /opt/592meal/www
git checkout app/Models/User.php app/Filament/Traits/HasResourcePermissions.php app/Providers/AppServiceProvider.php app/Filament/Resources/Stores/StoreResource.php
docker exec 592meal_php php artisan cache:clear
docker exec 592meal_php php artisan config:clear
cd /opt/592meal && docker compose restart php
```

### 3. 测试建议

#### 步骤 1: 清除浏览器数据
```
1. 按 F12 打开开发者工具
2. Application → Storage → Clear site data
3. 清除所有与 .592meal.online 相关的数据
```

#### 步骤 2: 登录测试
```
1. 访问 https://cms.592meal.online
2. 使用 admin@592meal.com / admin123
3. 观察是否出现 403
```

#### 步骤 3: 如果仍然 403
查看浏览器控制台：
- Network tab → 找到 403 响应
- Console tab → 查看 JavaScript 错误
- Application tab → 检查 Cookie

查看服务器日志：
```bash
docker exec 592meal_php tail -f /var/www/html/www/storage/logs/laravel-$(date +%Y-%m-%d).log
```

## 📝 数据验证结果

### 数据库查询确认
```
用户总数: 4
店家总数: 4

用户列表:
  ID: 1 | Email: luke2work@gmail.com | Roles: super_admin
  ID: 2 | Email: admin@592meal.com | Roles: super_admin
  ID: 3 | Email: owner1@592meal.com | Roles: store_owner
  ID: 4 | Email: owner2@592meal.com | Roles: store_owner

店家列表:
  ID: 2 | Name: 測試餐廳 - 管理員 | Owner: admin@592meal.com | Slug: test-restaurant-admin
  ID: 3 | Name: 美味小吃店 A | Owner: owner1@592meal.com | Slug: delicious-snack-a
  ID: 4 | Name: 咖啡廳 A | Owner: owner1@592meal.com | Slug: coffee-shop-a
  ID: 5 | Name: 美味小吃店 B | Owner: owner2@592meal.com | Slug: delicious-snack-b
```

### 角色权限确认
```
✅ 所有用户都已分配角色
✅ Super Admin 角色拥有所有权限
✅ Store Owner 角色拥有基本权限
✅ 所有用户的 email 都已验证
```

## 🎯 下一步

1. **立即测试登录**: 使用 `admin@592meal.com` / `admin123` 登录后台
2. **观察结果**:
   - 如果成功 → 403 问题确实是权限系统引起的
   - 如果仍然 403 → 问题在浏览器 Cookie/Session 或其他中间件
3. **报告测试结果**: 根据测试结果决定下一步调试方向

---

**插入脚本**: `/opt/592meal/www/insert_test_data.php`
**数据状态**: ✅ 已成功插入并验证
