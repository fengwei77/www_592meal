# 592Meal Phase 1: 店家後台完善計劃

## 📋 專案概述

**目標**: 完善店家後台管理系統，讓店家能夠完整管理店家資訊、菜單和訂單
**時間預估**: 2-3 週
**技術棧**: Laravel 12.32.5 + Filament v4.1.6 + PostgreSQL + Redis

---

## 🎯 Phase 1 核心目標

### 主要功能模組
1. **店家資訊管理系統**
2. **菜單管理系統**
3. **訂單處理系統**

### 成功指標
- ✅ 店家能夠完整設定基本資訊
- ✅ 店家能夠管理完整菜單（新增、編輯、刪除）
- ✅ 店家能夠接收和處理訂單
- ✅ 系統穩定性達到 99.5%

---

## 🏪 模組 1: 店家資訊管理系統

### 1.1 店家基本資料管理

#### 功能需求
- **店家基本資訊**
  - 店家名稱 (必填)
  - 店家描述 (選填)
  - 店家類型 (餐廳、咖啡廳、小吃店等)
  - 營業時間設定
  - 聯絡電話 (必填)
  - 店家地址
  - 經緯度坐標 (自動/手動)
  - 店家圖片 (Logo、店面照片)
  - 社群媒體連結

- **營業時間管理**
  - 週間營業時間
  - 週末營業時間
  - 特殊日期設定 (假日、暫停營業)
  - 自動營業狀態顯示

