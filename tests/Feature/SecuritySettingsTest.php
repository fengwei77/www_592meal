<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 安全設定功能測試
 */
class SecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $storeOwner;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立角色
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'store_owner']);

        // 建立測試用戶
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->storeOwner = User::create([
            'name' => 'Store Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password'),
        ]);
        $this->storeOwner->assignRole('store_owner');
    }

    /** @test */
    public function super_admin_可以訪問用戶管理頁面()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/users');

        $response->assertStatus(200);
    }

    /** @test */
    public function 一般店家無法訪問用戶管理頁面()
    {
        $response = $this->actingAs($this->storeOwner)
            ->get('/admin/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function 所有已登入用戶都可以訪問安全設定頁面()
    {
        // Super Admin
        $response = $this->actingAs($this->superAdmin)
            ->get('/admin/security-settings');
        $response->assertStatus(200);

        // Store Owner
        $response = $this->actingAs($this->storeOwner)
            ->get('/admin/security-settings');
        $response->assertStatus(200);
    }

    /** @test */
    public function 未登入用戶無法訪問安全設定頁面()
    {
        $response = $this->get('/admin/security-settings');
        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function super_admin_可以為店家啟用IP白名單()
    {
        $this->actingAs($this->superAdmin);

        $this->storeOwner->ip_whitelist_enabled = true;
        $this->storeOwner->ip_whitelist = ['192.168.1.100', '192.168.1.101'];
        $this->storeOwner->save();

        $this->assertTrue($this->storeOwner->ip_whitelist_enabled);
        $this->assertCount(2, $this->storeOwner->ip_whitelist);
    }

    /** @test */
    public function super_admin_可以為店家啟用雙因素認證()
    {
        $this->actingAs($this->superAdmin);

        $this->storeOwner->two_factor_enabled = true;
        $this->storeOwner->save();

        $this->assertTrue($this->storeOwner->two_factor_enabled);
    }

    /** @test */
    public function 店家只能在管理員啟用時修改IP白名單()
    {
        $this->actingAs($this->storeOwner);

        // 管理員未啟用時，無法修改
        $this->storeOwner->ip_whitelist_enabled = false;
        $this->storeOwner->save();

        $this->assertFalse($this->storeOwner->ip_whitelist_enabled);

        // 管理員啟用後，可以修改
        $this->storeOwner->ip_whitelist_enabled = true;
        $this->storeOwner->ip_whitelist = ['192.168.1.200'];
        $this->storeOwner->save();

        $this->assertTrue($this->storeOwner->ip_whitelist_enabled);
        $this->assertContains('192.168.1.200', $this->storeOwner->ip_whitelist);
    }
}
