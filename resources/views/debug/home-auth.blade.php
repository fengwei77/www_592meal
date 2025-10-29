@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">首頁認證狀態檢查</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Laravel 認證檢查</h2>

        @php
            $isLoggedInCustomer = Auth::guard('customer')->check();
            $customer = Auth::guard('customer')->user();
        @endphp

        <div class="space-y-2">
            <p><strong>Auth::guard('customer')->check():</strong> {{ $isLoggedInCustomer ? '✅ 已登入' : '❌ 未登入' }}</p>

            @if($isLoggedInCustomer)
                <p><strong>用戶名稱:</strong> {{ $customer->name }}</p>
                <p><strong>用戶 ID:</strong> {{ $customer->id }}</p>
                <p><strong>LINE ID:</strong> {{ $customer->line_id }}</p>
                <p><strong>頭像:</strong> {{ $customer->avatar_url ?? 'N/A' }}</p>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Session 檢查</h2>

        <div class="space-y-2">
            <p><strong>line_logged_in:</strong> {{ session('line_logged_in', false) ? '✅ true' : '❌ false' }}</p>
            <p><strong>line_user:</strong> {{ session('line_user') ? json_encode(session('line_user')) : 'null' }}</p>
            <p><strong>Session ID:</strong> {{ session()->getId() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">首頁按鈕模擬</h2>

        @if(!$isLoggedInCustomer)
            <div class="p-4 bg-red-50 rounded">
                <p class="text-red-700 font-medium">❌ 未登入 - 應該顯示 LINE 登入按鈕</p>
                <a href="{{ route('line.login') }}" class="inline-block mt-2 px-4 py-2 bg-green-600 text-white rounded">
                    我要訂餐（LINE 登入）
                </a>
            </div>
        @else
            <div class="p-4 bg-green-50 rounded">
                <p class="text-green-700 font-medium">✅ 已登入 - 應該顯示前往訂餐按鈕</p>
                <div class="mt-2">
                    <p>歡迎回來, {{ $customer->name }}!</p>
                    <a href="{{ route('frontend.stores.index') }}" class="inline-block mt-2 px-4 py-2 bg-blue-600 text-white rounded">
                        前往訂餐
                    </a>
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">操作按鈕</h2>

        <div class="space-x-4">
            <a href="{{ route('home') }}" class="inline-block px-4 py-2 bg-gray-600 text-white rounded">
                回首頁
            </a>

            @if($isLoggedInCustomer)
                <form action="{{ route('logout') }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">
                        登出
                    </button>
                </form>
            @else
                <a href="{{ route('line.login') }}" class="inline-block px-4 py-2 bg-green-600 text-white rounded">
                    LINE 登入
                </a>
            @endif
        </div>
    </div>
</div>
@endsection