<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * 顯示店家菜單首頁
     */
    public function index(Request $request): View
    {
        $store = $request->get('current_store');

        // 獲取所有啟用的菜單分類，按排序順序
        $categories = $store->menuCategories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['menuItems' => function($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order');
            }])
            ->get();

        // 獲取所有菜單項目（不依賴分類）
        $allItems = $store->menuItems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.menu.index', compact('store', 'categories', 'allItems'));
    }

    /**
     * 顯示特定分類的菜單項目
     */
    public function category(Request $request, MenuCategory $category): View
    {
        $store = $request->get('current_store');

        // 確認分類屬於當前店家
        if ($category->store_id !== $store->id) {
            abort(404, '菜單分類不存在');
        }

        if (!$category->is_active) {
            abort(404, '菜單分類未啟用');
        }

        // 獲取該分類的所有菜單項目
        $items = $category->menuItems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // 獲取所有其他分類（用於導航）
        $otherCategories = $store->menuCategories()
            ->where('is_active', true)
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.menu.category', compact('store', 'category', 'items', 'otherCategories'));
    }

    /**
     * 搜尋菜單項目
     */
    public function search(Request $request): View
    {
        $store = $request->get('current_store');
        $query = $request->get('q', '');

        if (empty($query)) {
            return redirect()->route('frontend.menu.index');
        }

        // 搜尋菜單項目
        $items = $store->menuItems()
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%");
            })
            ->orderBy('sort_order')
            ->get();

        return view('frontend.menu.search', compact('store', 'items', 'query'));
    }

    /**
     * API: 獲取菜單項目詳情
     */
    public function getItemDetails(Request $request, MenuItem $item)
    {
        $store = $request->get('current_store');

        // 確認菜單項目屬於當前店家
        if ($item->store_id !== $store->id || !$item->is_active) {
            return response()->json(['error' => '菜單項目不存在'], 404);
        }

        return response()->json([
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $item->price,
            'image_url' => $item->getImageUrl(),
            'category' => $item->menuCategory ? $item->menuCategory->name : null,
            'is_available' => $item->is_available,
            'preparation_time' => $item->preparation_time ?? null,
            'ingredients' => $item->ingredients ?? null,
            'allergens' => $item->allergens ?? null,
        ]);
    }
}