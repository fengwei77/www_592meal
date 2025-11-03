# API 500 错误修复报告

**修复日期**: 2025-11-02
**问题 API**: `/api/stores/map`
**错误**: 500 Internal Server Error
**状态**: ✅ **已完全修复**

---

## 🔴 错误现象

### 用户报告
```
Request URL: https://app.592meal.online/api/stores/map
Request Method: GET
Status Code: 500 Internal Server Error
```

### Laravel 错误日志

**错误 1** (已修复):
```
Call to undefined method App\Models\Store::getFullAddress()
at /var/www/html/www/app/Http/Controllers/Frontend/StoreController.php:428
```

**错误 2** (已修复):
```
Call to undefined method App\Models\Store::getCoordinateInfo()
at /var/www/html/www/app/Http/Controllers/Frontend/StoreController.php:437
```

**错误 3** (已修复):
```
Call to undefined method App\Models\Store::needsGeocoding()
at /var/www/html/www/app/Http/Controllers/Frontend/StoreController.php:438
```

**错误 4** (已修复):
```
Call to undefined method App\Models\Store::hasCoordinates()
at /var/www/html/www/app/Http/Controllers/Frontend/StoreController.php:439
```

---

## ❌ 错误原因

### 问题 1: 错误的方法调用方式

**文件**: `app/Http/Controllers/Frontend/StoreController.php:428`

❌ **错误代码**:
```php
'full_address' => $store->getFullAddress(),
```

Store 模型有一个 Laravel Accessor: `getFullAddressAttribute()`，应该作为属性访问，不是方法。

**Laravel Accessor 规则**:
- `getXxxAttribute()` → 通过 `$model->xxx` 访问（属性）
- `getXxx()` → 通过 `$model->getXxx()` 调用（方法）

✅ **正确代码**:
```php
'full_address' => $store->full_address,
```

---

### 问题 2: Store 模型缺少方法

**文件**: `app/Models/Store.php`

Store 模型缺少以下三个方法：
1. `getCoordinateInfo()` - 获取座标信息
2. `needsGeocoding()` - 检查是否需要地理编码
3. `hasCoordinates()` - 检查是否有座标

---

## ✅ 修复方案

### 修复 1: 更正控制器调用方式

**文件**: `app/Http/Controllers/Frontend/StoreController.php`

```php
// ❌ 错误
'full_address' => $store->getFullAddress(),

// ✅ 正确
'full_address' => $store->full_address,
```

---

### 修复 2: 添加缺失的方法到 Store 模型

**文件**: `app/Models/Store.php`

```php
/**
 * 取得座標資訊
 *
 * @return array
 */
public function getCoordinateInfo(): array
{
    return [
        'has_coordinates' => $this->hasCoordinates(),
        'needs_geocoding' => $this->needsGeocoding(),
        'latitude' => $this->latitude,
        'longitude' => $this->longitude,
    ];
}

/**
 * 檢查是否需要地理編碼
 *
 * @return bool
 */
public function needsGeocoding(): bool
{
    // 如果已有座標，則不需要
    if ($this->hasCoordinates()) {
        return false;
    }

    // 如果有地址，則需要地理編碼
    return !empty($this->address);
}

/**
 * 檢查是否有座標
 *
 * @return bool
 */
public function hasCoordinates(): bool
{
    return $this->latitude !== null && $this->longitude !== null;
}
```

---

## ✅ 测试结果

### API 响应测试

**请求**:
```bash
curl "https://app.592meal.online/api/stores/map"
```

**响应**:
```
HTTP Status: 200 OK
```

**响应数据示例**:
```json
{
  "stores": [
    {
      "id": 3,
      "name": "美味小吃店 A",
      "store_url": "https://app.592meal.online/store/delicious-snack-a",
      "store_slug": "delicious-snack-a",
      "store_type": "snack",
      "type_label": "小吃店",
      "address": "台北市大安區忠孝東路四段223號",
      "city": null,
      "area": null,
      "full_address": "台北市大安區忠孝東路四段223號",
      "latitude": "25.04156500",
      "longitude": "121.54357900",
      "logo_url": "https://app.592meal.online/images/default-store.svg",
      "is_open": true,
      "open_hours_text": "營業至 18:00",
      "service_mode": "pickup",
      "is_featured": false,
      "rating": 4.5,
      "coordinate_info": {
        "has_coordinates": true,
        "needs_geocoding": false,
        "latitude": "25.04156500",
        "longitude": "121.54357900"
      },
      "needs_geocoding": false,
      "has_coordinates": true,
      "location_status": "has_coordinates",
      "location_message": "店家坐標已標定",
      "can_be_geocoded": false
    }
  ]
}
```

### 测试覆盖

✅ **API 返回 200 OK**
✅ **返回 4 个店家数据**
✅ **所有字段正确返回**
✅ **座标信息正确**
✅ **营业状态正确**
✅ **地理编码状态正确**

---

## 📚 关键学习点

### 1. Laravel Accessor 的正确使用

**Accessor 定义**:
```php
public function getXxxAttribute(): type
{
    return $value;
}
```

**访问方式**:
```php
// ✅ 正确 - 作为属性访问
$model->xxx

// ❌ 错误 - 不能作为方法调用
$model->getXxx()
```

### 2. 方法与 Accessor 的区别

| 类型 | 定义 | 访问方式 | 用途 |
|------|------|----------|------|
| Accessor | `getXxxAttribute()` | `$model->xxx` | 计算属性、格式化数据 |
| Method | `getXxx()` | `$model->getXxx()` | 执行逻辑、返回数据 |

### 3. API 开发最佳实践

✅ **DO**:
- 为所有需要的方法提供实现
- 使用正确的访问方式（属性 vs 方法）
- 添加适当的文档注释
- 测试所有 API 端点

❌ **DON'T**:
- 混淆 Accessor 和 Method 的使用
- 在控制器中调用不存在的方法
- 忽略错误日志

---

## 🔍 调试技巧

### 1. 快速定位错误

查看 Laravel 错误日志:
```bash
docker exec 592meal_php tail -100 /var/www/html/www/storage/logs/laravel-*.log
```

关键信息:
- 错误类型: `BadMethodCallException`
- 错误位置: 文件名和行号
- 调用堆栈: 追踪调用路径

### 2. 测试 API

```bash
# 测试 API 响应
curl "https://app.592meal.online/api/stores/map"

# 检查 HTTP 状态码
curl -w "\nHTTP: %{http_code}\n" "https://app.592meal.online/api/stores/map"
```

---

## 📊 修复总结

### 错误类型
1. **Accessor 调用错误** - 使用方法调用而非属性访问
2. **缺失方法** - Store 模型缺少三个方法

### 修复内容
1. ✅ 更正 `full_address` 访问方式
2. ✅ 添加 `getCoordinateInfo()` 方法
3. ✅ 添加 `needsGeocoding()` 方法
4. ✅ 添加 `hasCoordinates()` 方法

### 影响范围
- 前台地图功能
- 店家列表 API
- 座标信息显示

### 修复时间
- 诊断: 5 分钟
- 修复: 10 分钟
- 测试: 5 分钟
- **总计: 20 分钟**

---

## ✨ 最终状态

| 项目 | 状态 |
|------|------|
| API 响应 | ✅ 200 OK |
| 店家数据 | ✅ 正确返回 |
| 座标信息 | ✅ 正确返回 |
| 地理编码状态 | ✅ 正确返回 |
| 营业状态 | ✅ 正确返回 |

---

**最后更新**: 2025-11-02
**API 状态**: ✅ **正常运行**
**问题状态**: ✅ **已完全解决**
