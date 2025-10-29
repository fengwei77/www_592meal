<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建角色
        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Store Owner']);
    }

    // ========== Menu Category Tests ==========

    /** @test */
    public function super_admin_can_view_all_menu_categories()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $category1 = MenuCategory::factory()->create(['store_id' => $store1->id]);
        $category2 = MenuCategory::factory()->create(['store_id' => $store2->id]);

        $response = $this->actingAs($superAdmin)
            ->get(route('filament.admin.resources.menu.menu-categories.index'));

        $response->assertOk();
        $response->assertSee($category1->name);
        $response->assertSee($category2->name);
    }

    /** @test */
    public function store_owner_can_only_view_their_own_menu_categories()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $ownStore = Store::factory()->create(['user_id' => $storeOwner->id]);
        $otherStore = Store::factory()->create();

        $ownCategory = MenuCategory::factory()->create(['store_id' => $ownStore->id]);
        $otherCategory = MenuCategory::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($storeOwner)
            ->get(route('filament.admin.resources.menu.menu-categories.index'));

        $response->assertOk();
        $response->assertSee($ownCategory->name);
        $response->assertDontSee($otherCategory->name);
    }

    /** @test */
    public function store_owner_can_create_menu_category()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        $categoryData = [
            'name' => '主食類',
            'description' => '各式主食餐點',
            'icon' => '🍚',
            'display_order' => 1,
            'is_active' => true,
        ];

        $response = $this->actingAs($storeOwner)
            ->post(route('filament.admin.resources.menu.menu-categories.store'), $categoryData);

        $this->assertDatabaseHas('menu_categories', [
            'name' => '主食類',
            'store_id' => $store->id,
        ]);
    }

    /** @test */
    public function store_owner_can_update_own_menu_category()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        $updateData = [
            'name' => '更新的分類名稱',
            'description' => $category->description,
            'display_order' => $category->display_order,
            'is_active' => $category->is_active,
        ];

        $response = $this->actingAs($storeOwner)
            ->put(route('filament.admin.resources.menu.menu-categories.update', $category), $updateData);

        $this->assertDatabaseHas('menu_categories', [
            'id' => $category->id,
            'name' => '更新的分類名稱',
        ]);
    }

    /** @test */
    public function store_owner_cannot_update_other_store_menu_category()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $ownStore = Store::factory()->create(['user_id' => $storeOwner->id]);

        $otherStore = Store::factory()->create();
        $otherCategory = MenuCategory::factory()->create(['store_id' => $otherStore->id]);

        $updateData = [
            'name' => '嘗試更新',
            'description' => $otherCategory->description,
            'display_order' => $otherCategory->display_order,
            'is_active' => $otherCategory->is_active,
        ];

        $response = $this->actingAs($storeOwner)
            ->put(route('filament.admin.resources.menu.menu-categories.update', $otherCategory), $updateData);

        $response->assertForbidden();
    }

    /** @test */
    public function store_owner_can_delete_own_menu_category()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($storeOwner)
            ->delete(route('filament.admin.resources.menu.menu-categories.destroy', $category));

        $this->assertDatabaseMissing('menu_categories', [
            'id' => $category->id,
        ]);
    }

    /** @test */
    public function menu_category_has_correct_relationships()
    {
        $store = Store::factory()->create();
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);
        $menuItem = MenuItem::factory()->create(['category_id' => $category->id]);

        // 測試 store 關聯
        $this->assertInstanceOf(Store::class, $category->store);
        $this->assertEquals($store->id, $category->store->id);

        // 測試 menuItems 關聯
        $this->assertTrue($category->menuItems->contains($menuItem));
    }

    /** @test */
    public function menu_category_scopes_work_correctly()
    {
        $store = Store::factory()->create();

        $activeCategory = MenuCategory::factory()->create([
            'store_id' => $store->id,
            'is_active' => true,
            'display_order' => 2,
        ]);

        $inactiveCategory = MenuCategory::factory()->create([
            'store_id' => $store->id,
            'is_active' => false,
            'display_order' => 1,
        ]);

        // 測試 active scope
        $activeCategories = MenuCategory::active()->get();
        $this->assertTrue($activeCategories->contains($activeCategory));
        $this->assertFalse($activeCategories->contains($inactiveCategory));

        // 測試 ordered scope
        $orderedCategories = MenuCategory::ordered()->get();
        $this->assertEquals($inactiveCategory->id, $orderedCategories->first()->id);
    }

    // ========== Menu Item Tests ==========

    /** @test */
    public function super_admin_can_view_all_menu_items()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $item1 = MenuItem::factory()->create(['store_id' => $store1->id]);
        $item2 = MenuItem::factory()->create(['store_id' => $store2->id]);

        $response = $this->actingAs($superAdmin)
            ->get(route('filament.admin.resources.menu.menu-items.index'));

        $response->assertOk();
        $response->assertSee($item1->name);
        $response->assertSee($item2->name);
    }

    /** @test */
    public function store_owner_can_only_view_their_own_menu_items()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $ownStore = Store::factory()->create(['user_id' => $storeOwner->id]);
        $otherStore = Store::factory()->create();

        $ownItem = MenuItem::factory()->create(['store_id' => $ownStore->id]);
        $otherItem = MenuItem::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($storeOwner)
            ->get(route('filament.admin.resources.menu.menu-items.index'));

        $response->assertOk();
        $response->assertSee($ownItem->name);
        $response->assertDontSee($otherItem->name);
    }

    /** @test */
    public function store_owner_can_create_menu_item()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        $itemData = [
            'category_id' => $category->id,
            'name' => '招牌滷肉飯',
            'description' => '香濃美味的滷肉飯',
            'price' => 65,
            'is_active' => true,
            'is_featured' => false,
            'is_sold_out' => false,
            'display_order' => 1,
        ];

        $response = $this->actingAs($storeOwner)
            ->post(route('filament.admin.resources.menu.menu-items.store'), $itemData);

        $this->assertDatabaseHas('menu_items', [
            'name' => '招牌滷肉飯',
            'store_id' => $store->id,
            'price' => 65,
        ]);
    }

    /** @test */
    public function store_owner_can_update_own_menu_item()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        $item = MenuItem::factory()->create(['store_id' => $store->id]);

        $updateData = [
            'category_id' => $item->category_id,
            'name' => '更新的餐點名稱',
            'description' => $item->description,
            'price' => 100,
            'is_active' => true,
            'is_featured' => true,
            'is_sold_out' => false,
            'display_order' => $item->display_order,
        ];

        $response = $this->actingAs($storeOwner)
            ->put(route('filament.admin.resources.menu.menu-items.update', $item), $updateData);

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'name' => '更新的餐點名稱',
            'price' => 100,
            'is_featured' => true,
        ]);
    }

    /** @test */
    public function store_owner_cannot_update_other_store_menu_item()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $ownStore = Store::factory()->create(['user_id' => $storeOwner->id]);

        $otherStore = Store::factory()->create();
        $otherItem = MenuItem::factory()->create(['store_id' => $otherStore->id]);

        $updateData = [
            'category_id' => $otherItem->category_id,
            'name' => '嘗試更新',
            'description' => $otherItem->description,
            'price' => $otherItem->price,
            'is_active' => $otherItem->is_active,
            'is_featured' => $otherItem->is_featured,
            'is_sold_out' => $otherItem->is_sold_out,
            'display_order' => $otherItem->display_order,
        ];

        $response = $this->actingAs($storeOwner)
            ->put(route('filament.admin.resources.menu.menu-items.update', $otherItem), $updateData);

        $response->assertForbidden();
    }

    /** @test */
    public function store_owner_can_soft_delete_own_menu_item()
    {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        $item = MenuItem::factory()->create(['store_id' => $store->id]);

        $response = $this->actingAs($storeOwner)
            ->delete(route('filament.admin.resources.menu.menu-items.destroy', $item));

        // 軟刪除，所以資料庫中還存在，但有 deleted_at
        $this->assertSoftDeleted('menu_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function menu_item_has_correct_relationships()
    {
        $store = Store::factory()->create();
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);
        $item = MenuItem::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
        ]);

        // 測試 store 關聯
        $this->assertInstanceOf(Store::class, $item->store);
        $this->assertEquals($store->id, $item->store->id);

        // 測試 category 關聯
        $this->assertInstanceOf(MenuCategory::class, $item->category);
        $this->assertEquals($category->id, $item->category->id);
    }

    /** @test */
    public function menu_item_scopes_work_correctly()
    {
        $store = Store::factory()->create();

        $activeItem = MenuItem::factory()->create([
            'store_id' => $store->id,
            'is_active' => true,
            'is_featured' => false,
            'is_sold_out' => false,
            'display_order' => 2,
        ]);

        $featuredItem = MenuItem::factory()->featured()->create([
            'store_id' => $store->id,
            'is_active' => true,
            'is_sold_out' => false,
            'display_order' => 1,
        ]);

        $soldOutItem = MenuItem::factory()->soldOut()->create([
            'store_id' => $store->id,
            'is_active' => true,
        ]);

        $inactiveItem = MenuItem::factory()->inactive()->create([
            'store_id' => $store->id,
        ]);

        // 測試 active scope
        $activeItems = MenuItem::active()->get();
        $this->assertTrue($activeItems->contains($activeItem));
        $this->assertTrue($activeItems->contains($featuredItem));
        $this->assertFalse($activeItems->contains($inactiveItem));

        // 測試 featured scope
        $featuredItems = MenuItem::featured()->get();
        $this->assertTrue($featuredItems->contains($featuredItem));
        $this->assertFalse($featuredItems->contains($activeItem));

        // 測試 available scope (is_active && !is_sold_out)
        $availableItems = MenuItem::available()->get();
        $this->assertTrue($availableItems->contains($activeItem));
        $this->assertTrue($availableItems->contains($featuredItem));
        $this->assertFalse($availableItems->contains($soldOutItem));
        $this->assertFalse($availableItems->contains($inactiveItem));

        // 測試 ordered scope
        $orderedItems = MenuItem::ordered()->get();
        $this->assertEquals($featuredItem->id, $orderedItems->first()->id);
    }

    /** @test */
    public function menu_item_helper_methods_work_correctly()
    {
        $item = MenuItem::factory()->create([
            'is_active' => true,
            'is_sold_out' => false,
            'price' => 65,
        ]);

        // 測試 isAvailable 方法
        $this->assertTrue($item->isAvailable());

        $item->is_sold_out = true;
        $this->assertFalse($item->isAvailable());

        $item->is_active = false;
        $item->is_sold_out = false;
        $this->assertFalse($item->isAvailable());

        // 測試 formatted_price 屬性
        $item->price = 65;
        $this->assertEquals('$65', $item->formatted_price);
    }

    /** @test */
    public function menu_item_can_belong_to_category()
    {
        $store = Store::factory()->create();
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        $item = MenuItem::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
        ]);

        // 測試分類包含此餐點
        $this->assertTrue($category->menuItems->contains($item));

        // 測試 activeMenuItems 關聯
        $this->assertTrue($category->activeMenuItems->contains($item));
    }

    /** @test */
    public function deleting_category_sets_menu_items_category_to_null()
    {
        $store = Store::factory()->create();
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);
        $item = MenuItem::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
        ]);

        $category->delete();

        $item->refresh();
        $this->assertNull($item->category_id);
    }
}
