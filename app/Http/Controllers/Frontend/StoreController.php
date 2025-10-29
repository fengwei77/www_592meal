<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
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

        $query = Store::where('is_active', true)
                      ->whereNotNull('latitude')
                      ->whereNotNull('longitude');

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
                       ->limit(100) // 地圖模式限制數量
                       ->get();

        $data = $stores->map(function ($store) use ($userLat, $userLng) {
            $storeData = [
                'id' => $store->id,
                'name' => $store->name,
                'store_url' => $store->store_url,
                'store_slug' => $store->store_slug,
                'store_type' => $store->store_type,
                'type_label' => $store->getTypeLabel(),
                'address' => $store->address,
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
                'logo_url' => $store->logo_url,
                'is_open' => $store->isCurrentlyOpen(),
                'open_hours_text' => $store->getOpenHoursText(),
                'service_mode' => $store->service_mode,
                'is_featured' => $store->is_featured,
                'rating' => $store->getAverageRating(),
            ];

            // 如果有距離資訊，則格式化距離顯示
            if (isset($store->distance_km)) {
                if ($store->distance_km < 1) {
                    $storeData['distance'] = round($store->distance_km * 1000) . ' 公尺';
                } else {
                    $storeData['distance'] = round($store->distance_km, 1) . ' 公里';
                }
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
            return [
                'cities' => Store::where('is_active', true)
                                 ->whereNotNull('city')
                                 ->distinct()
                                 ->orderBy('city')
                                 ->pluck('city'),

                'areas' => Store::where('is_active', true)
                                ->whereNotNull('area')
                                ->distinct()
                                ->orderBy('area')
                                ->pluck('area'),

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
}