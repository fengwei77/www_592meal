<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * 獲取購物車內容
     */
    public function getCart(): array
    {
        return Session::get('cart', []);
    }

    /**
     * 將商品加入購物車
     */
    public function add(int $itemId, int $quantity = 1): bool
    {
        $item = MenuItem::find($itemId);

        if (!$item || !$item->is_active || $item->is_sold_out) {
            return false;
        }

        $cart = $this->getCart();

        if (isset($cart[$itemId])) {
            $newQuantity = $cart[$itemId] + $quantity;
            if ($newQuantity > 99) {
                return false; // 超過最大數量限制
            }
            $cart[$itemId] = $newQuantity;
        } else {
            $cart[$itemId] = $quantity;
        }

        Session::put('cart', $cart);
        return true;
    }

    /**
     * 更新購物車商品數量
     */
    public function update(int $itemId, int $quantity): bool
    {
        if ($quantity < 1 || $quantity > 99) {
            return false;
        }

        $cart = $this->getCart();

        if (!isset($cart[$itemId])) {
            return false;
        }

        if ($quantity == 0) {
            unset($cart[$itemId]);
        } else {
            $cart[$itemId] = $quantity;
        }

        Session::put('cart', $cart);
        return true;
    }

    /**
     * 從購物車移除商品
     */
    public function remove(int $itemId): bool
    {
        $cart = $this->getCart();

        if (!isset($cart[$itemId])) {
            return false;
        }

        unset($cart[$itemId]);
        Session::put('cart', $cart);
        return true;
    }

    /**
     * 清空購物車
     */
    public function clear(): void
    {
        Session::forget('cart');
    }

    /**
     * 獲取購物車商品詳細資訊
     */
    public function getCartItems(?int $storeId = null): Collection
    {
        $cart = $this->getCart();
        $items = collect();

        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);

            if ($item && $item->is_active && !$item->is_sold_out) {
                // 如果指定了店家ID，只返回該店家的商品
                if ($storeId && $item->store_id !== $storeId) {
                    continue;
                }

                $items->push([
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'image_url' => null,
                    'quantity' => $quantity,
                    'subtotal' => $item->price * $quantity,
                    'store_id' => $item->store_id,
                    'category_name' => $item->menuCategory?->name,
                ]);
            }
        }

        return $items;
    }

    /**
     * 計算購物車總金額
     */
    public function getTotal(?int $storeId = null): float
    {
        return $this->getCartItems($storeId)->sum('subtotal');
    }

    /**
     * 獲取購物車商品總數量
     */
    public function getTotalQuantity(): int
    {
        return array_sum($this->getCart());
    }

    /**
     * 檢查購物車是否為空
     */
    public function isEmpty(): bool
    {
        return empty($this->getCart());
    }

    /**
     * 檢查購物車是否包含指定店家的商品
     */
    public function containsStoreItems(int $storeId): bool
    {
        return $this->getCartItems($storeId)->isNotEmpty();
    }

    /**
     * 移除指定店家的商品
     */
    public function removeStoreItems(int $storeId): void
    {
        $cart = $this->getCart();
        $updatedCart = [];

        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);

            if ($item && $item->store_id !== $storeId) {
                $updatedCart[$itemId] = $quantity;
            }
        }

        Session::put('cart', $updatedCart);
    }

    /**
     * 驗證購物車商品的有效性
     */
    public function validateCart(?int $storeId = null): array
    {
        $cart = $this->getCart();
        $validItems = [];
        $invalidItems = [];
        $totalAmount = 0;

        foreach ($cart as $itemId => $quantity) {
            $item = MenuItem::find($itemId);

            if (!$item) {
                $invalidItems[] = [
                    'id' => $itemId,
                    'reason' => '商品不存在'
                ];
                continue;
            }

            if (!$item->is_active) {
                $invalidItems[] = [
                    'id' => $itemId,
                    'name' => $item->name,
                    'reason' => '商品已下架'
                ];
                continue;
            }

            if ($item->is_sold_out) {
                $invalidItems[] = [
                    'id' => $itemId,
                    'name' => $item->name,
                    'reason' => '商品已售完'
                ];
                continue;
            }

            if ($storeId && $item->store_id !== $storeId) {
                $invalidItems[] = [
                    'id' => $itemId,
                    'name' => $item->name,
                    'reason' => '商品不屬於當前店家'
                ];
                continue;
            }

            $subtotal = $item->price * $quantity;
            $totalAmount += $subtotal;

            $validItems[] = [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'image_url' => null, // 暫時設置為 null，後續可以添加圖片功能
                'category_name' => $item->menuCategory?->name,
            ];
        }

        return [
            'valid_items' => $validItems,
            'invalid_items' => $invalidItems,
            'total_amount' => $totalAmount,
            'is_valid' => empty($invalidItems),
        ];
    }

    /**
     * 合併購物車（用於登入用戶）
     */
    public function mergeCart(array $sessionCart, array $userCart): array
    {
        $mergedCart = $sessionCart;

        foreach ($userCart as $itemId => $quantity) {
            if (isset($mergedCart[$itemId])) {
                $newQuantity = $mergedCart[$itemId] + $quantity;
                if ($newQuantity <= 99) {
                    $mergedCart[$itemId] = $newQuantity;
                }
            } else {
                $mergedCart[$itemId] = $quantity;
            }
        }

        return $mergedCart;
    }

    /**
     * 獲取購物車統計資訊
     */
    public function getCartStats(?int $storeId = null): array
    {
        $items = $this->getCartItems($storeId);

        return [
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'total_amount' => $items->sum('subtotal'),
            'average_price' => $items->isNotEmpty() ? $items->avg('price') : 0,
            'most_expensive' => $items->max('price'),
            'least_expensive' => $items->min('price'),
        ];
    }
}