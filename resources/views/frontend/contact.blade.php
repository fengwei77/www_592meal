@extends('frontend.layouts.app')

@section('title', '聯絡我們 - ' . ($store->name ?? '592Meal'))

@section('content')
<div class="bg-white">
    <!-- 頁面標題橫幅 -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">聯絡我們</h1>
                <p class="text-xl text-blue-100">有任何問題或建議？我們很樂意為您服務</p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- 聯絡表單 -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">傳送訊息</h2>

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
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
                @endif

                <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- 姓名 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            姓名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               required
                               maxlength="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="請輸入您的姓名">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 電子郵件 -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            電子郵件 <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               maxlength="255"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="請輸入您的電子郵件（例如：user@example.com）">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            我們會將回覆訊息寄送至您的電子郵件
                        </p>
                    </div>

                    <!-- 電話 -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            電話（選填）
                        </label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               value="{{ old('phone') }}"
                               maxlength="20"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="請輸入您的電話號碼（選填）">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 主題 -->
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                            主題 <span class="text-red-500">*</span>
                        </label>
                        <select id="subject"
                                name="subject"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">請選擇主題</option>
                            <option value="general">一般問題</option>
                            <option value="technical">技術支援</option>
                            <option value="partnership">合作提案</option>
                            <option value="feedback">意見回饋</option>
                            <option value="other">其他</option>
                        </select>
                        @error('subject')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 訊息內容 -->
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            訊息內容 <span class="text-red-500">*</span>
                        </label>
                        <textarea id="message"
                                  name="message"
                                  rows="6"
                                  required
                                  maxlength="1000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="請詳細描述您的問題或建議">{{ old('message') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">最多 1000 個字元</p>
                        @error('message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 提交按鈕 -->
                    <div>
                        <button type="submit"
                                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>傳送訊息
                        </button>
                        <p class="mt-2 text-xs text-gray-500 text-center">
                            我們會在 24 小時內回覆您的訊息
                        </p>
                    </div>
                </form>
            </div>

            <!-- 聯絡資訊 -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">聯絡資訊</h2>

                @if(isset($store))
                    <!-- 店家資訊 -->
                    <div class="bg-blue-50 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $store->name }}</h3>

                        @if($store->phone)
                            <div class="flex items-center mb-3">
                                <i class="fas fa-phone text-blue-600 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">電話</p>
                                    <p class="font-medium">{{ $store->phone }}</p>
                                </div>
                            </div>
                        @endif

                        @if($store->address)
                            <div class="flex items-center mb-3">
                                <i class="fas fa-map-marker-alt text-blue-600 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">地址</p>
                                    <p class="font-medium">{{ $store->address }}</p>
                                </div>
                            </div>
                        @endif

                        @if($store->business_hours)
                            <div class="flex items-center">
                                <i class="fas fa-clock text-blue-600 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-600">營業時間</p>
                                    @if($store->isCurrentlyOpen())
                                        <p class="text-green-600 font-medium">營業中</p>
                                    @else
                                        <p class="text-red-600 font-medium">休息中</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- 一般聯絡方式 -->
                <div class="bg-gray-50 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">一般聯絡方式</h3>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-gray-600 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">電子郵件</p>
                                <p class="font-medium">support@592meal.com</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <i class="fas fa-phone text-gray-600 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">客服電話</p>
                                <p class="font-medium">0800-592-592</p>
                                <p class="text-xs text-gray-500">週一至週五 9:00-18:00</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <i class="fab fa-line text-gray-600 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">LINE 官方帳號</p>
                                <p class="font-medium">@592meal</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 常見問題 -->
                <div class="bg-yellow-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">常見問題</h3>

                    <div class="space-y-4">
                        <details class="cursor-pointer">
                            <summary class="font-medium text-gray-900 hover:text-blue-600">如何開始訂餐？</summary>
                            <p class="mt-2 text-sm text-gray-600">
                                1. 瀏覽店家菜單<br>
                                2. 將喜歡的商品加入購物車<br>
                                3. 填寫聯絡資訊<br>
                                4. 確認下單
                            </p>
                        </details>

                        <details class="cursor-pointer">
                            <summary class="font-medium text-gray-900 hover:text-blue-600">可以修改或取消訂單嗎？</summary>
                            <p class="mt-2 text-sm text-gray-600">
                                訂單確認後，請立即聯繫店家進行修改或取消。店家聯絡方式可在訂單確認頁面找到。
                            </p>
                        </details>

                        <details class="cursor-pointer">
                            <summary class="font-medium text-gray-900 hover:text-blue-600">付款方式有哪些？</summary>
                            <p class="mt-2 text-sm text-gray-600">
                                目前支援現金付款，到店取餐時付款。未來將會提供更多付款方式選擇。
                            </p>
                        </details>

                        <details class="cursor-pointer">
                            <summary class="font-medium text-gray-900 hover:text-blue-600">店家如何加入平台？</summary>
                            <p class="mt-2 text-sm text-gray-600">
                                請點擊「店家註冊」按鈕，填寫基本資料即可加入我們的平台。我們會盡快審核您的申請。
                            </p>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 表單驗證和提交
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;

        // 禁用提交按鈕，防止重複提交
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>傳送中...';

        // 5秒後恢復按鈕（防止長時間等待）
        setTimeout(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }, 5000);
    });

    // 字數計數
    const messageTextarea = document.getElementById('message');
    const maxLength = 1000;

    messageTextarea.addEventListener('input', function() {
        const remaining = maxLength - this.value.length;
        const counter = document.querySelector('.text-xs.text-gray-500');

        if (counter) {
            counter.textContent = `還剩 ${remaining} 個字元`;
            if (remaining < 100) {
                counter.classList.add('text-red-600');
                counter.classList.remove('text-gray-500');
            } else {
                counter.classList.remove('text-red-600');
                counter.classList.add('text-gray-500');
            }
        }
    });
});
</script>
@endsection