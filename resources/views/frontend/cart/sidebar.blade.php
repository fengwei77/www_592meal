@if(!empty($cartItems))
    <!-- 使用 flex 佈局分割空間 -->
    <div class="flex flex-col h-full">
        <!-- 購物車商品列表 (可滾動區域) -->
        <div class="flex-1 overflow-y-auto cart-items-container space-y-3 pr-2">
            @foreach($cartItems as $item)
                <div class="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start space-x-3">
                        <!-- 商品圖片 -->
                        <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                            @if($item['image_url'])
                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-utensils text-gray-400 text-xl"></i>
                                </div>
                            @endif
                        </div>

                        <!-- 商品資訊 -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-1">
                                <h4 class="text-sm font-semibold text-gray-900 line-clamp-2 flex-1">{{ $item['name'] }}</h4>
                                <!-- 刪除按鈕 -->
                                <button onclick="removeFromCart({{ $item['id'] }})"
                                        class="ml-2 w-6 h-6 flex items-center justify-center text-red-500 hover:bg-red-50 rounded transition-colors"
                                        title="移除商品">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">
                                單價 ${{ number_format($item['price'], 0) }}
                            </p>

                            <!-- 數量控制 -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center border border-gray-300 rounded-lg">
                                    <button onclick="updateCartQuantity({{ $item['id'] }}, {{ $item['quantity'] - 1 }})"
                                            class="w-8 h-8 text-gray-600 hover:bg-gray-100 flex items-center justify-center rounded-l-lg transition-colors">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>

                                    <span class="w-10 text-center text-sm font-semibold text-gray-900">
                                        {{ $item['quantity'] }}
                                    </span>

                                    <button onclick="updateCartQuantity({{ $item['id'] }}, {{ $item['quantity'] + 1 }})"
                                            class="w-8 h-8 text-gray-600 hover:bg-gray-100 flex items-center justify-center rounded-r-lg transition-colors"
                                            @if($item['quantity'] >= 99) disabled @endif>
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>

                                <!-- 小計 -->
                                <div class="text-right">
                                    <p class="text-sm font-bold text-green-600">
                                        ${{ number_format($item['subtotal'], 0) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- 總計和結帳 (固定在底部) -->
        <div class="flex-shrink-0 border-t pt-3 mt-3">
        <!-- 小計明細 -->
        <div class="bg-gray-50 rounded-lg p-3 mb-3">
            <div class="flex justify-between items-center text-sm mb-2">
                <span class="text-gray-600">商品總數</span>
                <span class="font-medium">{{ array_sum(array_column($cartItems, 'quantity')) }} 件</span>
            </div>
            <div class="flex justify-between items-center text-sm mb-2">
                <span class="text-gray-600">小計</span>
                <span class="font-medium">${{ number_format($total, 0) }}</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                <span class="text-base font-semibold text-gray-900">總金額</span>
                <span class="text-lg font-bold text-green-600">
                    ${{ number_format($total, 0) }}
                </span>
            </div>
        </div>

        <!-- 注意事項 -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
            <div class="flex items-start space-x-2">
                <i class="fas fa-info-circle text-blue-600 mt-0.5 text-sm"></i>
                <div class="text-xs text-blue-800">
                    <p class="font-semibold mb-1">訂餐注意事項</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>請確認商品數量與金額</li>
                        <li>送出後將無法修改訂單</li>
                        <li>請於指定時間取餐</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 送出訂單按鈕 -->
        <div class="space-y-2">
            @if(isset($current_store) && $current_store)
                @if(session('line_logged_in'))
                    <!-- 已登入 LINE：直接送出訂單 -->
                    <a href="{{ route('frontend.order.create', $current_store->store_slug_name) }}"
                       class="w-full text-white py-3 px-4 rounded-lg text-center flex items-center justify-center transition-all duration-200 hover:opacity-90 font-semibold shadow-md"
                       style="background-color: #06C755;">
                        <i class="fas fa-check-circle mr-2"></i>送出訂單
                    </a>
                @else
                    <!-- 未登入 LINE：提示登入 -->
                    <a href="{{ route('line.login') }}?return_url={{ urlencode(route('frontend.order.create', $current_store->store_slug_name)) }}"
                       class="w-full text-white py-3 px-4 rounded-lg text-center flex items-center justify-center transition-all duration-200 hover:opacity-90 font-semibold shadow-md"
                       style="background-color: #06C755;">
                        <!-- LINE Icon SVG -->
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        使用 LINE 登入
                    </a>
                @endif
            @else
                <button disabled
                        class="w-full bg-gray-400 text-white py-3 px-4 rounded-lg text-center block cursor-not-allowed font-semibold"
                        title="請先選擇店家">
                    <i class="fas fa-check-circle mr-2"></i>送出訂單（請先選擇店家）
                </button>
            @endif
            <button onclick="clearCartWithConfirm()"
                    class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-center block hover:bg-gray-200 transition-colors text-sm">
                <i class="fas fa-trash-alt mr-1"></i>清空購物車
            </button>
        </div>
        </div>
    </div>
@else
    <!-- 購物車為空 -->
    <div class="text-center py-12">
        <i class="fas fa-shopping-cart text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-gray-900 font-medium mb-2">購物車是空的</h3>
        <p class="text-gray-500 text-sm mb-4">還沒有添加任何商品</p>
        <a href="{{ isset($current_store) ? route('frontend.store.detail', $current_store->store_slug_name) : route('frontend.stores.index') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
            <i class="fas fa-shopping-bag mr-2"></i>開始購物
        </a>
    </div>
@endif