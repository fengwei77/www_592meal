@extends('frontend.layouts.app')

@section('title', '結帳 - ' . $store->name)

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- 頁面標題 -->
    <div class="bg-white border-b">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-8 order-container" style="max-width: 1400px;">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-credit-card mr-2"></i>結帳
                </h1>
                <a href="{{ route('frontend.cart.index') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i>返回購物車
                </a>
            </div>
        </div>
    </div>

    <!-- 結帳進度指示器 -->
    <div class="bg-white border-b">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-4" style="max-width: 1400px;">
            <div class="flex items-center justify-center space-x-4 md:space-x-8">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                        1
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-900">確認訂單</span>
                </div>

                <div class="flex items-center">
                    <div class="w-12 h-0.5 bg-gray-300"></div>
                </div>

                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium">
                        2
                    </div>
                    <span class="ml-2 text-sm text-gray-500">填寫資訊</span>
                </div>

                <div class="flex items-center">
                    <div class="w-12 h-0.5 bg-gray-300"></div>
                </div>

                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium">
                        3
                    </div>
                    <span class="ml-2 text-sm text-gray-500">確認下單</span>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <!-- 錯誤訊息 -->
        <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 mt-6" style="max-width: 1400px;">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">請修正以下錯誤：</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-8" style="max-width: 1400px;">
        <form action="{{ route('frontend.order.store', $store->store_slug_name) }}" method="POST" class="grid grid-cols-1 xl:grid-cols-4 gap-8" data-protect="true">
            @csrf
            @if(isset($formTimestamp))
                <input type="hidden" name="timestamp" value="{{ $formTimestamp }}">
            @endif

            <!-- 訂單商品列表區域 (2/4 寬度) -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">訂單商品</h2>
                    </div>

                    <div class="divide-y">
                        @foreach($cartItems as $item)
                            <div class="p-6 bg-white rounded-xl border border-gray-200 hover:shadow-lg transition-all duration-200">
                                <div class="flex items-start gap-4">
                                    <!-- 商品圖片 -->
                                    <div class="flex-shrink-0 w-20 h-20 bg-gray-100 rounded-lg overflow-hidden border border-gray-300">
                                        @if($item['image_url'])
                                            <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}"
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-utensils text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- 商品資訊 -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-xl font-semibold text-gray-900 mb-2 leading-tight">{{ $item['name'] }}</h3>
                                        <p class="text-gray-600 mb-3 text-base">
                                            ${{ number_format($item['price'], 0) }} × {{ $item['quantity'] }}
                                        </p>
                                    </div>

                                    <!-- 小計 -->
                                    <div class="flex-shrink-0 text-right">
                                        <div class="bg-green-50 rounded-lg px-3 py-2 border border-green-200">
                                            <p class="text-xl font-bold text-green-700">
                                                ${{ number_format($item['subtotal'], 0) }}
                                            </p>
                                            <p class="text-xs text-green-600 mt-1">小計</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- 總計 -->
                    <div class="px-8 py-6 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl">
                        <div class="text-center">
                            <span class="text-lg font-semibold text-green-800 block mb-1">訂單總計</span>
                            <div class="text-4xl font-black text-green-900 font-bold">
                                ${{ number_format($total, 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 顧客資訊和付款 -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">顧客資訊</h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- 姓名 -->
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                                姓名 <span class="text-red-500">*</span>
                                @if(session('line_logged_in'))
                                    <span class="text-xs text-green-600 ml-2">
                                        <i class="fab fa-line mr-1"></i>已從 LINE 登入
                                    </span>
                                @endif
                            </label>
                            <input type="text"
                                   id="customer_name"
                                   name="customer_name"
                                   value="{{ old('customer_name', session('line_user.display_name', '')) }}"
                                   required
                                   maxlength="100"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="請輸入您的姓名"
                                   @if(session('line_logged_in')) readonly @endif>
                            @if(session('line_logged_in'))
                                <p class="mt-1 text-xs text-gray-500">LINE 登入的姓名將自動填入</p>
                            @endif
                            @error('customer_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 電話 -->
                        <div>
                            <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                電話 <span class="text-gray-400 text-xs">(選填)</span>
                            </label>
                            <input type="tel"
                                   id="customer_phone"
                                   name="customer_phone"
                                   value="{{ old('customer_phone') }}"
                                   maxlength="20"
                                   pattern="[0-9+\-()# ]*"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="請輸入您的電話號碼（選填）">
                            <p class="mt-1 text-xs text-gray-500">提供電話方便店家聯繫</p>
                            @error('customer_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 備註 -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                備註 (選填)
                            </label>
                            <textarea id="notes"
                                      name="notes"
                                      rows="3"
                                      maxlength="500"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="特殊需求或備註事項">{{ old('notes') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">最多 500 個字元</p>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 付款方式 -->
                <div class="bg-white rounded-lg shadow-sm mt-6">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">付款方式</h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-3">
                            <!-- 現金付款 -->
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="cash" checked class="mr-3">
                                <div class="flex items-center">
                                    <i class="fas fa-money-bill-wave text-green-600 mr-3"></i>
                                    <div>
                                        <div class="font-medium">現金付款</div>
                                        <div class="text-sm text-gray-500">到店付款</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 提交按鈕 -->
                <div class="mt-6">
                    <button type="submit"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        <i class="fas fa-check-circle mr-2"></i>確認下單
                    </button>

                    <div class="mt-3 text-center">
                        <p class="text-xs text-gray-500">
                            點擊「確認下單」即表示您同意我們的
                            <a href="#" class="text-blue-600 hover:text-blue-800">服務條款</a>
                            和
                            <a href="#" class="text-blue-600 hover:text-blue-800">隱私政策</a>
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 表單驗證和提交
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    // 基本表單驗證
    form.addEventListener('submit', function(e) {
        const customerNameInput = document.getElementById('customer_name');

        // 驗證必填欄位
        if (!customerNameInput || !customerNameInput.value.trim()) {
            e.preventDefault();
            alert('請填寫您的姓名');
            customerNameInput.focus();
            return false;
        }

        console.log('Order form validation passed');
    });

    // 電話號碼格式化
    const phoneInput = document.getElementById('customer_phone');
    phoneInput.addEventListener('input', function() {
        // 移除非數字字符（保留 +, -, (), # 和空格）
        this.value = this.value.replace(/[^0-9+\-()# ]/g, '');
    });

    // 備註字數計數
    const notesTextarea = document.getElementById('notes');
    const maxLength = 500;

    notesTextarea.addEventListener('input', function() {
        const remaining = maxLength - this.value.length;
        const counter = document.querySelector('.text-xs.text-gray-500');

        if (counter) {
            counter.textContent = `還剩 ${remaining} 個字元`;
            if (remaining < 50) {
                counter.classList.add('text-red-600');
                counter.classList.remove('text-gray-500');
            } else {
                counter.classList.remove('text-red-600');
                counter.classList.add('text-gray-500');
            }
        }
    });
});

// 返回按鈕確認
function confirmGoBack() {
    if (confirm('確定要返回購物車嗎？未保存的資訊將會遺失。')) {
        window.location.href = '{{ route('frontend.cart.index') }}';
    }
}
</script>
@endsection