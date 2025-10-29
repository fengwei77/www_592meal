<?php

namespace Tests\Feature\Ordering;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private array $cartItems;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建一個基本用戶，但不關聯 store 以避免複雜性
        $user = \App\Models\User::factory()->create();

        // 手動創建 Store，避免使用 factory
        $this->store = Store::create([
            'user_id' => $user->id,
            'name' => 'Test Store',
            'subdomain' => 'teststore-' . uniqid(),
            'phone' => '0912345678',
            'address' => 'Test Address',
            'store_type' => 'restaurant',
            'service_mode' => 'pickup',
            'is_active' => true,
        ]);

        // Create menu items
        $item1 = MenuItem::factory()->create([
            'store_id' => $this->store->id,
            'is_active' => true,
            'is_sold_out' => false,
            'price' => 100.00,
            'name' => 'Test Item 1',
        ]);

        $item2 = MenuItem::factory()->create([
            'store_id' => $this->store->id,
            'is_active' => true,
            'is_sold_out' => false,
            'price' => 150.00,
            'name' => 'Test Item 2',
        ]);

        $this->cartItems = [
            $item1->id => 2,
            $item2->id => 1,
        ];

        // Setup cart in session
        session()->put('cart', $this->cartItems);
    }

    /** @test */
    public function it_can_create_order_with_valid_data()
    {
        // Arrange
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '0912345678',
            'notes' => 'Test order notes',
        ];

        // Act
        $response = $this->post("http://{$this->store->subdomain}." . parse_url(config('app.url'), PHP_URL_HOST) . "/checkout", $orderData);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success', '訂單建立成功！');

        // Verify order in database
        $order = Order::where('customer_name', 'John Doe')->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->store->id, $order->store_id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(350.00, $order->total_amount);
        $this->assertEquals('John Doe', $order->customer_name);
        $this->assertEquals('0912345678', $order->customer_phone);
        $this->assertEquals('Test order notes', $order->notes);

        // Verify order items
        $this->assertEquals(2, $order->orderItems()->count());

        $orderItem1 = $order->orderItems()->where('menu_item_id', array_keys($this->cartItems)[0])->first();
        $this->assertEquals(2, $orderItem1->quantity);
        $this->assertEquals(100.00, $orderItem1->unit_price);
        $this->assertEquals(200.00, $orderItem1->total_price);

        // Verify cart is cleared
        $this->assertNull(session('cart'));
    }

    /** @test */
    public function it_fails_to_create_order_with_invalid_data()
    {
        // Arrange - missing required fields
        $orderData = [
            'customer_name' => '',
            'customer_phone' => '',
        ];

        // Act
        $response = $this->post("http://{$this->store->subdomain}." . parse_url(config('app.url'), PHP_URL_HOST) . "/checkout", $orderData);

        // Assert
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    /** @test */
    public function it_fails_to_create_order_with_empty_cart()
    {
        // Arrange
        session()->forget('cart');
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '0912345678',
        ];

        // Act
        $response = $this->post("http://{$this->store->subdomain}." . parse_url(config('app.url'), PHP_URL_HOST) . "/checkout", $orderData);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('error', '購物車是空的');
        $this->assertDatabaseCount('orders', 0);
    }

    /** @test */
    public function it_generates_unique_order_number()
    {
        // Arrange
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '0912345678',
        ];

        // Act - Create first order
        $this->post("/checkout", $orderData);

        // Act - Create second order
        $this->post("/checkout", $orderData);

        // Assert
        $orders = Order::all();
        $this->assertEquals(2, $orders->count());
        $this->assertNotEquals($orders[0]->order_number, $orders[1]->order_number);
        $this->assertMatches('/^ORD\d{14}$/', $orders[0]->order_number);
        $this->assertMatches('/^ORD\d{14}$/', $orders[1]->order_number);
    }

    /** @test */
    public function it_creates_order_items_with_correct_pricing()
    {
        // Arrange
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '0912345678',
        ];

        // Act
        $this->post("/checkout", $orderData);

        // Assert
        $order = Order::first();
        $orderItems = $order->orderItems;

        $this->assertEquals(2, $orderItems->count());

        // Check first item (quantity: 2, price: 100)
        $firstItem = $orderItems->where('menu_item_id', array_keys($this->cartItems)[0])->first();
        $this->assertEquals(2, $firstItem->quantity);
        $this->assertEquals(100.00, $firstItem->unit_price);
        $this->assertEquals(200.00, $firstItem->total_price);

        // Check second item (quantity: 1, price: 150)
        $secondItem = $orderItems->where('menu_item_id', array_keys($this->cartItems)[1])->first();
        $this->assertEquals(1, $secondItem->quantity);
        $this->assertEquals(150.00, $secondItem->unit_price);
        $this->assertEquals(150.00, $secondItem->total_price);
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        // This test simulates a database error during order creation
        // by mocking the Order model to throw an exception

        // Arrange
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '0912345678',
        ];

        // Mock Order model to throw exception
        $orderModel = $this->mock(Order::class);
        $orderModel->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        // Bind the mock
        $this->app->instance(Order::class, $orderModel);

        // Act
        $response = $this->post("http://{$this->store->subdomain}." . parse_url(config('app.url'), PHP_URL_HOST) . "/checkout", $orderData);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('error', '訂單建立失敗，請稍後再試');

        // Verify no order was created and cart is still intact
        $this->assertDatabaseCount('orders', 0);
        $this->assertNotNull(session('cart'));
        $this->assertEquals($this->cartItems, session('cart'));
    }

    /** @test */
    public function it_redirects_to_order_confirmation_page_after_success()
    {
        // Arrange
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '0912345678',
        ];

        // Act
        $response = $this->post("http://{$this->store->subdomain}." . parse_url(config('app.url'), PHP_URL_HOST) . "/checkout", $orderData);

        // Assert
        $order = Order::first();
        $response->assertRedirect("/order/confirmed/{$order->id}");
        $response->assertSessionHas('success', '訂單建立成功！');
    }
}