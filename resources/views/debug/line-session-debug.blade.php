<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LINE Session Debug - 592Meal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">LINE Session 診斷工具</h1>

        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h2 class="text-xl font-semibold mb-4">Session 狀態</h2>
            <div class="space-y-2 font-mono text-sm">
                <div><strong>Session ID:</strong> {{ session()->getId() }}</div>
                <div><strong>LINE 登入狀態:</strong> {{ session('line_logged_in') ? '✅ 已登入' : '❌ 未登入' }}</div>
                <div><strong>Customer Auth:</strong> {{ auth('customer')->check() ? '✅ 已登入' : '❌ 未登入' }}</div>
                @if(auth('customer')->check())
                    <div><strong>Customer ID:</strong> {{ auth('customer')->id() }}</div>
                    <div><strong>Customer Name:</strong> {{ auth('customer')->name }}</div>
                @endif
                @if(session('line_user'))
                    <div><strong>LINE 用戶 ID:</strong> {{ session('line_user.user_id') }}</div>
                    <div><strong>LINE 用戶名:</strong> {{ session('line_user.display_name') }}</div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h2 class="text-xl font-semibold mb-4">所有 Session 資料</h2>
            <pre class="text-xs bg-gray-100 p-4 rounded overflow-x-auto">
{{ json_encode(session()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
            </pre>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h2 class="text-xl font-semibold mb-4">Request 資訊</h2>
            <div class="space-y-2 text-sm">
                <div><strong>URL:</strong> {{ url()->current() }}</div>
                <div><strong>Domain:</strong> {{ request()->getHost() }}</div>
                <div><strong>Method:</strong> {{ request()->method() }}</div>
                <div><strong>Session Driver:</strong> {{ config('session.driver') }}</div>
                <div><strong>Session Domain:</strong> {{ config('session.domain') }}</div>
                <div><strong>Session Path:</strong> {{ config('session.path') }}</div>
                <div><strong>Session Secure:</strong> {{ config('session.secure') ? 'Yes' : 'No' }}</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h2 class="text-xl font-semibold mb-4">Cookies</h2>
            <div class="space-y-2 text-sm">
                @foreach(request()->cookie() as $name => $value)
                    <div><strong>{{ $name }}:</strong> {{ $value }}</div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">測試操作</h2>
            <div class="space-x-4">
                <a href="{{ route('line.login') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    LINE 登入測試
                </a>
                <a href="{{ route('line.logout') }}" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    LINE 登出測試
                </a>
                <a href="{{ route('frontend.stores.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    回到店家清單
                </a>
            </div>
        </div>
    </div>
</body>
</html>