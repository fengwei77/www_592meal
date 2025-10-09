<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckIpWhitelist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IP 白名單功能測試
 */
class IpWhitelistTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立角色
        Role::create(['name' => 'store_owner']);

        // 建立測試用戶
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
        ]);
        $this->user->assignRole('store_owner');
    }

    /** @test */
    public function 用戶可以檢查IP是否在白名單中()
    {
        $this->user->ip_whitelist_enabled = true;
        $this->user->ip_whitelist = ['192.168.1.100', '192.168.1.101'];
        $this->user->save();

        $this->assertTrue($this->user->isIpAllowed('192.168.1.100'));
        $this->assertTrue($this->user->isIpAllowed('192.168.1.101'));
        $this->assertFalse($this->user->isIpAllowed('192.168.1.102'));
    }

    /** @test */
    public function 當IP白名單未啟用時_所有IP都允許()
    {
        $this->user->ip_whitelist_enabled = false;
        $this->user->ip_whitelist = ['192.168.1.100'];
        $this->user->save();

        $this->assertTrue($this->user->isIpAllowed('192.168.1.100'));
        $this->assertTrue($this->user->isIpAllowed('192.168.1.999'));
        $this->assertTrue($this->user->isIpAllowed('10.0.0.1'));
    }

    /** @test */
    public function 當白名單為空時_所有IP都不允許()
    {
        $this->user->ip_whitelist_enabled = true;
        $this->user->ip_whitelist = [];
        $this->user->save();

        $this->assertFalse($this->user->isIpAllowed('192.168.1.100'));
        $this->assertFalse($this->user->isIpAllowed('10.0.0.1'));
    }

    /** @test */
    public function 用戶可以添加IP到白名單()
    {
        $this->user->ip_whitelist_enabled = true;
        $this->user->ip_whitelist = ['192.168.1.100'];
        $this->user->save();

        $this->user->addIpToWhitelist('192.168.1.101');

        $this->assertCount(2, $this->user->ip_whitelist);
        $this->assertContains('192.168.1.101', $this->user->ip_whitelist);
    }

    /** @test */
    public function 添加重複的IP不會重複加入()
    {
        $this->user->ip_whitelist_enabled = true;
        $this->user->ip_whitelist = ['192.168.1.100'];
        $this->user->save();

        $this->user->addIpToWhitelist('192.168.1.100');

        $this->assertCount(1, $this->user->ip_whitelist);
    }

    /** @test */
    public function 用戶可以從白名單移除IP()
    {
        $this->user->ip_whitelist_enabled = true;
        $this->user->ip_whitelist = ['192.168.1.100', '192.168.1.101'];
        $this->user->save();

        $this->user->removeIpFromWhitelist('192.168.1.100');

        $this->assertCount(1, $this->user->ip_whitelist);
        $this->assertNotContains('192.168.1.100', $this->user->ip_whitelist);
        $this->assertContains('192.168.1.101', $this->user->ip_whitelist);
    }

    /** @test */
    public function IP白名單中介層會阻擋未授權的IP()
    {
        $this->user->ip_whitelist_enabled = true;
        $this->user->ip_whitelist = ['192.168.1.100'];
        $this->user->save();

        Auth::login($this->user);

        $request = Request::create('/admin', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.999');

        $middleware = new CheckIpWhitelist();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('您的 IP 位址不在允許的白名單內');

        $middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function IP白名單中介層允許白名單內的IP()
    {
        $this->user->ip_whitelist_enabled = true;
        $this->user->ip_whitelist = ['192.168.1.100'];
        $this->user->save();

        Auth::login($this->user);

        $request = Request::create('/admin', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $middleware = new CheckIpWhitelist();

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function 未啟用IP白名單時_中介層不會阻擋任何IP()
    {
        $this->user->ip_whitelist_enabled = false;
        $this->user->ip_whitelist = ['192.168.1.100'];
        $this->user->save();

        Auth::login($this->user);

        $request = Request::create('/admin', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.999');

        $middleware = new CheckIpWhitelist();

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }
}
