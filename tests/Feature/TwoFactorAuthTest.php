<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 雙因素認證 (2FA) 功能測試
 */
class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Google2FA $google2fa;

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

        $this->google2fa = new Google2FA();
    }

    /** @test */
    public function 用戶可以生成雙因素認證密鑰()
    {
        $secret = $this->google2fa->generateSecretKey();

        $this->user->two_factor_secret = encrypt($secret);
        $this->user->save();

        $this->assertNotNull($this->user->two_factor_secret);
        $this->assertEquals($secret, decrypt($this->user->two_factor_secret));
    }

    /** @test */
    public function 用戶可以檢查是否已啟用雙因素認證()
    {
        $this->user->two_factor_enabled = false;
        $this->user->save();

        $this->assertFalse($this->user->hasTwoFactorEnabled());

        $this->user->two_factor_enabled = true;
        $this->user->save();

        $this->assertTrue($this->user->hasTwoFactorEnabled());
    }

    /** @test */
    public function 用戶可以確認雙因素認證設定()
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_enabled = true;
        $this->user->two_factor_secret = encrypt($secret);
        $this->user->save();

        $this->assertNull($this->user->two_factor_confirmed_at);

        $this->user->confirmTwoFactor();

        $this->assertNotNull($this->user->fresh()->two_factor_confirmed_at);
    }

    /** @test */
    public function 用戶可以停用雙因素認證()
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_enabled = true;
        $this->user->two_factor_secret = encrypt($secret);
        $this->user->two_factor_recovery_codes = encrypt(json_encode(['code1', 'code2']));
        $this->user->confirmTwoFactor();
        $this->user->save();

        $this->assertTrue($this->user->hasTwoFactorEnabled());
        $this->assertNotNull($this->user->two_factor_secret);
        $this->assertNotNull($this->user->two_factor_confirmed_at);

        $this->user->disableTwoFactor();

        $this->assertFalse($this->user->fresh()->two_factor_enabled);
        $this->assertNull($this->user->fresh()->two_factor_secret);
        $this->assertNull($this->user->fresh()->two_factor_recovery_codes);
        $this->assertNull($this->user->fresh()->two_factor_confirmed_at);
    }

    /** @test */
    public function 可以驗證正確的雙因素認證驗證碼()
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_enabled = true;
        $this->user->two_factor_secret = encrypt($secret);
        $this->user->save();

        // 生成當前的驗證碼
        $validCode = $this->google2fa->getCurrentOtp($secret);

        // 驗證碼應該是有效的
        $isValid = $this->google2fa->verifyKey($secret, $validCode);
        $this->assertTrue($isValid);
    }

    /** @test */
    public function 無效的雙因素認證驗證碼會被拒絕()
    {
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_enabled = true;
        $this->user->two_factor_secret = encrypt($secret);
        $this->user->save();

        // 使用無效的驗證碼
        $invalidCode = '000000';

        $isValid = $this->google2fa->verifyKey($secret, $invalidCode);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function 可以生成雙因素認證恢復碼()
    {
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = bin2hex(random_bytes(4));
        }

        $this->user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $this->user->save();

        $savedCodes = json_decode(decrypt($this->user->two_factor_recovery_codes), true);
        $this->assertCount(8, $savedCodes);
        $this->assertEquals($recoveryCodes, $savedCodes);
    }

    /** @test */
    public function 只有當管理員啟用時才能設定雙因素認證()
    {
        // 管理員未啟用
        $this->user->two_factor_enabled = false;
        $this->user->save();

        $this->assertFalse($this->user->hasTwoFactorEnabled());

        // 管理員啟用後
        $this->user->two_factor_enabled = true;
        $this->user->save();

        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_secret = encrypt($secret);
        $this->user->save();

        $this->assertTrue($this->user->hasTwoFactorEnabled());
        $this->assertNotNull($this->user->two_factor_secret);
    }

    /** @test */
    public function 雙因素認證狀態可以正確顯示()
    {
        // 未啟用
        $this->user->two_factor_enabled = false;
        $this->assertFalse($this->user->hasTwoFactorEnabled());

        // 已啟用但未設定
        $this->user->two_factor_enabled = true;
        $this->user->save();
        $this->assertTrue($this->user->hasTwoFactorEnabled());
        $this->assertNull($this->user->two_factor_secret);

        // 已設定但未確認
        $secret = $this->google2fa->generateSecretKey();
        $this->user->two_factor_secret = encrypt($secret);
        $this->user->save();
        $this->assertNotNull($this->user->two_factor_secret);
        $this->assertNull($this->user->two_factor_confirmed_at);

        // 已確認
        $this->user->confirmTwoFactor();
        $this->assertNotNull($this->user->fresh()->two_factor_confirmed_at);
    }
}
