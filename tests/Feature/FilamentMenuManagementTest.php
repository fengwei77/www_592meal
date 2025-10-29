<?php

use App\Filament\Resources\Menu\MenuCategoryResource;
use App\Filament\Resources\Menu\MenuCategoryResource\Pages\CreateMenuCategory;
use App\Filament\Resources\Menu\MenuCategoryResource\Pages\EditMenuCategory;
use App\Filament\Resources\Menu\MenuCategoryResource\Pages\ListMenuCategories;
use App\Filament\Resources\Menu\MenuItemResource;
use App\Filament\Resources\Menu\MenuItemResource\Pages\CreateMenuItem;
use App\Filament\Resources\Menu\MenuItemResource\Pages\EditMenuItem;
use App\Filament\Resources\Menu\MenuItemResource\Pages\ListMenuItems;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Store;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // 建立權限
    $permissions = [
        'view_menu_categories',
        'create_menu_categories',
        'edit_menu_categories',
        'delete_menu_categories',
        'view_menu_items',
        'create_menu_items',
        'edit_menu_items',
        'delete_menu_items',
        'view_store',
        'edit_store',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission], ['guard_name' => 'web']);
    }

    // 建立角色
    $superAdmin = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
    $storeOwner = Role::firstOrCreate(['name' => 'Store Owner'], ['guard_name' => 'web']);

    // Super Admin 擁有所有權限
    $superAdmin->syncPermissions(Permission::all());

    // Store Owner 擁有指定權限
    $storeOwner->syncPermissions($permissions);
});

// ========================================
// Menu Category Resource Tests
// ========================================

describe('MenuCategoryResource - List Page', function () {
    test('Super Admin can view all menu categories from all stores', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $category1 = MenuCategory::factory()->create(['store_id' => $store1->id]);
        $category2 = MenuCategory::factory()->create(['store_id' => $store2->id]);

        livewire(ListMenuCategories::class)
            ->actingAs($superAdmin)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$category1, $category2]);
    });

    test('Store Owner can only see their own store menu categories', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $ownStore = Store::factory()->create(['user_id' => $storeOwner->id]);
        $otherStore = Store::factory()->create();

        $ownCategory = MenuCategory::factory()->create(['store_id' => $ownStore->id]);
        $otherCategory = MenuCategory::factory()->create(['store_id' => $otherStore->id]);

        livewire(ListMenuCategories::class)
            ->actingAs($storeOwner)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$ownCategory])
            ->assertCanNotSeeTableRecords([$otherCategory]);
    });

    test('table displays correct columns', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        livewire(ListMenuCategories::class)
            ->actingAs($storeOwner)
            ->assertSuccessful()
            ->assertTableColumnExists('icon')
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('store.name')
            ->assertTableColumnExists('menuItems_count')
            ->assertTableColumnExists('is_active');
    });
});

describe('MenuCategoryResource - Create Page', function () {
    test('can load create page', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        Store::factory()->create(['user_id' => $storeOwner->id]);

        livewire(CreateMenuCategory::class)
            ->actingAs($storeOwner)
            ->assertSuccessful()
            ->assertFormExists();
    });

    test('can create menu category with valid data', function () {
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

        livewire(CreateMenuCategory::class)
            ->actingAs($storeOwner)
            ->fillForm($categoryData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_categories', [
            'name' => '主食類',
            'store_id' => $store->id,
            'description' => '各式主食餐點',
        ]);
    });

    test('validates required fields', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        Store::factory()->create(['user_id' => $storeOwner->id]);

        livewire(CreateMenuCategory::class)
            ->actingAs($storeOwner)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    });

    test('validates name max length', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        Store::factory()->create(['user_id' => $storeOwner->id]);

        livewire(CreateMenuCategory::class)
            ->actingAs($storeOwner)
            ->fillForm([
                'name' => str_repeat('a', 256), // 超過 255 字元
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    });

    test('automatically assigns store_id to authenticated Store Owner', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        livewire(CreateMenuCategory::class)
            ->actingAs($storeOwner)
            ->fillForm([
                'name' => '測試分類',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // 驗證自動指派的 store_id
        $this->assertDatabaseHas('menu_categories', [
            'name' => '測試分類',
            'store_id' => $store->id,
        ]);
    });
});

