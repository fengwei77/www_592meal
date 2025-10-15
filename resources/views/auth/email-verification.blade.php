<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>驗證您的 Email - 592Meal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container mx-auto max-w-md p-6">
        <div class="bg-white p-8 rounded-lg shadow-md">

            {{-- Debug: 顯示所有 session 資料 --}}
            @if(config('app.debug'))
                <div class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                    <strong>Debug Session:</strong><br>
                    verification_success: {{ session('verification_success') ? 'true' : 'false' }}<br>
                    verified_email: {{ session('verified_email') ?? 'NULL' }}<br>
                    status: {{ session('status') ?? 'NULL' }}
                </div>
            @endif

            {{-- 驗證成功狀態 --}}
            @if(session('verification_success'))
                <div class="text-center">
                    <div class="mb-6">
                        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h1 class="text-3xl font-bold mb-4 text-center text-green-600">驗證成功！</h1>

                    <div class="mb-6 p-4 bg-green-50 rounded-lg">
                        <p class="text-lg font-medium text-green-800 mb-2">
                            🎉 恭喜！您的 Email 已成功驗證
                        </p>
                        <p class="text-sm text-green-700">
                            您的帳號已啟用，現在可以登入後台開始使用 592Meal 平台
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <h3 class="font-semibold text-blue-900 mb-2">📧 後台登入資訊</h3>
                            <div class="text-left space-y-2">
                                <div class="flex items-center justify-between p-2 bg-white rounded border">
                                    <span class="text-sm text-gray-600">登入 Email：</span>
                                    <span class="font-medium">{{ session('verified_email') }}</span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white rounded border">
                                    <span class="text-sm text-gray-600">後台網址：</span>
                                    <span class="font-medium text-blue-600">{{ config('app.admin_url') }}/login</span>
                                </div>
                            </div>
                        </div>

                        <a href="{{ config('app.admin_url') }}/login"
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg text-center transition duration-200">
                            立即前往後台登入
                        </a>

                        <p class="text-xs text-gray-500 text-center">
                            我們也已將後台登入連結發送至您的郵箱
                        </p>
                    </div>
                </div>

            {{-- 一般驗證狀態 --}}
            @else
                <h1 class="text-2xl font-bold mb-6 text-center">驗證您的 Email</h1>

                <div class="mb-4 text-sm text-gray-600">
                    感謝您的註冊！在開始之前，請點擊我們剛剛寄給您的 Email 中的連結來驗證您的 Email 地址。如果您沒有收到郵件，我們很樂意重新寄送一封。
                </div>

                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4">
                        @foreach($errors->all() as $error)
                            <div class="text-sm text-red-600">{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <!-- 驗證碼輸入表單 -->
                <form method="POST" action="{{ route('verification.verify') }}" class="mb-6">
                    @csrf

                    <!-- Email Address (預填) -->
                    <div class="mb-4">
                        <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ $email }}" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required readonly>
                    </div>

                    <!-- Verification Code -->
                    <div class="mb-4">
                        <label for="code" class="block font-medium text-sm text-gray-700">6 位數驗證碼</label>
                        <input id="code" type="text" name="code" placeholder="請輸入 6 位數驗證碼" maxlength="6" pattern="[0-9]{6}" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <p class="mt-1 text-xs text-gray-500">請輸入您在郵件中收到的 6 位數驗證碼</p>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            驗證
                        </button>
                    </div>
                </form>

                <div class="mt-6 p-4 bg-blue-50 rounded-md">
                    <p class="text-sm text-blue-800">
                        <strong>驗證說明：</strong><br>
                        請檢查您的收件匣 <strong>{{ $email }}</strong> 中的驗證郵件，輸入其中的 6 位數驗證碼完成驗證。<br>
                        如果沒有收到郵件，請使用下方的「重新發送」功能。
                    </p>
                </div>

                <div class="mt-6 border-t pt-6">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <p class="text-center text-sm text-gray-600">
                            沒有收到驗證信？
                            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                點此重新發送
                            </button>
                        </p>
                    </form>
                </div>
            @endif
        </div>
    </div>
</body>
</html>