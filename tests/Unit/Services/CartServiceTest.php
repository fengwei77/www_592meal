<?php

namespace Tests\Unit\Services;

use App\Models\MenuItem;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cartService;
    private Store $store;
    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
        $this->store = Store::factory()->create();
        $this->menuItem = MenuItem::factory()->create([
            'store_id' => $this->store->id,
            'is_active' => true,
            'is_sold_out' => false,
            'price' => 100.00,
        ]);
    }

    /** @test */
    public function it_can_add_item_to_cart()
    {
        // Act
        $result = $this->cartService->add($this->menuItem->id, 1);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(1, $this->cartService->getTotalQuantity());
    }

    /** @test */
    public function it_cannot_add_inactive_item_to_cart()
    {
        // Arrange
        $this->menuItem->update(['is_active' => false]);

        // Act
        $result = $this->cartService->add($this->menuItem->id, 1);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(0, $this->cartService->getTotalQuantity());
    }

    /** @test */
    public function it_cannot_add_sold_out_item_to_cart()
    {
        // Arrange
        $this->menuItem->update(['is_sold_out' => true]);

        // Act
        $result = $this->cartService->add($this->menuItem->id, 1);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(0, $this->cartService->getTotalQuantity());
    }

    /** @test */
    public function it_can_update_item_quantity()
    {
        // Arrange
        $this->cartService->add($this->menuItem->id, 1);

        // Act
        $result = $this->cartService->update($this->menuItem->id, 3);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(3, $this->cartService->getTotalQuantity());
    }

    /** @test */
    public function it_can_remove_item_from_cart()
    {
        // Arrange
        $this->cartService->add($this->menuItem->id, 2);

        // Act
        $result = $this->cartService->remove($this->menuItem->id);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(0, $this->cartService->getTotalQuantity());
    }

    /** @test */
    public function it_can_clear_cart()
    {
        // Arrange
        $this->cartService->add($this->menuItem->id, 2);

        // Act
        $this->cartService->clear();

        // Assert
        $this->assertTrue($this->cartService->isEmpty());
        $this->assertEquals(0, $this->cartService->getTotalQuantity());
    }

    /** @test */
    public function it_calculates_total_correctly()
    {
        // Arrange
        $menuItem2 = MenuItem::factory()->create([
            'store_id' => $this->store->id,
            'is_active' => true,
            'is_sold_out' => false,
            'price' => 150.00,
        ]);

        // Act
        $this->cartService->add($this->menuItem->id, 2);
        $this->cartService->add($menuItem2->id, 1);

        // Assert
        $total = $this->cartService->getTotal($this->store->id);
        $this->assertEquals(350.00, $total); // (100 * 2) + (150 * 1)
    }

    /** @test */
    public function it_validates_cart_correctly()
    {
        // Arrange
        $inactiveItem = MenuItem::factory()->create([
            'store_id' => $this->store->id,
            'is_active' => false,
            'price' => 80.00,
        ]);

        $otherStore = Store::factory()->create();
        $otherStoreItem = MenuItem::factory()->create([
            'store_id' => $otherStore->id,
            'is_active' => true,
            'price' => 120.00,
        ]);

        // Add items to cart - directly manipulate session for invalid items
        session()->put('cart', [
            $this->menuItem->id => 2,
            $inactiveItem->id => 1,
            $otherStoreItem->id => 1,
        ]);

        // Act
        $validation = $this->cartService->validateCart($this->store->id);

        // Assert
        $this->assertFalse($validation['is_valid']);
        $this->assertEquals(1, count($validation['valid_items'])); // Only the active item from this store
        $this->assertEquals(2, count($validation['invalid_items'])); // Inactive item and other store item
        $this->assertEquals(200.00, $validation['total_amount']); // Only valid item: 100 * 2
    }

    /** @test */
    public function it_returns_empty_cart_items_when_cart_is_empty()
    {
        // Act
        $items = $this->cartService->getCartItems($this->store->id);

        // Assert
        $this->assertTrue($items->isEmpty());
    }

    /** @test */
    public function it_returns_cart_items_for_specific_store()
    {
        // Arrange
        $otherStore = Store::factory()->create();
        $otherStoreItem = MenuItem::factory()->create([
            'store_id' => $otherStore->id,
            'is_active' => true,
            'price' => 120.00,
        ]);

        $this->cartService->add($this->menuItem->id, 2);
        $this->cartService->add($otherStoreItem->id, 1);

        // Act
        $items = $this->cartService->getCartItems($this->store->id);

        // Assert
        $this->assertEquals(1, $items->count());
        $this->assertEquals($this->menuItem->id, $items->first()['id']);
        $this->assertEquals(200.00, $items->first()['subtotal']);
    }

    /** @test */
    public function it_removes_store_items_when_requested()
    {
        // Arrange
        $otherStore = Store::factory()->create();
        $otherStoreItem = MenuItem::factory()->create([
            'store_id' => $otherStore->id,
            'is_active' => true,
            'price' => 120.00,
        ]);

        $this->cartService->add($this->menuItem->id, 2);
        $this->cartService->add($otherStoreItem->id, 1);

        // Act
        $this->cartService->removeStoreItems($this->store->id);

        // Assert
        $this->assertFalse($this->cartService->containsStoreItems($this->store->id));
        $this->assertTrue($this->cartService->containsStoreItems($otherStore->id));
    }

    /** @test */
    public function it_prevents_duplicate_quantity_when_exceeding_limit()
    {
        // Arrange
        $this->cartService->add($this->menuItem->id, 99);

        // Act
        $result = $this->cartService->add($this->menuItem->id, 1);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(99, $this->cartService->getTotalQuantity());
    }

    /** @test */
    public function it_provides_cart_statistics()
    {
        // Arrange
        $menuItem2 = MenuItem::factory()->create([
            'store_id' => $this->store->id,
            'is_active' => true,
            'price' => 150.00,
        ]);

        $this->cartService->add($this->menuItem->id, 2);
        $this->cartService->add($menuItem2->id, 1);

        // Act
        $stats = $this->cartService->getCartStats($this->store->id);

        // Assert
        $this->assertEquals(2, $stats['total_items']);
        $this->assertEquals(3, $stats['total_quantity']);
        $this->assertEquals(350.00, $stats['total_amount']);
        $this->assertEquals(100.00, $stats['least_expensive']);
        $this->assertEquals(150.00, $stats['most_expensive']);
        $this->assertEquals(125.00, $stats['average_price']); // (100+150)/2 - average of unique items
    }
}