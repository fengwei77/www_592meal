@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Flash Messages -->
        @if(session('status'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-green-700 font-medium">{{ session('status') }}</p>
                        <p class="text-xs text-green-600 mt-1">請前往您的信箱收取驗證碼，並按照指示完成帳號啟用。</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">
                歡迎來到 592Meal
            </h1>
            <p class="text-xl text-gray-600 mb-8">
                美味餐點,LINE 一下即可訂購
            </p>

            @guest('customer')
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <!-- 顧客登入 -->
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center px-6 py-3 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                       style="background-color: #06C755;">
                        <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        我要訂餐（LINE 登入）
                    </a>
                </div>
            @else
                <div class="text-center">
                    <p class="text-2xl text-gray-700 mb-4">
                        歡迎回來,{{ auth('customer')->user()->name }}!
                    </p>
                    <div class="flex gap-4 justify-center">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-800 underline">
                                登出
                            </button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>

        <!-- Features Section -->
        <div class="grid md:grid-cols-3 gap-8 mt-16">
            <div class="text-center p-6 bg-white rounded-lg shadow-sm">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">快速訂餐</h3>
                <p class="text-gray-600">LINE 登入,一鍵下單,省時省力</p>
            </div>

            <div class="text-center p-6 bg-white rounded-lg shadow-sm">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">品質保證</h3>
                <p class="text-gray-600">精選優質餐廳,美味有保障</p>
            </div>

            <div class="text-center p-6 bg-white rounded-lg shadow-sm">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">優惠活動</h3>
                <p class="text-gray-600">定期推出優惠,讓你吃得更划算</p>
            </div>
        </div>
    </div>
@endsection