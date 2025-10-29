<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * 顯示購物車內容
     */
    public function index(Request $request): View|RedirectResponse
    {
        // 嘗試從 session 或其他地方獲取店家信息
        $store = $request->get('current_store');

        $cart = session()->get('cart', []);
        $cartItems = [];
        $total = 0;

        // 如果沒有店家資訊，嘗試從購物車商品自動判斷店家
        if (!$store && !empty($cart)) {
            $storeIds = [];
            foreach ($cart as $itemId => $quantity) {
                $item = MenuItem::find($itemId);
                if ($item && $item->is_active) {
                    $storeIds[] = $item->store_id;
                }
            }

            // 去除重複的店家 ID
            $uniqueStoreIds = array_unique($storeIds);

            // 如果所有商品都來自同一個店家，自動設置該店家
            if (count($uniqueStoreIds) === 1) {
                $store = \App\Models\Store::find($uniqueStoreIds[0]);
            }
        }

        // 如果沒有店家資訊，顯示通用購物車（顯示所有商品）
        if (!$store) {
            foreach ($cart as $itemId => $quantity) {
                $item = MenuItem::find($itemId);

                if ($item && $item->is_active) {
                    $subtotal = $item->price * $quantity;
                    $cartItems[] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'image_url' => null, // 暫時設置為 null，後續可以添加圖片功能
                        'store_name' => $item->store->name ?? '未知店家',
                    ];
                    $total += $subtotal;
                }
            }

            // 如果是 AJAX 請求，返回 sidebar 視圖
            if ($request->ajax() || $request->wantsJson()) {
                return view('frontend.cart.sidebar', compact('cartItems', 'total'))
                    ->with('current_store', null);
            }

            return view('frontend.cart.generic', compact('cartItems', 'total'));
        }

        // 有店家資訊時，只顯示該店家的商品
        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);

            if ($item && $item->is_active && $item->store_id === $store->id) {
                $subtotal = $item->price * $quantity;
                $cartItems[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'image_url' => null, // 暫時設置為 null，後續可以添加圖片功能
                ];
                $total += $subtotal;
            }
        }

        // 如果是 AJAX 請求，返回 sidebar 視圖
        if ($request->ajax() || $request->wantsJson()) {
            return view('frontend.cart.sidebar', compact('cartItems', 'total'))
                ->with('current_store', $store);
        }

        return view('frontend.cart.index', compact('store', 'cartItems', 'total'));
    }

    /**
     * 顯示特定店家的購物車內容
     */
    public function storeCartIndex(Request $request, $store_slug): View
    {
        // 根據 store_slug 獲取店家信息
        $store = \App\Models\Store::where('store_slug_name', $store_slug)
                                     ->where('is_active', true)
                                     ->firstOrFail();

        // 設置到請求中以便視圖使用
        $request->merge(['current_store' => $store]);
        $cart = session()->get('cart', []);

        $cartItems = [];
        $total = 0;

        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);

            if ($item && $item->is_active && $item->store_id === $store->id) {
                $subtotal = $item->price * $quantity;
                $cartItems[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'image_url' => null, // 暫時設置為 null，後續可以添加圖片功能
                ];
                $total += $subtotal;
            }
        }

        // 如果是 AJAX 請求，返回 sidebar 視圖
        if ($request->ajax() || $request->wantsJson()) {
            return view('frontend.cart.sidebar', compact('cartItems', 'total'))
                ->with('current_store', $store);
        }

        return view('frontend.cart.index', compact('store', 'cartItems', 'total'));
    }

    /**
     * 通用更新購物車商品數量 (不依賴店家)
     */
    public function update(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity');

        $cart = session()->get('cart', []);

        if ($quantity < 1) {
            unset($cart[$itemId]);
        } else {
            $cart[$itemId] = $quantity;
        }

        session()->put('cart', $cart);

        // 計算新的總數量和總金額
        $totalQuantity = array_sum($cart);
        $totalAmount = $this->calculateCartTotal($cart);

        return response()->json([
            'success' => true,
            'message' => '購物車已更新',
            'cart_count' => $totalQuantity,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * 通用從購物車移除商品 (不依賴店家)
     */
    public function remove(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:menu_items,id',
        ]);

        $itemId = $request->input('item_id');
        $cart = session()->get('cart', []);

        if (isset($cart[$itemId])) {
            unset($cart[$itemId]);
            session()->put('cart', $cart);
        }

        // 計算新的總數量和總金額
        $totalQuantity = array_sum($cart);
        $totalAmount = $this->calculateCartTotal($cart);

        return response()->json([
            'success' => true,
            'message' => '商品已從購物車移除',
            'cart_count' => $totalQuantity,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * 將商品加入購物車
     */
    public function add(Request $request, $store_slug)
    {
        $request->validate([
            'item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        // 根據 store_slug 獲取店家信息
        $store = \App\Models\Store::where('store_slug_name', $store_slug)
                                     ->where('is_active', true)
                                     ->firstOrFail();

        // 設置到請求中以便視圖使用
        $request->merge(['current_store' => $store]);
        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity', 1);

        $item = MenuItem::find($itemId);

        // 確認商品屬於當前店家且可用
        if (!$item || $item->store_id !== $store->id || !$item->is_active || $item->is_sold_out) {
            return response()->json([
                'success' => false,
                'message' => '商品不可用'
            ], 400);
        }

        $cart = session()->get('cart', []);
        $cart[$itemId] = isset($cart[$itemId]) ? $cart[$itemId] + $quantity : $quantity;
        session()->put('cart', $cart);

        // 計算購物車總數量
        $totalQuantity = array_sum($cart);

        return response()->json([
            'success' => true,
            'message' => '商品已加入購物車',
            'cart_count' => $totalQuantity,
        ]);
    }

    /**
     * 更新店家特定購物車商品數量
     */
    public function updateForStore(Request $request, $store_slug)
    {
        $request->validate([
            'item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        // 根據 store_slug 獲取店家信息
        $store = \App\Models\Store::where('store_slug_name', $store_slug)
                                     ->where('is_active', true)
                                     ->firstOrFail();

        // 設置到請求中以便視圖使用
        $request->merge(['current_store' => $store]);

        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity');

        $cart = session()->get('cart', []);

        if ($quantity < 1) {
            unset($cart[$itemId]);
        } else {
            $cart[$itemId] = $quantity;
        }

        session()->put('cart', $cart);

        // 計算新的總數量和總金額
        $totalQuantity = array_sum($cart);
        $totalAmount = $this->calculateCartTotal($cart);

        return response()->json([
            'success' => true,
            'message' => '購物車已更新',
            'cart_count' => $totalQuantity,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * 從店家特定購物車移除商品
     */
    public function removeFromStore(Request $request, $store_slug)
    {
        $request->validate([
            'item_id' => 'required|exists:menu_items,id',
        ]);

        // 根據 store_slug 獲取店家信息
        $store = \App\Models\Store::where('store_slug_name', $store_slug)
                                     ->where('is_active', true)
                                     ->firstOrFail();

        // 設置到請求中以便視圖使用
        $request->merge(['current_store' => $store]);

        $itemId = $request->input('item_id');
        $cart = session()->get('cart', []);

        if (isset($cart[$itemId])) {
            unset($cart[$itemId]);
            session()->put('cart', $cart);
        }

        // 計算新的總數量和總金額
        $totalQuantity = array_sum($cart);
        $totalAmount = $this->calculateCartTotal($cart);

        return response()->json([
            'success' => true,
            'message' => '商品已從購物車移除',
            'cart_count' => $totalQuantity,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * 清空購物車
     */
    public function clear()
    {
        session()->forget('cart');

        return response()->json([
            'success' => true,
            'message' => '購物車已清空',
            'cart_count' => 0,
            'total_amount' => 0,
        ]);
    }

    /**
     * 計算購物車總金額
     */
    private function calculateCartTotal(array $cart): float
    {
        $total = 0;

        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);
            if ($item) {
                $total += $item->price * $quantity;
            }
        }

        return $total;
    }

    /**
     * 獲取購物車數量（API）
     */
    public function getCount()
    {
        $cart = session()->get('cart', []);
        $count = array_sum($cart);

        return response()->json([
            'count' => $count,
        ]);
    }
}