#### 技術實現
```php
// 資料庫結構設計
Schema::create('stores', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->string('store_type', 50); // restaurant, cafe, snack, etc.
    $table->string('phone', 20);
    $table->string('address');
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    $table->json('business_hours'); // JSON 格式儲存營業時間
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

#### Filament 頁面設計
- StoreResource.php - 店家資源管理
- StoreSettingsPage.php - 店家設定頁面
- BusinessHoursSettingsPage.php - 營業時間設定

### 1.2 權限管理
- 店家只能管理自己的店家資訊
- Super Admin 可以管理所有店家
- 店家擁有者的權限分配

---

## 🍽️ 模組 2: 菜單管理系統

### 2.1 菜單分類管理

#### 功能需求
- **分類結構**
  - 主分類 (例: 主餐、飲料、甜點)
  - 子分類支援 (例: 主餐 > 燴飯、麵食、飯糰)
  - 分類排序功能
  - 分類顯示/隱藏控制

#### 資料庫設計
```php
Schema::create('menu_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained()->onDelete('cascade');
    $table->string('name', 50);
    $table->text('description')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('menu_categories');
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 2.2 菜品管理

#### 功能需求
- **菜品基本資訊**
  - 菜品名稱 (必填)
  - 菜品描述
  - 價格設定
  - 菜品圖片 (支援多張)
  - 營養資訊 (選填)
  - 过敏原資訊
  - 準備時間

- **進階功能**
  - 菜品規格選項 (例: 大小、辣度、加料)
  - 庫存管理
  - 售賣時間限制
  - 菜品狀態 (上架/下架/缺貨)

#### 資料庫設計
```php
Schema::create('menu_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained()->onDelete('cascade');
    $table->foreignId('category_id')->constrained('menu_categories');
    $table->string('name', 100);
    $table->text('description');
    $table->decimal('price', 8, 2);
    $table->string('image_url')->nullable();
    $table->integer('prep_time_minutes')->default(0); // 準備時間
    $table->json('nutrition_info')->nullable(); // 營養資訊 JSON
    $table->json('allergen_info')->nullable(); // 過敏原資訊 JSON
    $table->integer('stock_quantity')->default(-1); // -1 表示無限庫存
    $table->boolean('is_available')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});

// 菜品選項 (規格)
Schema::create('menu_item_options', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_item_id')->constrained()->onDelete('cascade');
    $table->string('option_name', 50); // 例: 大小、辣度
    $table->string('option_type', 20); // single, multiple
    $table->boolean('is_required')->default(false);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});

// 菜品選項值
Schema::create('menu_item_option_values', function (Blueprint $table) {
    $table->id();
    $table->foreignId('option_id')->constrained()->onDelete('cascade');
    $table->string('value_name', 50); // 例: 大杯、中杯、小杯
    $table->decimal('price_modifier', 8, 2)->default(0); // 價格調整
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### 2.3 Filament 管理界面
- MenuItemResource.php - 菜品資源管理
- MenuCategoryResource.php - 分類管理
- MenuItemOptionResource.php - 規格選項管理

---

## 📦 模組 3: 訂單處理系統

### 3.1 訂單基本架構

#### 功能需求
- **訂單接收**
  - 即時訂單通知
  - 訂單狀態管理
  - 訂單確認流程
  - 自動編號系統

- **訂單內容**
  - 訂單項目明細
  - 數量與規格
  - 價格計算
  - 備註資訊

#### 資料庫設計
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number', 20)->unique(); // 訂單編號
    $table->foreignId('store_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->nullable()->constrained('users');
    $table->string('customer_name', 100);
    $table->string('customer_phone', 20);
    $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled']);
    $table->decimal('subtotal', 10, 2);
    $table->decimal('tax', 8, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->text('notes')->nullable();
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});

Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->foreignId('menu_item_id')->constrained();
    $table->string('item_name', 100); // 複製菜品名稱，防止菜品變更影響訂單
    $table->decimal('unit_price', 8, 2);
    $table->integer('quantity');
    $table->decimal('subtotal', 10, 2);
    $table->json('selected_options')->nullable(); // 選擇的規格
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 3.2 訂單狀態管理

#### 狀態流程
1. **pending** (待確認) → **confirmed** (已確認)
2. **confirmed** → **preparing** (準備中)
3. **preparing** → **ready** (準備完成)
4. **ready** → **completed** (已完成)
5. **任何狀態** → **cancelled** (已取消)

#### 即時通知
- 新訂單通知
- 狀態變更通知
- WebSocket 即時更新

### 3.3 訂單管理界面
- OrderResource.php - 訂單管理
- OrderViewPage.php - 訂單詳情查看
- OrderStatusChangeAction.php - 狀態變更操作

---

## 🔧 技術實現細節

### 檔案結構規劃
```
app/
├── Models/
│   ├── Store.php
│   ├── MenuCategory.php
│   ├── MenuItem.php
│   ├── MenuItemOption.php
│   ├── Order.php
│   └── OrderItem.php
├── Filament/
│   └── Resources/
│       ├── StoreResource.php
│       ├── MenuCategoryResource.php
│       ├── MenuItemResource.php
│       └── OrderResource.php
├── Http/
│   ├── Controllers/
│   │   ├── StoreController.php
│   │   ├── MenuController.php
│   │   └── OrderController.php
│   └── Requests/
│       ├── StoreRequest.php
│       ├── MenuItemRequest.php
│       └── OrderRequest.php
└── Services/
    ├── OrderService.php
    ├── MenuService.php
    └── StoreService.php
```

### 主要依賴套件
```json
{
    "require": {
        "filament/filament": "^4.1",
        "spatie/laravel-permission": "^6.21",
        "intervention/image": "^3.0",
        "pusher/pusher-php-server": "^7.0"
    }
}
```

### 系統整合點
- **圖片處理**: Intervention Image
- **即時通訊**: Pusher/WebSockets
- **地理位置**: Google Maps API
- **通知系統**: Laravel Notification + Email/SMS

---

## 📅 開發時間線

### 第一週: 店家資訊管理 (5-7 天)
- Day 1-2: Store Model + Migration + Controller
- Day 3-4: Filament StoreResource + 頁面開發
- Day 5-6: 營業時間管理 + 圖片上傳
- Day 7: 測試與修正

### 第二週: 菜單管理系統 (7 天)
- Day 1-2: MenuCategory Model + Resource
- Day 3-5: MenuItem Model + 複雜表單設計
- Day 6-7: 菜品選項/規格系統 + 測試

### 第三週: 訂單系統 (5-7 天)
- Day 1-2: Order Model + 基本邏輯
- Day 3-4: 狀態管理 + 即時通知
- Day 5-6: Filament 訂單管理界面
- Day 7: 整合測試與部署準備

---

## 🧪 測試策略

### 測試類型
1. **單元測試** - Model 關聯、業務邏輯
2. **功能測試** - API 端點、表單提交
3. **整合測試** - 跨模組功能流程
4. **用戶驗收測試** - 實際店家操作流程

### 關鍵測試案例
- 店家註冊完成後可以立即設定店家資訊
- 菜單項目新增後能正確顯示在店家頁面
- 訂單狀態變更能即時反映在管理界面
- 權限控制確保店家只能管理自己的資料

---

## 🚀 部署準備

### 環境需求
- PHP 8.4+
- PostgreSQL 13+
- Redis 6+
- Nginx/Apache
- SSL 憑證

### 上線檢查清單
- [ ] 所有資料庫 Migration 執行
- [ ] 權限角色正確設定
- [ ] 圖片上傳路徑權限
- [ ] 環境變數配置
- [ ] SSL 憑證設定
- [ ] 備份策略建立
- [ ] 監控系統設定

---

## 📞 聯絡與支援

**技術顧問**: BMad Master
**專案狀態**: 進行中
**下次更新**: 第一週結束時

如需技術支援或專案諮詢，隨時聯繫 BMad Master！