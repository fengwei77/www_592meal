@extends('layouts.app')

@section('title', '登入狀態調試')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">登入狀態調試</h1>

    <!-- Session 資訊 -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Session 資訊</h2>

        <div class="space-y-3">
            <div class="flex justify-between items-center py-2 border-b">
                <span class="font-medium">LINE 登入狀態:</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium @if(session('line_logged_in')) bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                    {{ session('line_logged_in') ? '已登入' : '未登入' }}
                </span>
            </div>

            <div class="flex justify-between items-center py-2 border-b">
                <span class="font-medium">LINE 用戶資料:</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium @if(session('line_user')) bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                    {{ session('line_user') ? '存在' : '不存在' }}
                </span>
            </div>

            @if(session('line_user'))
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium text-gray-900 mb-2">LINE 用戶詳細資料:</h3>
                    <pre class="text-sm text-gray-700 bg-white p-3 rounded border overflow-x-auto">{{ json_encode(session('line_user'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>
    </div>

    <!-- 導航按鈕 -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">導航按鈕</h2>

        <div class="space-y-4">
            @if(session('line_logged_in') && session('line_user'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-green-800 mb-3">✅ 您已通過 LINE 登入，應該可以看到通知設定按鈕</p>
                    <a href="{{ route('customer.notifications.settings') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        前往通知設定
                    </a>
                </div>
            @else
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-yellow-800 mb-3">⚠️ 您尚未通過 LINE 登入，因此看不到通知設定按鈕</p>
                    <a href="{{ route('line.login') }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.345 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        LINE 登入
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- 操作按鈕 -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">操作</h2>

        <div class="space-y-3">
            @if(session('line_logged_in'))
                <form method="POST" action="{{ route('line.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        登出 LINE
                    </button>
                </form>
            @endif

            <a href="{{ route('home') }}" class="inline-block px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                回到首頁
            </a>

            <a href="/auth/line/check" class="inline-block px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                檢查登入狀態 (API)
            </a>
        </div>
    </div>
</div>
@endsection