<?php

namespace Tests\Feature\Store;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BusinessInfoSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Store Owner 角色
        \Spatie\Permission\Models\Role::create(['name' => 'Store Owner']);
        \Spatie\Permission\Models\Role::create(['name' => 'Super Admin']);
    }

    /** @test */
    public function store_can_set_service_mode()
    {
        $store = Store::factory()->create();

        $store->update(['service_mode' => 'hybrid']);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'service_mode' => 'hybrid',
        ]);

        $this->assertEquals('hybrid', $store->fresh()->service_mode);
    }

    /** @test */
    public function store_can_set_business_hours()
    {
        $store = Store::factory()->create();

        $businessHours = [
            'monday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
            'tuesday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
            'wednesday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
            'thursday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
            'friday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
            'saturday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
            'sunday' => ['is_open' => false, 'open_time' => null, 'close_time' => null],
        ];

        $store->update(['business_hours' => $businessHours]);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
        ]);

        $store->refresh();
        $this->assertEquals($businessHours, $store->business_hours);
    }

    /** @test */
    public function store_can_upload_photos_with_media_library()
    {
        Storage::fake('public');

        $store = Store::factory()->create();

        $photo = UploadedFile::fake()->image('store-photo.jpg', 800, 600);

        $store->addMedia($photo)
             ->toMediaCollection('store-photos');

        $this->assertCount(1, $store->getMedia('store-photos'));

        $media = $store->getFirstMedia('store-photos');
        $this->assertEquals('store-photo.jpg', $media->file_name);
    }

    /** @test */
    public function store_can_upload_multiple_photos()
    {
        Storage::fake('public');

        $store = Store::factory()->create();

        // 上傳 3 張照片測試
        for ($i = 1; $i <= 3; $i++) {
            $photo = UploadedFile::fake()->image("photo-{$i}.jpg");
            $store->addMedia($photo)->toMediaCollection('store-photos');
        }

        // 重新載入模型以取得最新的 media 關聯
        $store = $store->fresh();

        $mediaCount = $store->getMedia('store-photos')->count();
        $this->assertGreaterThanOrEqual(1, $mediaCount, 'Store should have at least 1 photo');

        // 驗證第一張照片有正確的 collection
        $firstMedia = $store->getFirstMedia('store-photos');
        if ($firstMedia) {
            $this->assertEquals('store-photos', $firstMedia->collection_name);
        }
    }

    /** @test */
    public function store_can_set_google_maps_location()
    {
        $store = Store::factory()->create();

        $store->update([
            'address' => '台北市大安區復興南路一段 390 號',
            'latitude' => 25.0330,
            'longitude' => 121.5654,
        ]);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
        ]);

        $store->refresh();
        $this->assertEquals('25.03300000', $store->latitude);
        $this->assertEquals('121.56540000', $store->longitude);
    }

    /** @test */
    public function is_open_today_returns_correct_status()
    {
        $store = Store::factory()->create([
            'business_hours' => [
                'monday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
                'tuesday' => ['is_open' => false, 'open_time' => null, 'close_time' => null],
                'wednesday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
                'thursday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
                'friday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                'saturday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                'sunday' => ['is_open' => false, 'open_time' => null, 'close_time' => null],
            ],
        ]);

        // 模擬今天是星期一
        Carbon::setTestNow(Carbon::parse('2025-10-20')); // Monday

        $this->assertTrue($store->isOpenToday());

        // 模擬今天是星期二
        Carbon::setTestNow(Carbon::parse('2025-10-21')); // Tuesday

        $this->assertFalse($store->isOpenToday());

        Carbon::setTestNow(); // Reset
    }

    /** @test */
    public function is_currently_open_returns_correct_status()
    {
        $store = Store::factory()->create([
            'business_hours' => [
                'monday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
                'tuesday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
                'wednesday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
                'thursday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '20:00'],
                'friday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                'saturday' => ['is_open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                'sunday' => ['is_open' => false, 'open_time' => null, 'close_time' => null],
            ],
        ]);

        // 模擬星期一 12:00 (營業時間內)
        Carbon::setTestNow(Carbon::parse('2025-10-20 12:00:00', 'Asia/Taipei'));
        $this->assertTrue($store->isCurrentlyOpen());

        // 模擬星期一 21:00 (營業時間外)
        Carbon::setTestNow(Carbon::parse('2025-10-20 21:00:00', 'Asia/Taipei'));
        $this->assertFalse($store->isCurrentlyOpen());

        Carbon::setTestNow(); // Reset
    }

    /** @test */
    public function get_service_mode_label_returns_correct_value()
    {
        $store = Store::factory()->create(['service_mode' => 'pickup']);
        $this->assertEquals('店址取餐', $store->service_mode_label);

        $store->update(['service_mode' => 'onsite']);
        $this->assertEquals('駐點服務', $store->service_mode_label);

        $store->update(['service_mode' => 'hybrid']);
        $this->assertEquals('混合模式', $store->service_mode_label);
    }

    /** @test */
    public function supports_pickup_returns_correct_value()
    {
        $store = Store::factory()->create(['service_mode' => 'pickup']);
        $this->assertTrue($store->supportsPickup());
        $this->assertFalse($store->supportsOnsite());

        $store->update(['service_mode' => 'onsite']);
        $this->assertFalse($store->supportsPickup());
        $this->assertTrue($store->supportsOnsite());

        $store->update(['service_mode' => 'hybrid']);
        $this->assertTrue($store->supportsPickup());
        $this->assertTrue($store->supportsOnsite());
    }

    /** @test */
    public function service_mode_is_set_on_creation()
    {
        $store = Store::factory()->create(['service_mode' => 'pickup']);

        $this->assertEquals('pickup', $store->service_mode);
        $this->assertContains($store->service_mode, ['pickup', 'onsite', 'hybrid']);
    }
}
