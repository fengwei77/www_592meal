<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\AddressGeocodingService;
use App\Services\StoreGeocodingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class StoreController extends Controller
{
    /**
     * 顯示店家清單頁面
     */
    public function index(Request $request): View
    {
        $view = $request->get('view', 'list');
        $city = $request->get('city');
        $area = $request->get('area');
        $search = $request->get('search');
        $type = $request->get('type');

        // 建立查詢
        $query = Store::where('is_active', true)
                      ->with(['owner'])
                      ->withCount(['orders as orders_count', 'menuItems as menu_items_count']);

        // 篩選條件
        if ($city) {
            $query->where('city', $city);
        }

        if ($area) {
            $query->where('area', $area);
        }

        if ($type) {
            $query->where('store_type', $type);
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        // 分頁處理
        $stores = $query->orderBy('is_featured', 'desc')
                       ->orderBy('created_at', 'desc')
                       ->paginate(12);

        // 取得篩選選項
        $filters = $this->getFilterOptions();

        // 取得統計資料
        $stats = $this->getStoreStats();

        return view('frontend.stores.index', compact(
            'stores',
            'filters',
            'stats',
            'view',
            'city',
            'area',
            'search',
            'type'
        ));
    }

    /**
     * API: 取得店家列表
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $city = $request->get('city');
        $area = $request->get('area');
        $search = $request->get('search');
        $type = $request->get('type');
        $page = $request->get('page', 1);
        $nearby = $request->get('nearby');
        $userLat = $request->get('lat'); // 使用者緯度
        $userLng = $request->get('lng'); // 使用者經度

        $cacheKey = "stores:list:" . md5(serialize([
            'city' => $city,
            'area' => $area,
            'search' => $search,
            'type' => $type,
            'nearby' => $nearby,
            'user_lat' => $userLat,
            'user_lng' => $userLng,
            'page' => $page
        ]));

        $data = Cache::remember($cacheKey, 300, function () use ($city, $area, $search, $type, $nearby, $userLat, $userLng) {
            $query = Store::where('is_active', true)
                          ->with(['owner'])
                          ->withCount(['orders as orders_count', 'menuItems as menu_items_count']);

            // 篩選條件
            if ($city) {
                $query->where('city', $city);
            }

            if ($area) {
                $query->where('area', $area);
            }

            if ($type) {
                $query->where('store_type', $type);
            }

            if ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('address', 'LIKE', "%{$search}%");
                });
            }

            // 附近店家篩選
            if ($nearby === 'true' && $userLat && $userLng) {
                // 使用 Haversine 公式計算距離並篩選附近店家
                $query->selectRaw('*,
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                    sin(radians(latitude)))) AS distance_km',
                    [$userLat, $userLng, $userLat]
                )
                ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                    sin(radians(latitude)))) <= 20', // 限制在 20 公里內
                    [$userLat, $userLng, $userLat]
                )
                ->orderBy('distance_km', 'asc');
            } else {
                $query->orderBy('is_featured', 'desc')
                       ->orderBy('created_at', 'desc');
            }

            $stores = $query->paginate(12);

            return [
                'stores' => $stores->map(function ($store) use ($nearby, $userLat, $userLng) {
                    $storeData = [
                        'id' => $store->id,
                        'name' => $store->name,
                        'store_url' => $store->store_url,
                        'store_slug' => $store->store_slug,
                        'description' => $store->description,
                        'store_type' => $store->store_type,
                        'type_label' => $store->getTypeLabel(),
                        'address' => $store->address,
                        'city' => $store->city,
                        'area' => $store->area,
                        'phone' => $store->phone,
                        'logo_url' => $store->logo_url,
                        'cover_image_url' => $store->cover_image_url,
                        'latitude' => $store->latitude,
                        'longitude' => $store->longitude,
                        'is_open' => $store->isCurrentlyOpen(),
                        'open_hours_text' => $store->getOpenHoursText(),
                        'service_mode' => $store->service_mode,
                        'is_featured' => $store->is_featured,
                        'orders_count' => $store->orders_count,
                        'menu_items_count' => $store->menu_items_count,
                        'rating' => $store->getAverageRating(),
                        'created_at' => $store->created_at->format('Y-m-d'),
                    ];

                    // 如果有附近篩選且有距離資訊，則添加距離資訊
                    if ($nearby === 'true' && isset($store->distance_km)) {
                        if ($store->distance_km < 1) {
                            $storeData['distance'] = round($store->distance_km * 1000) . ' 公尺';
                        } else {
                            $storeData['distance'] = round($store->distance_km, 1) . ' 公里';
                        }
                    }

                    return $storeData;
                }),
                'pagination' => [
                    'current_page' => $stores->currentPage(),
                    'last_page' => $stores->lastPage(),
                    'per_page' => $stores->perPage(),
                    'total' => $stores->total(),
                    'from' => $stores->firstItem(),
                    'to' => $stores->lastItem(),
                ]
            ];
        });

        return response()->json($data);
    }

    /**
     * API: 取得篩選選項
     */
    public function getFilters(): JsonResponse
    {
        $cacheKey = 'stores:filters';

        $filters = Cache::remember($cacheKey, 3600, function () {
            return [
                'cities' => Store::where('is_active', true)
                                 ->whereNotNull('city')
                                 ->distinct()
                                 ->pluck('city')
                                 ->sort()
                                 ->values(),

                'areas' => Store::where('is_active', true)
                                ->whereNotNull('area')
                                ->distinct()
                                ->pluck('area')
                                ->sort()
                                ->values(),

                'types' => collect([
                    ['value' => 'restaurant', 'label' => '餐廳'],
                    ['value' => 'cafe', 'label' => '咖啡廳'],
                    ['value' => 'snack', 'label' => '小吃店'],
                    ['value' => 'bar', 'label' => '酒吧'],
                    ['value' => 'bakery', 'label' => '烘焙坊'],
                    ['value' => 'other', 'label' => '其他'],
                ]),
            ];
        });

        return response()->json($filters);
    }

    /**
     * API: 店家搜尋建議
     */
    public function searchSuggestions(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (!$query || strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = Store::where('is_active', true)
                           ->where(function (Builder $q) use ($query) {
                               $q->where('name', 'LIKE', "%{$query}%")
                                 ->orWhere('description', 'LIKE', "%{$query}%");
                           })
                           ->limit(5)
                           ->get(['id', 'name', 'city', 'area', 'store_type'])
                           ->map(function ($store) {
                               return [
                                   'id' => $store->id,
                                   'name' => $store->name,
                                   'location' => "{$store->city} {$store->area}",
                                   'type' => $store->store_type,
                                   'type_label' => $store->getTypeLabel(),
                               ];
                           });

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * API: 取得店家摘要資訊
     */
    public function show(Store $store): JsonResponse
    {
        if (!$store->is_active) {
            return response()->json(['error' => '店家不存在或已停用'], 404);
        }

        $store->load(['owner', 'menuItems' => function ($query) {
            $query->where('is_active', true)->take(6);
        }]);

        $data = [
            'id' => $store->id,
            'name' => $store->name,
            'subdomain' => $store->subdomain,
            'description' => $store->description,
            'store_type' => $store->store_type,
            'type_label' => $store->getTypeLabel(),
            'address' => $store->address,
            'city' => $store->city,
            'area' => $store->area,
            'phone' => $store->phone,
            'logo_url' => $store->logo_url,
            'cover_image_url' => $store->cover_image_url,
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
            'is_open' => $store->isCurrentlyOpen(),
            'open_hours_text' => $store->getOpenHoursText(),
            'business_hours' => $store->business_hours,
            'service_mode' => $store->service_mode,
            'service_mode_label' => $store->getServiceModeLabel(),
            'social_links' => $store->social_links,
            'is_featured' => $store->is_featured,
            'menu_items' => $store->menuItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'is_featured' => $item->is_featured,
                    'category_name' => $item->menuCategory?->name,
                ];
            }),
            'stats' => [
                'orders_count' => $store->orders()->count(),
                'menu_items_count' => $store->menuItems()->where('is_active', true)->count(),
                'rating' => $store->getAverageRating(),
                'created_at' => $store->created_at->format('Y-m-d'),
            ]
        ];

        return response()->json($data);
    }

    /**
     * API: 取得地圖模式的店家資料
     */
    public function mapStores(Request $request): JsonResponse
    {
        $bounds = $request->get('bounds'); // 地圖邊界座標
        $city = $request->get('city');
        $area = $request->get('area');
        $userLat = $request->get('user_lat'); // 使用者緯度
        $userLng = $request->get('user_lng'); // 使用者經度
        $includeAddressOnly = $request->get('include_address_only', 'false'); // 是否包含僅有地址的店家

        $query = Store::where('is_active', true);

        // 根據參數決定是否包含僅有地址的店家
        if ($includeAddressOnly === 'true') {
            // 包含有坐標的店家，以及有地址但沒有坐標的店家
            $query->where(function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->whereNotNull('latitude')
                          ->whereNotNull('longitude');
                })->orWhere(function ($subQuery) {
                    $subQuery->where(function ($innerQuery) {
                        $innerQuery->whereNotNull('address')
                              ->orWhereNotNull('city')
                              ->orWhereNotNull('area');
                    })->where(function ($innerQuery) {
                        $innerQuery->whereNull('latitude')
                              ->orWhereNull('longitude');
                    });
                });
            });
        } else {
            // 僅包含有坐標的店家（原始行為）
            $query->whereNotNull('latitude')
                  ->whereNotNull('longitude');
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($area) {
            $query->where('area', $area);
        }

        // 如果有地圖邊界，則篩選邊界內的店家
        if ($bounds && isset($bounds['north'], $bounds['south'], $bounds['east'], $bounds['west'])) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                  ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        // 如果有使用者位置，則計算距離並按距離排序
        if ($userLat && $userLng) {
            // 使用 Haversine 公式計算距離
            $query->selectRaw('*,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                sin(radians(latitude)))) AS distance_km',
                [$userLat, $userLng, $userLat]
            )
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                sin(radians(latitude)))) <= 50', // 限制在 50 公里內
                [$userLat, $userLng, $userLat]
            )
            ->orderBy('distance_km', 'asc');
        } else {
            $query->orderBy('is_featured', 'desc')
                   ->orderBy('created_at', 'desc');
        }

        $stores = $query->withCount(['orders', 'menuItems'])
                       ->limit(200) // 地圖模式限制數量
                       ->get();

        // 確保至少有一些店家資料
        if ($stores->isEmpty()) {
            // 如果沒有符合條件的店家，嘗試取得所有店家
            $stores = Store::where('is_active', true)
                           ->whereNotNull('latitude')
                           ->whereNotNull('longitude')
                           ->withCount(['orders', 'menuItems'])
                           ->orderBy('is_featured', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->limit(50)
                           ->get();
        }

        $data = $stores->map(function ($store) use ($userLat, $userLng, $includeAddressOnly) {
            $storeData = [
                'id' => $store->id,
                'name' => $store->name,
                'store_url' => $store->store_url,
                'store_slug' => $store->store_slug,
                'store_type' => $store->store_type,
                'type_label' => $store->getTypeLabel(),
                'address' => $store->address,
                'city' => $store->city,
                'area' => $store->area,
                'full_address' => $store->full_address,
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
                'logo_url' => $store->logo_url,
                'is_open' => $store->isCurrentlyOpen(),
                'open_hours_text' => $store->getOpenHoursText(),
                'service_mode' => $store->service_mode,
                'is_featured' => $store->is_featured,
                'rating' => $store->getAverageRating(),
                'coordinate_info' => $store->getCoordinateInfo(),
                'needs_geocoding' => $store->needsGeocoding(),
                'has_coordinates' => $store->hasCoordinates(),
            ];

            // 如果有距離資訊，則格式化距離顯示
            if (isset($store->distance_km)) {
                if ($store->distance_km < 1) {
                    $storeData['distance'] = round($store->distance_km * 1000) . ' 公尺';
                } else {
                    $storeData['distance'] = round($store->distance_km, 1) . ' 公里';
                }
            }

            // 如果包含地址僅有的店家，添加地址定位提示
            if ($includeAddressOnly === 'true' && !$store->hasCoordinates() && !empty($store->getFullAddress())) {
                $storeData['location_status'] = 'address_only';
                $storeData['location_message'] = '店家有地址資訊，但尚未標定確切坐標';
                $storeData['can_be_geocoded'] = true;
            } elseif ($store->hasCoordinates()) {
                $storeData['location_status'] = 'has_coordinates';
                $storeData['location_message'] = '店家坐標已標定';
                $storeData['can_be_geocoded'] = false;
            } else {
                $storeData['location_status'] = 'no_location';
                $storeData['location_message'] = '店家缺少地址和坐標資訊';
                $storeData['can_be_geocoded'] = false;
            }

            return $storeData;
        });

        return response()->json(['stores' => $data]);
    }

    /**
     * 店家菜單頁面 (使用 slug)
     */
    public function storeDetail(string $store_slug)
    {
        $store = Store::where('store_slug_name', $store_slug)
                     ->where('is_active', true)
                     ->firstOrFail();

        // 設置店家到請求中
        request()->merge(['current_store' => $store]);
        view()->share('current_store', $store);

        // 獲取所有啟用的菜單分類，按排序順序
        $categories = $store->menuCategories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['menuItems' => function($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order');
            }])
            ->get();

        // 獲取所有菜單項目（不依賴分類）
        $allItems = $store->menuItems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // 直接顯示店家菜單頁面
        return view('frontend.menu.index', compact('store', 'categories', 'allItems'));
    }

    /**
     * 取得篩選選項 (內部使用)
     */
    private function getFilterOptions(): array
    {
        $cacheKey = 'stores:filters:frontend';

        return Cache::remember($cacheKey, 3600, function () {
            // 台灣縣市區域資料
            $taiwanCities = [
                '台北市' => [
                    '中正區', '大同區', '中山區', '松山區', '大安區', '萬華區', '信義區', '士林區', '北投區', '內湖區', '南港區', '文山區'
                ],
                '新北市' => [
                    '板橋區', '三重區', '中和區', '永和區', '新莊區', '新店區', '土城區', '蘆洲區', '樹林區', '鶯歌區', '三峽區',
                    '淡水區', '汐止區', '瑞芳區', '五股區', '泰山區', '林口區', '深坑區', '石碇區', '坪林區', '三芝區', '石門區',
                    '八里區', '平溪區', '雙溪區', '貢寮區', '金山区', '萬里區', '烏來區'
                ],
                '桃園市' => [
                    '桃園區', '中壢區', '平鎮區', '八德區', '楊梅區', '蘆竹區', '大溪區', '龍潭區', '龜山區', '大園區',
                    '觀音區', '新屋區', '楊梅區', '蘆竹區', '大園區', '龜山區', '平鎮區', '龍潭區', '新屋區', '觀音區'
                ],
                '台中市' => [
                    '中西區', '東區', '南區', '西區', '北區', '北屯區', '西屯區', '南屯區', '太平區', '大里區', '霧峰區',
                    '烏日區', '豐原區', '后里區', '石岡區', '東勢區', '和平區', '新社區', '潭子區', '大雅區', '神岡區',
                    '大肚區', '沙鹿區', '龍井區', '梧棲區', '清水區', '大甲區', '外埔區', '大安區'
                ],
                '台南市' => [
                    '中西區', '東區', '南區', '北區', '安平區', '安南區', '永康區', '歸仁區', '新化區', '左鎮區',
                    '玉井區', '楠西區', '南化區', '仁德區', '關廟區', '龍崎區', '官田區', '麻豆區', '佳里區', '西港區',
                    '七股區', '將軍區', '學甲區', '北門區', '新營區', '後壁區', '白河區', '東山區', '六甲區', '下營區',
                    '柳營區', '鹽水區', '善化區', '大內區', '山上區', '新市區', '安定區'
                ],
                '高雄市' => [
                    '楠梓區', '左營區', '鼓山區', '三民區', '鹽埕區', '前金區', '新興區', '苓雅區', '前鎮區', '旗津區',
                    '小港區', '鳳山區', '林園區', '大寮區', '大樹區', '大社區', '仁武區', '鳥松區', '岡山區', '橋頭區',
                    '燕巢區', '田寮區', '阿蓮區', '路竹區', '湖內區', '茄萣區', '永安區', '彌陀區', '梓官區', '旗山區',
                    '美濃區', '六龜區', '甲仙區', '杉林區', '內門區', '茂林區', '桃源區', '那瑪夏區'
                ],
                '基隆市' => [
                    '仁愛區', '信義區', '中正區', '中山區', '安樂區', '七堵區', '暖暖區', '中山区'
                ],
                '新竹市' => [
                    '東區', '北區', '香山區'
                ],
                '新竹縣' => [
                    '竹北市', '竹東鎮', '新埔鎮', '關西鎮', '湖口鄉', '新豐鄉', '芎林鄉', '橫山鄉', '北埔鄉', '寶山鄉',
                    '峨眉鄉', '五峰鄉', '尖石鄉'
                ],
                '嘉義市' => [
                    '東區', '西區'
                ],
                '嘉義縣' => [
                    '太保市', '朴子市', '布袋鎮', '大林鎮', '民雄鄉', '溪口鄉', '新港鄉', '六腳鄉', '東石鄉', '義竹鄉',
                    '鹿草鄉', '水上鄉', '中埔鄉', '竹崎鄉', '梅山鄉', '番路鄉', '大埔鄉', '阿里山鄉'
                ],
                '宜蘭縣' => [
                    '宜蘭市', '羅東鎮', '蘇澳鎮', '頭城鎮', '礁溪鄉', '壯圍鄉', '員山鄉', '冬山鄉', '五結鄉', '三星鄉',
                    '大同鄉', '南澳鄉'
                ],
                '花蓮縣' => [
                    '花蓮市', '吉安鄉', '壽豐鄉', '秀林鄉', '玉里鎮', '新城鄉', '吉安鄉', '光復鄉', '豐濱鄉', '瑞穗鄉',
                    '萬榮鄉', '鳳林鎮', '富里鄉', '卓溪鄉'
                ],
                '台東縣' => [
                    '台東市', '成功鎮', '關山鎮', '卑南鄉', '鹿野鄉', '池上鄉', '東河鄉', '長濱鄉', '太麻里鄉', '大武鄉',
                    '綠島鄉', '海端鄉', '延平鄉', '金峰鄉', '達仁鄉', '蘭嶼鄉'
                ],
                '澎湖縣' => [
                    '馬公市', '湖西鄉', '白沙鄉', '西嶼鄉', '望安鄉', '七美鄉'
                ],
                '金門縣' => [
                    '金城鎮', '金沙鎮', '金湖鎮', '金寧鄉', '烈嶼鄉', '烏坵鄉'
                ],
                '連江縣' => [
                    '南竿鄉', '北竿鄉', '莒光鄉', '東引鄉'
                ]
            ];

            // 取得資料庫中實際有店家的縣市區域
            $existingCities = Store::where('is_active', true)
                                  ->whereNotNull('city')
                                  ->distinct()
                                  ->orderBy('city')
                                  ->pluck('city')
                                  ->toArray();

            $existingAreas = Store::where('is_active', true)
                                 ->whereNotNull('area')
                                 ->distinct()
                                 ->orderBy('area')
                                 ->pluck('area')
                                 ->toArray();

            // 合併資料：如果資料庫中有資料就用資料庫的，否則用完整台灣資料
            $allCities = collect(array_keys($taiwanCities));

            // 如果資料庫有縣市資料，優先使用資料庫的縣市
            if (!empty($existingCities)) {
                $cities = collect($existingCities);
            } else {
                $cities = $allCities;
            }

            // 合併區域資料：包含資料庫中現有的和台灣所有的區域
            $allAreas = collect();
            foreach ($taiwanCities as $city => $areas) {
                $allAreas = $allAreas->merge($areas);
            }

            $areas = $allAreas->merge($existingAreas)->unique()->sort()->values();

            return [
                'cities' => $cities,
                'areas' => $areas,
                'taiwan_cities_areas' => $taiwanCities, // 用於前端動態載入區域
                'types' => collect([
                    ['value' => 'restaurant', 'label' => '餐廳'],
                    ['value' => 'cafe', 'label' => '咖啡廳'],
                    ['value' => 'snack', 'label' => '小吃店'],
                    ['value' => 'bar', 'label' => '酒吧'],
                    ['value' => 'bakery', 'label' => '烘焙坊'],
                    ['value' => 'other', 'label' => '其他'],
                ]),
            ];
        });
    }

    /**
     * 取得統計資料 (內部使用)
     */
    private function getStoreStats(): array
    {
        return Cache::remember('stores:stats', 600, function () {
            return [
                'total_stores' => Store::where('is_active', true)->count(),
                'featured_stores' => Store::where('is_active', true)->where('is_featured', true)->count(),
                'cities_count' => Store::where('is_active', true)->whereNotNull('city')->distinct('city')->count(),
                'types' => Store::where('is_active', true)
                               ->groupBy('store_type')
                               ->selectRaw('store_type, count(*) as count')
                               ->pluck('count', 'store_type')
                               ->toArray(),
            ];
        });
    }

    /**
     * API: 地址地理編碼 - 將地址轉換為經緯度
     */
    public function geocodeAddress(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|max:255'
        ]);

        $address = $request->input('address');
        $geocodingService = new AddressGeocodingService();

        try {
            // 驗證地址格式
            if (!$geocodingService->validateAddress($address)) {
                return response()->json([
                    'success' => false,
                    'error' => '地址格式無效',
                    'message' => '請輸入有效的台灣地址'
                ], 400);
            }

            // 標準化地址
            $normalizedAddress = $geocodingService->normalizeAddress($address);

            // 執行地理編碼
            $result = $geocodingService->geocodeAddress($normalizedAddress);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'error' => 'geocoding_failed',
                    'message' => '無法找到該地址的坐標資訊',
                    'original_address' => $address,
                    'normalized_address' => $normalizedAddress
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude'],
                    'formatted_address' => $result['formatted_address'],
                    'confidence' => $result['confidence'],
                    'source' => $result['source'],
                    'original_address' => $address,
                    'normalized_address' => $normalizedAddress
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('地址地理編碼失敗', [
                'address' => $address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => '伺服器錯誤，請稍後再試'
            ], 500);
        }
    }

    /**
     * API: 批量地址地理編碼
     */
    public function batchGeocodeAddresses(Request $request): JsonResponse
    {
        $request->validate([
            'addresses' => 'required|array|max:10', // 限制最多10個地址
            'addresses.*' => 'required|string|max:255'
        ]);

        $addresses = $request->input('addresses');
        $geocodingService = new AddressGeocodingService();

        try {
            $results = [];

            foreach ($addresses as $index => $address) {
                // 驗證地址格式
                if (!$geocodingService->validateAddress($address)) {
                    $results[$index] = [
                        'success' => false,
                        'error' => 'invalid_address',
                        'message' => '地址格式無效'
                    ];
                    continue;
                }

                // 標準化地址
                $normalizedAddress = $geocodingService->normalizeAddress($address);

                // 執行地理編碼
                $geocodingResult = $geocodingService->geocodeAddress($normalizedAddress);

                if ($geocodingResult) {
                    $results[$index] = [
                        'success' => true,
                        'data' => [
                            'latitude' => $geocodingResult['latitude'],
                            'longitude' => $geocodingResult['longitude'],
                            'formatted_address' => $geocodingResult['formatted_address'],
                            'confidence' => $geocodingResult['confidence'],
                            'source' => $geocodingResult['source']
                        ]
                    ];
                } else {
                    $results[$index] = [
                        'success' => false,
                        'error' => 'geocoding_failed',
                        'message' => '無法找到該地址的坐標資訊'
                    ];
                }

                // 添加延遲避免 API 限制
                usleep(200000); // 0.2 秒
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'total_processed' => count($addresses),
                'successful' => count(array_filter($results, fn($r) => $r['success']))
            ]);

        } catch (\Exception $e) {
            Log::error('批量地址地理編碼失敗', [
                'addresses' => $addresses,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => '伺服器錯誤，請稍後再試'
            ], 500);
        }
    }

    /**
     * API: 更新店家坐標
     */
    public function updateStoreCoordinates(Request $request, Store $store): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'source' => 'nullable|string|max:50'
        ]);

        try {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $source = $request->input('source', 'manual');

            $success = $store->updateCoordinates($latitude, $longitude, $source);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => '店家坐標更新成功',
                    'data' => [
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'latitude' => (float) $store->latitude,
                        'longitude' => (float) $store->longitude,
                        'source' => $source
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'update_failed',
                    'message' => '更新店家坐標失敗'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('更新店家坐標失敗', [
                'store_id' => $store->id,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => '伺服器錯誤，請稍後再試'
            ], 500);
        }
    }

    /**
     * API: 自動更新所有缺少坐標的店家
     */
    public function autoGeocodeStores(): JsonResponse
    {
        try {
            // 獲取需要地理編碼的店家
            $storesNeedingGeocoding = Store::where('is_active', true)
                ->needsGeocoding()
                ->limit(20) // 限制處理數量避免超時
                ->get();

            if ($storesNeedingGeocoding->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => '沒有需要地理編碼的店家',
                    'data' => [
                        'processed' => 0,
                        'updated' => 0,
                        'failed' => 0
                    ]
                ]);
            }

            $geocodingService = new AddressGeocodingService();
            $processed = 0;
            $updated = 0;
            $failed = 0;
            $results = [];

            foreach ($storesNeedingGeocoding as $store) {
                $processed++;
                $address = $store->getGeocodableAddress();

                try {
                    $geocodingResult = $geocodingService->geocodeAddress($address);

                    if ($geocodingResult) {
                        $success = $store->updateCoordinates(
                            $geocodingResult['latitude'],
                            $geocodingResult['longitude'],
                            $geocodingResult['source']
                        );

                        if ($success) {
                            $updated++;
                            $results[] = [
                                'store_id' => $store->id,
                                'store_name' => $store->name,
                                'address' => $address,
                                'coordinates' => [
                                    'latitude' => $geocodingResult['latitude'],
                                    'longitude' => $geocodingResult['longitude']
                                ],
                                'source' => $geocodingResult['source'],
                                'confidence' => $geocodingResult['confidence']
                            ];
                        } else {
                            $failed++;
                        }
                    } else {
                        $failed++;
                        $results[] = [
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                            'address' => $address,
                            'error' => 'geocoding_failed'
                        ];
                    }

                    // 添加延遲避免 API 限制
                    usleep(500000); // 0.5 秒

                } catch (\Exception $e) {
                    $failed++;
                    Log::error('單一店家地理編碼失敗', [
                        'store_id' => $store->id,
                        'address' => $address,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "處理完成：處理 {$processed} 家，更新 {$updated} 家，失敗 {$failed} 家",
                'data' => [
                    'processed' => $processed,
                    'updated' => $updated,
                    'failed' => $failed,
                    'results' => $results
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('自動地理編碼店家失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => '伺服器錯誤，請稍後再試'
            ], 500);
        }
    }

    /**
     * API: 獲取店家坐標統計
     */
    public function getCoordinatesStats(): JsonResponse
    {
        try {
            $totalStores = Store::where('is_active', true)->count();
            $storesWithCoordinates = Store::where('is_active', true)
                ->withCoordinates()
                ->count();
            $storesNeedingGeocoding = Store::where('is_active', true)
                ->needsGeocoding()
                ->count();

            $coveragePercentage = $totalStores > 0
                ? round(($storesWithCoordinates / $totalStores) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_stores' => $totalStores,
                    'stores_with_coordinates' => $storesWithCoordinates,
                    'stores_needing_geocoding' => $storesNeedingGeocoding,
                    'coverage_percentage' => $coveragePercentage,
                    'coverage_status' => match(true) {
                        $coveragePercentage >= 90 => 'excellent',
                        $coveragePercentage >= 70 => 'good',
                        $coveragePercentage >= 50 => 'fair',
                        default => 'poor'
                    }
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('獲取坐標統計失敗', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => '伺服器錯誤，請稍後再試'
            ], 500);
        }
    }

    /**
     * API: 編輯頁面地址定位
     * 專為店家編輯頁面設計的地址定位功能
     */
    public function geocodeStoreForEdit(Store $store, Request $request): JsonResponse
    {
        try {
            // 檢查店家是否存在
            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到指定的店家'
                ], 404);
            }

            // 檢查是否有地址
            if (empty($store->address)) {
                return response()->json([
                    'success' => false,
                    'message' => '店家地址為空，無法進行定位'
                ]);
            }

            // 檢查是否已經有有效的坐標
            if (!empty($store->latitude) && !empty($store->longitude) &&
                $store->latitude != 0 && $store->longitude != 0) {
                return response()->json([
                    'success' => false,
                    'message' => '店家已經有坐標資料，無需重新定位'
                ]);
            }

            // 使用 StoreGeocodingService 進行地址定位
            $geocodingService = app(StoreGeocodingService::class);
            $result = $geocodingService->geocodeStore($store);

            if ($result['success']) {
                Log::info('編輯頁面地址定位成功', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'address' => $store->address,
                    'coordinates' => $result['data']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '地址定位成功',
                    'data' => [
                        'latitude' => $store->fresh()->latitude,
                        'longitude' => $store->fresh()->longitude,
                        'source' => $result['data']['source'] ?? 'google'
                    ]
                ]);
            } else {
                Log::warning('編輯頁面地址定位失敗', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'address' => $store->address,
                    'error' => $result['message']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('編輯頁面地址定位發生錯誤', [
                'store_id' => $store->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '地址定位時發生伺服器錯誤，請稍後再試'
            ], 500);
        }
    }
}