<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 創建測試用戶
    $this->owner = User::factory()->create();
    $this->owner->assignRole('Store Owner');
});

test('store can set weekly business hours', function () {
    $store = Store::factory()->create([
        'user_id' => $this->owner->id,
        'business_hours' => [
            'monday' => [
                'is_open' => true,
                'open_time' => '09:00',
                'close_time' => '22:00',
            ],
            'tuesday' => [
                'is_open' => true,
                'open_time' => '09:00',
                'close_time' => '22:00',
            ],
            'sunday' => [
                'is_open' => false,
            ],
        ],
    ]);

    expect($store->business_hours)->toBeArray()
        ->and($store->business_hours['monday']['is_open'])->toBeTrue()
        ->and($store->business_hours['monday']['open_time'])->toBe('09:00')
        ->and($store->business_hours['sunday']['is_open'])->toBeFalse();
});

test('store can check if it is open on a specific date', function () {
    $store = Store::factory()->create([
        'user_id' => $this->owner->id,
        'business_hours' => [
            'monday' => [
                'is_open' => true,
                'open_time' => '09:00',
                'close_time' => '22:00',
            ],
            'sunday' => [
                'is_open' => false,
            ],
        ],
    ]);

    // 測試週一 (假設今天是週一)
    $monday = now()->next('Monday');
    expect($store->isOpenOnDate($monday))->toBeTrue();

    // 測試週日
    $sunday = now()->next('Sunday');
    expect($store->isOpenOnDate($sunday))->toBeFalse();
});

test('store can set special hours for holidays', function () {
    $store = Store::factory()->create([
        'user_id' => $this->owner->id,
        'special_hours' => [
            [
                'date' => '2025-01-01',
                'name' => '元旦',
                'is_open' => false,
            ],
            [
                'date' => '2025-02-10',
                'name' => '春節',
                'is_open' => true,
                'open_time' => '11:00',
                'close_time' => '18:00',
            ],
        ],
    ]);

    expect($store->special_hours)->toBeArray()
        ->and($store->special_hours)->toHaveCount(2)
        ->and($store->special_hours[0]['name'])->toBe('元旦')
        ->and($store->special_hours[0]['is_open'])->toBeFalse()
        ->and($store->special_hours[1]['name'])->toBe('春節')
        ->and($store->special_hours[1]['open_time'])->toBe('11:00');
});

test('special hours override regular business hours', function () {
    $store = Store::factory()->create([
        'user_id' => $this->owner->id,
        'business_hours' => [
            'monday' => [
                'is_open' => true,
                'open_time' => '09:00',
                'close_time' => '22:00',
            ],
        ],
        'special_hours' => [
            [
                'date' => '2025-01-06', // 假設這是週一
                'name' => '特殊假日',
                'is_open' => false,
            ],
        ],
    ]);

    // 正常週一應該營業
    $normalMonday = '2025-01-13';
    $hours = $store->getBusinessHoursForDate($normalMonday);
    expect($hours)->not->toBeNull()
        ->and($hours['is_open'])->toBeTrue();

    // 特殊假日的週一應該公休
    $specialMonday = '2025-01-06';
    $specialHours = $store->getBusinessHoursForDate($specialMonday);
    expect($specialHours)->not->toBeNull()
        ->and($specialHours['is_open'])->toBeFalse()
        ->and($specialHours['name'])->toBe('特殊假日');
});

test('can check if store is open on today', function () {
    $today = now('Asia/Taipei');
    $dayOfWeek = strtolower($today->englishDayOfWeek);

    $store = Store::factory()->create([
        'user_id' => $this->owner->id,
        'business_hours' => [
            $dayOfWeek => [
                'is_open' => true,
                'open_time' => '09:00',
                'close_time' => '22:00',
            ],
        ],
    ]);

    expect($store->isOpenToday())->toBeTrue();
});

test('can retrieve business hours for date', function () {
    $store = Store::factory()->create([
        'user_id' => $this->owner->id,
        'business_hours' => [
            'friday' => [
                'is_open' => true,
                'open_time' => '10:00',
                'close_time' => '23:00',
            ],
        ],
    ]);

    $friday = now()->next('Friday');
    $hours = $store->getBusinessHoursForDate($friday);

    expect($hours)->not->toBeNull()
        ->and($hours['is_open'])->toBeTrue()
        ->and($hours['open_time'])->toBe('10:00')
        ->and($hours['close_time'])->toBe('23:00');
});

test('returns null when no business hours set for date', function () {
    $store = Store::factory()->create([
        'user_id' => $this->owner->id,
        'business_hours' => [
            'monday' => [
                'is_open' => true,
                'open_time' => '09:00',
                'close_time' => '22:00',
            ],
        ],
    ]);

    $tuesday = now()->next('Tuesday');
    $hours = $store->getBusinessHoursForDate($tuesday);

    expect($hours)->toBeNull();
});