describe('MenuCategoryResource - Edit Page', function () {
    test('can load edit page with existing data', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        livewire(EditMenuCategory::class, ['record' => $category->id])
            ->actingAs($storeOwner)
            ->assertSuccessful()
            ->assertFormSet([
                'name' => $category->name,
                'description' => $category->description,
                'icon' => $category->icon,
                'is_active' => $category->is_active,
            ]);
    });

    test('can update menu category', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        $newData = [
            'name' => '更新的分類名稱',
            'description' => '更新的描述',
            'is_active' => false,
        ];

        livewire(EditMenuCategory::class, ['record' => $category->id])
            ->actingAs($storeOwner)
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_categories', [
            'id' => $category->id,
            'name' => '更新的分類名稱',
            'description' => '更新的描述',
            'is_active' => false,
        ]);
    });

    test('Store Owner cannot edit other store category', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        Store::factory()->create(['user_id' => $storeOwner->id]);

        $otherStore = Store::factory()->create();
        $otherCategory = MenuCategory::factory()->create(['store_id' => $otherStore->id]);

        livewire(EditMenuCategory::class, ['record' => $otherCategory->id])
            ->actingAs($storeOwner)
            ->assertForbidden();
    });

    test('can delete category', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        livewire(EditMenuCategory::class, ['record' => $category->id])
            ->actingAs($storeOwner)
            ->callAction(DeleteAction::class);

        $this->assertDatabaseMissing('menu_categories', [
            'id' => $category->id,
        ]);
    });
});

// ========================================
// Menu Item Resource Tests
// ========================================

describe('MenuItemResource - List Page', function () {
    test('Super Admin can view all menu items from all stores', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        $item1 = MenuItem::factory()->create(['store_id' => $store1->id]);
        $item2 = MenuItem::factory()->create(['store_id' => $store2->id]);

        livewire(ListMenuItems::class)
            ->actingAs($superAdmin)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$item1, $item2]);
    });

    test('Store Owner can only see their own store menu items', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $ownStore = Store::factory()->create(['user_id' => $storeOwner->id]);
        $otherStore = Store::factory()->create();

        $ownItem = MenuItem::factory()->create(['store_id' => $ownStore->id]);
        $otherItem = MenuItem::factory()->create(['store_id' => $otherStore->id]);

        livewire(ListMenuItems::class)
            ->actingAs($storeOwner)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$ownItem])
            ->assertCanNotSeeTableRecords([$otherItem]);
    });

    test('can filter by category', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);

        $category1 = MenuCategory::factory()->create(['store_id' => $store->id, 'name' => '主食']);
        $category2 = MenuCategory::factory()->create(['store_id' => $store->id, 'name' => '飲料']);

        $item1 = MenuItem::factory()->create(['store_id' => $store->id, 'category_id' => $category1->id]);
        $item2 = MenuItem::factory()->create(['store_id' => $store->id, 'category_id' => $category2->id]);

        livewire(ListMenuItems::class)
            ->actingAs($storeOwner)
            ->filterTable('category_id', $category1->id)
            ->assertCanSeeTableRecords([$item1])
            ->assertCanNotSeeTableRecords([$item2]);
    });
});

