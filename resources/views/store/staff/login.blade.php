<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>店員登入 - {{ $store->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- 店家資訊 -->
        <div class="text-center mb-8">
            <div class="inline-block bg-white rounded-full p-4 shadow-lg mb-4">
                <i class="fas fa-store text-4xl text-orange-600"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">{{ $store->name }}</h1>
            <p class="text-gray-600">店員登入</p>
        </div>

        <!-- 登入表單 -->
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('admin.store.staff.login.submit', $store->store_slug_name) }}" method="POST">
                @csrf

                <!-- 密碼輸入 -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1"></i>
                        店員密碼
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors @error('password') border-red-500 @enderror"
                        placeholder="請輸入密碼"
                        autofocus
                        required
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- 登入按鈕 -->
                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-orange-600 to-red-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-orange-700 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    登入
                </button>
            </form>

            <!-- 說明文字 -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-500 text-center">
                    <i class="fas fa-info-circle mr-1"></i>
                    請輸入店家設定的店員密碼以訪問訂單管理系統
                </p>
            </div>
        </div>

        <!-- 返回首頁 -->
        <div class="mt-6 text-center">
            <a href="{{ route('frontend.store.detail', $store->store_slug_name) }}" class="text-gray-600 hover:text-gray-800 transition-colors inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                返回店家首頁
            </a>
        </div>
    </div>

    <!-- 自動聚焦密碼輸入框 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>
