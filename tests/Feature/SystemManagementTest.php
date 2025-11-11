<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Services\SystemStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Filament\Facades\Filament;

/**
 * System Management Test
 *
 * 測試系統管理頁面的各種功能
 */
class SystemManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 設置測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 創建 Super Admin 用戶
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
        $this->superAdmin->givePermissionTo('access_system_management');
        $this->superAdmin->givePermissionTo('view_system_statistics');
        $this->superAdmin->givePermissionTo('manage_orders');
        $this->superAdmin->givePermissionTo('manual_payment_processing');

        // 創建普通店家用戶
        $this->storeOwner = User::factory()->create();
        $this->storeOwner->assignRole('store_owner');

        // 創建測試訂單
        $this->testOrder = Order::factory()->create([
            'user_id' => $this->storeOwner->id,
            'total_amount' => 1500, // 3個月訂閱
            'status' => 'pending',
            'payment_method' => 'credit_card',
        ]);
    }

    /**
     * 測試 Super Admin 可以訪問系統管理頁面
     */
    public function test_super_admin_can_access_system_management(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin/system-management');

        $response->assertStatus(200);
    }

    /**
     * 測試普通店家無法訪問系統管理頁面
     */
    public function test_store_owner_cannot_access_system_management(): void
    {
        $this->actingAs($this->storeOwner);

        $response = $this->get('/admin/system-management');

        $response->assertStatus(403);
    }

    /**
     * 測試統計服務功能
     */
    public function test_system_statistics_service(): void
    {
        $service = new SystemStatisticsService();

        // 測試總體統計
        $overallStats = $service->getOverallStats();
        $this->assertArrayHasKey('total_users', $overallStats);
        $this->assertArrayHasKey('subscribed_users', $overallStats);
        $this->assertArrayHasKey('total_revenue', $overallStats);
        $this->assertArrayHasKey('total_orders', $overallStats);

        // 測試訂單統計
        $orderStats = $service->getOrderStats();
        $this->assertArrayHasKey('completed', $orderStats);
        $this->assertArrayHasKey('pending', $orderStats);
        $this->assertArrayHasKey('failed', $orderStats);
        $this->assertArrayHasKey('cancelled', $orderStats);

        // 測試今日統計
        $todayStats = $service->getTodayStats();
        $this->assertArrayHasKey('new_registrations', $todayStats);
        $this->assertArrayHasKey('website_logins', $todayStats);
        $this->assertArrayHasKey('admin_logins_success', $todayStats);
        $this->assertArrayHasKey('admin_logins_failed', $todayStats);
    }

    /**
     * 測試訂單狀態更新功能
     */
    public function test_order_status_update(): void
    {
        $this->actingAs($this->superAdmin);

        // 測試標記為已付款
        $response = $this->patch("/admin/orders/{$this->testOrder->id}/mark-as-paid", [
            'notes' => '手動處理付款'
        ]);

        $this->testOrder->refresh();

        $this->assertEquals('paid', $this->testOrder->status);
        $this->assertNotNull($this->testOrder->paid_at);
        $this->assertEquals('手動處理付款', $this->testOrder->payment_notes);

        // 檢查用戶訂閱期限是否更新
        $this->storeOwner->refresh();
        $this->assertNotNull($this->storeOwner->subscription_ends_at);
    }

    /**
     * 測試權限檢查
     */
    public function test_permission_checks(): void
    {
        // 測試沒有權限的 Super Admin
        $superAdminWithoutPermission = User::factory()->create();
        $superAdminWithoutPermission->assignRole('super_admin');
        // 不給予 access_system_management 權限

        $this->actingAs($superAdminWithoutPermission);

        $response = $this->get('/admin/system-management');
        $response->assertStatus(403);
    }

    /**
     * 測試日誌記錄功能
     */
    public function test_login_logging(): void
    {
        // 測試登入成功日誌
        $this->actingAs($this->superAdmin);

        $this->assertDatabaseHas('admin_login_logs', [
            'user_id' => $this->superAdmin->id,
            'email' => $this->superAdmin->email,
            'success' => true,
        ]);

        // 測試登入失敗日誌
        $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertDatabaseHas('admin_login_logs', [
            'email' => 'wrong@example.com',
            'success' => false,
        ]);
    }

    /**
     * 測試訂閱相關統計
     */
    public function test_subscription_statistics(): void
    {
        $service = new SystemStatisticsService();

        // 創建一些訂閱用戶
        $activeUser = User::factory()->create([
            'subscription_ends_at' => now()->addMonths(3),
        ]);

        $expiredUser = User::factory()->create([
            'subscription_ends_at' => now()->subMonth(),
        ]);

        $stats = $service->getSubscriptionStats();

        $this->assertGreaterThan(0, $stats['active_subscriptions']);
        $this->assertGreaterThan(0, $stats['expired_subscriptions']);
    }
}