describe('MenuItemResource - Create Page', function () {
    test('can load create page', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        Store::factory()->create(['user_id' => $storeOwner->id]);

        livewire(CreateMenuItem::class)
            ->actingAs($storeOwner)
            ->assertSuccessful()
            ->assertFormExists();
    });

    test('can create menu item with valid data', function () {
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

        livewire(CreateMenuItem::class)
            ->actingAs($storeOwner)
            ->fillForm($itemData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_items', [
            'name' => '招牌滷肉飯',
            'store_id' => $store->id,
            'price' => 6500, // 資料庫儲存為分（cents）
        ]);
    });

    test('validates required fields', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        Store::factory()->create(['user_id' => $storeOwner->id]);

        livewire(CreateMenuItem::class)
            ->actingAs($storeOwner)
            ->fillForm([
                'name' => null,
                'price' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'price' => 'required']);
    });

    test('validates price is numeric and positive', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        livewire(CreateMenuItem::class)
            ->actingAs($storeOwner)
            ->fillForm([
                'name' => '測試餐點',
                'category_id' => $category->id,
                'price' => -10,
            ])
            ->call('create')
            ->assertHasFormErrors(['price']);
    });

    test('automatically assigns store_id to authenticated Store Owner', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $category = MenuCategory::factory()->create(['store_id' => $store->id]);

        livewire(CreateMenuItem::class)
            ->actingAs($storeOwner)
            ->fillForm([
                'name' => '測試餐點',
                'category_id' => $category->id,
                'price' => 50,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_items', [
            'name' => '測試餐點',
            'store_id' => $store->id,
        ]);
    });
});

describe('MenuItemResource - Edit Page', function () {
    test('can load edit page with existing data', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $item = MenuItem::factory()->create(['store_id' => $store->id]);

        livewire(EditMenuItem::class, ['record' => $item->id])
            ->actingAs($storeOwner)
            ->assertSuccessful()
            ->assertFormSet([
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price / 100, // 轉換回元
                'is_active' => $item->is_active,
                'is_featured' => $item->is_featured,
            ]);
    });

    test('can update menu item', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $item = MenuItem::factory()->create(['store_id' => $store->id]);

        $newData = [
            'name' => '更新的餐點名稱',
            'price' => 100,
            'is_featured' => true,
        ];

        livewire(EditMenuItem::class, ['record' => $item->id])
            ->actingAs($storeOwner)
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'name' => '更新的餐點名稱',
            'price' => 10000,
            'is_featured' => true,
        ]);
    });

    test('Store Owner cannot edit other store menu item', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        Store::factory()->create(['user_id' => $storeOwner->id]);

        $otherStore = Store::factory()->create();
        $otherItem = MenuItem::factory()->create(['store_id' => $otherStore->id]);

        livewire(EditMenuItem::class, ['record' => $otherItem->id])
            ->actingAs($storeOwner)
            ->assertForbidden();
    });

    test('can soft delete menu item', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $store = Store::factory()->create(['user_id' => $storeOwner->id]);
        $item = MenuItem::factory()->create(['store_id' => $store->id]);

        livewire(EditMenuItem::class, ['record' => $item->id])
            ->actingAs($storeOwner)
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('menu_items', [
            'id' => $item->id,
        ]);
    });
});

// ========================================
// Permission-Based Access Control Tests
// ========================================

describe('Permission Enforcement', function () {
    test('user without create_menu_categories permission cannot access create page', function () {
        $user = User::factory()->create();
        // 不給任何權限

        Store::factory()->create(['user_id' => $user->id]);

        livewire(CreateMenuCategory::class)
            ->actingAs($user)
            ->assertForbidden();
    });

    test('user with create_menu_categories permission can access create page', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('create_menu_categories');

        Store::factory()->create(['user_id' => $user->id]);

        livewire(CreateMenuCategory::class)
            ->actingAs($user)
            ->assertSuccessful();
    });

    test('Store Owner role has all menu management permissions', function () {
        $storeOwner = User::factory()->create();
        $storeOwner->assignRole('Store Owner');

        $this->assertTrue($storeOwner->can('view_menu_categories'));
        $this->assertTrue($storeOwner->can('create_menu_categories'));
        $this->assertTrue($storeOwner->can('edit_menu_categories'));
        $this->assertTrue($storeOwner->can('delete_menu_categories'));
        $this->assertTrue($storeOwner->can('view_menu_items'));
        $this->assertTrue($storeOwner->can('create_menu_items'));
        $this->assertTrue($storeOwner->can('edit_menu_items'));
        $this->assertTrue($storeOwner->can('delete_menu_items'));
    });
});
