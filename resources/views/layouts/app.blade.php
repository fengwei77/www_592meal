<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', '592Meal') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chocolate+Classical+Sans&family=Reenie+Beanie&display=swap" rel="stylesheet">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom Styles -->
    <style>
        /* 字型定義 */
        .reenie-beanie-regular {
            font-family: "Reenie Beanie", cursive;
            font-weight: 400;
            font-style: normal;
        }

        .chocolate-classical-sans-regular {
            font-family: "Chocolate Classical Sans", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        /* 網站名稱字型 */
        .site-name {
            font-family: "Reenie Beanie", cursive;
            font-weight: 400;
            font-style: normal;
            color: #FB923C; /* 橘色 */
        }

        /* 前台標題字型 */
        .frontend-title {
            font-family: "Chocolate Classical Sans", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        /* 前台內容字型 */
        .frontend-content {
            font-family: "Chocolate Classical Sans", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        /* 整個頁面內容字型 */
        body {
            font-family: "Chocolate Classical Sans", sans-serif;
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased bg-gray-50 flex flex-col min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-4xl font-bold site-name">
                        592Meal
                    </a>
                </div>

                <!-- User Menu -->
                <div class="flex items-center">
                    @if(session('line_logged_in') && session('line_user'))
                        @php
                            $lineUser = session('line_user');
                        @endphp
                        <!-- Authenticated Customer -->
                        <div class="flex items-center space-x-4">
                            <!-- Customer Avatar & Name -->
                            <div class="flex items-center">
                                @if(!empty($lineUser['picture_url']))
                                    <img src="{{ $lineUser['picture_url'] }}"
                                         alt="{{ $lineUser['display_name'] }}"
                                         class="w-10 h-10 rounded-full border-2 border-gray-200">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center border-2 border-gray-200">
                                        <span class="text-gray-600 font-semibold text-sm">
                                            {{ mb_substr($lineUser['display_name'] ?? '用戶', 0, 2) }}
                                        </span>
                                    </div>
                                @endif
                                <span class="ml-3 text-sm font-medium text-gray-700">
                                    {{ $lineUser['display_name'] ?? '用戶' }}
                                </span>
                            </div>

                            <!-- Notification Settings Button -->
                            <a href="{{ route('customer.notifications.settings') }}"
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors inline-flex items-center">
                                <i class="fas fa-bell mr-1.5"></i>
                                通知設定
                            </a>

                            <!-- Logout Button -->
                            <form method="POST" action="{{ route('line.logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    登出
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- Guest User -->
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 text-sm font-medium text-white rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                               style="background-color: #06C755;">
                                <svg class="w-5 h-5 inline-block mr-1" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                                LINE 登入
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <a href="{{ route('merchant.register') }}" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                店家註冊
            </a>
            <p class="mt-4 text-xs text-gray-400">
                &copy; {{ date('Y') }} <span class="site-name">592Meal</span>. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- 設定用戶資訊給 JavaScript -->
    @if(session('line_logged_in') && session('line_user'))
        @php
            $lineUser = session('line_user');
            $customer = \App\Models\Customer::where('line_id', $lineUser['user_id'])->first();
        @endphp
        @if($customer)
            <script>
                window.currentUser = {
                    id: {{ $customer->id }},
                    name: '{{ $lineUser['display_name'] }}',
                    lineId: '{{ $lineUser['user_id'] }}'
                };
            </script>
        @endif
    @endif

    <!-- 設定 VAPID 公鑰 -->
    <script>
        window.vapidPublicKey = '{{ config('broadcasting.push.vapid.public_key', 'BD7y3xvsnG7PK4t2NRbIci5oBFSkB6-mniFjRxhywHQXi-ylnp1y4EO_es9Yx5CJYDo-KLWtw5fiEGHYHyKC_S4') }}';
    </script>

    <!-- Custom Scripts -->
    @stack('scripts')
</body>
</html>
