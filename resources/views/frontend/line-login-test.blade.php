@extends('frontend.layouts.app')

@section('title', 'LINE 登入測試')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 標題 -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <span class="text-green-500">LINE</span> 登入測試工具
            </h1>
            <p class="text-gray-600">
                測試和診斷 LINE 登入功能的問題
            </p>
        </div>

        <!-- 訊息提示 -->
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">錯誤</h3>
                        <div class="mt-2 text-sm text-red-700">{{ session('error') }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414l-2.586 2.586z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">成功</h3>
                        <div class="mt-2 text-sm text-green-700">{{ session('success') }}</div>

                        @if(session('profile'))
                            <div class="mt-3 p-3 bg-white rounded border border-green-200">
                                <h4 class="font-semibold text-green-800 mb-2">用戶資料：</h4>
                                <p><strong>用戶ID：</strong> {{ session('profile')['user_id'] }}</p>
                                <p><strong>顯示名稱：</strong> {{ session('profile')['display_name'] }}</p>
                                <p><strong>狀態訊息：</strong> {{ session('profile')['status_message'] ?? '無' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- 主要內容 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 左側：系統診斷 -->
            <div class="space-y-6">
                <!-- LINE 配置 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        LINE 配置狀態
                    </h2>

                    <div class="space-y-3">
                        @foreach($lineConfig as $key => $value)
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">
                                    {{ str_replace('_', ' ', ucfirst($key)) }}
                                </span>
                                <span class="text-sm {{ $value ? 'text-green-600' : 'text-red-600' }}">
                                    @if($value)
                                        {{ substr($value, 0, 20) }}{{ strlen($value) > 20 ? '...' : '' }}
                                    @else
                                        ❌ 未設定
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t">
                        <p class="text-xs text-gray-500">
                            🔗 當前測試 Callback URL: {{ $lineConfig['callback_url'] }}
                        </p>

                        <div class="mt-3 p-3 bg-yellow-50 rounded border border-yellow-200">
                            <h4 class="text-sm font-semibold text-yellow-800 mb-2">📝 LINE 開發者後台設定</h4>
                            <div class="text-xs text-yellow-700 space-y-1">
                                <p><strong>需要在 LINE 開發者後台添加以下 Callback URL：</strong></p>
                                <p class="font-mono bg-white p-2 rounded">{{ $lineConfig['callback_url'] }}</p>
                                <p class="text-xs mt-2">如果已有類似 URL，可以先刪除舊的再新增這個。</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 系統診斷 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-3.298 2.283-.455 1.653-.055 3.657 2.285 3.657.926 0 2.134-.399 2.733-1.025a.53.53 0 00.75-.75c-.418-.418-.816-.786-1.197-1.123-.416-.369-.812-.699-1.198-1.015a.53.53 0 00-.75.75c.418.418.816.786 1.197 1.123.416.369.812.699 1.198 1.015a.53.53 0 00.75-.75c-.418-.418-.816-.786-1.197-1.123zm-6.8-2.826c.455-.455.455-1.192 0-1.647 0l-.823.823a1.502 1.502 0 01-1.647 0l-.824-.824c-.455-.455-.455-1.192 0-1.647l.824-.823a1.502 1.502 0 011.647 0l.823.824c.455.455.455 1.192 0 1.647l-.823.823a1.502 1.502 0 01-1.647 0l-.824-.824c-.455-.455-.455-1.192 0-1.647l.824-.823a1.502 1.502 0 011.647 0l.823.824c.455.455.455 1.192 0 1.647l-.823.823a1.502 1.502 0 01-1.647 0z" clip-rule="evenodd" />
                        </svg>
                        系統診斷
                    </h2>

                    <div class="space-y-2">
                        @foreach($diagnostics as $key => $value)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                            <span class="text-sm text-gray-900 font-mono">{{ $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- 登入測試按鈕 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.567zm.034.965c.064.04.135.076.207.109a1.417 1.417 0 00.604-.13c.13-.033.258-.076.377-.13.331-.144.634-.35.865-.623a.535.535 0 00-.618.874c-.273.34-.607.605-.986.797a1.41 1.41 0 01-.865-.623 2.056 2.056 0 01-.377-.13 1.432 1.432 0 01-.207-.109c.09.09.196.196.346.327.311.27.637.489.986.623.258.073.534.13.865.13.317 0 .607-.057.865-.13.331-.144.634-.35.865-.623a.535.535 0 00-.618-.874c-.273.34-.607.605-.986.797a1.417 1.417 0 01-.865-.623c-.13-.033-.258-.076-.377-.13a2.057 2.057 0 01-.207-.109c.09.09.196.196.346.327.311.27.637.489.986.623.258.073.534.13.865.13.317 0 .607-.057.865-.13.331-.144.634-.35.865-.623a.535.535 0 00-.618-.874c-.273.34-.607.605-.986.797a1.417 1.417 0 01-.865-.623z"/>
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916.5.5 0 00-.908-.617A6 6 0 1118 8a.5.5 0 00-.254-.322A5 5 0 0010 11z" clip-rule="evenodd"/>
                        </svg>
                        登入測試
                    </h2>

                    <div class="space-y-3">
                        <a href="{{ route('line.login.test.auth') }}"
                           class="w-full flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 240 150">
                                <path fill="#00C300" d="M219.135 120.945c-5.101-13.22-15.641-24.972-26.795-29.617c-1.828-.767-3.744-1.462-5.732-2.082 8.896-5.459 16.857-12.411 22.752-20.437-4.625-3.6-9.945-6.419-15.677-8.298-13.22-4.262-27.376 2.898-37.543 14.946-10.166 12.049-16.208 32.642-11.98 51.5 4.227 18.857 25.451 33.532 51.5 29.305 10.915-2.808 20.925-7.632 29.021-14.239z"/>
                                <path fill="#00C300" d="M181.364 66.432c-11.982-3.914-24.819-5.465-37.543-4.749-4.312.237-8.56.703-12.713 1.38-6.842-10.523-10.354-23.418-9.742-37.543 4.749 7.938-9.742 16.401-9.742 25.194 0 15.677 6.842 31.354 18.857 43.377-2.036 5.101-4.976 9.742-8.6 13.732z"/>
                            </svg>
                            🚀 開始 LINE 登入測試
                        </a>

                        @if($authStatus['is_authenticated'])
                            <div class="p-3 bg-green-50 rounded border border-green-200">
                                <p class="text-sm text-green-800">
                                    ✅ 您已登入 ({{ $authStatus['guard'] }})
                                </p>
                            </div>
                        @else
                            <div class="p-3 bg-yellow-50 rounded border border-yellow-200">
                                <p class="text-sm text-yellow-800">
                                    ⚠️ 目前未登入，測試後不會自動登入系統
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 右側：詳細資訊 -->
            <div class="space-y-6">
                <!-- 認證狀態 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5v9a4.5 4.5 0 004.5 4.5h8a4.5 4.5 0 004.5-4.5v-9A4.5 4.5 0 0018 1h-8zm4.5 7.5a.5.5 0 01-.5.5h-4a.5.5 0 010-1h4a.5.5 0 01.5.5zm2.5-.5h1a.5.5 0 010 1h-1a.5.5 0 010-1z" clip-rule="evenodd"/>
                        </svg>
                        認證狀態
                    </h2>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">是否已認證</span>
                            <span class="text-sm {{ $authStatus['is_authenticated'] ? 'text-green-600' : 'text-red-600' }}">
                                {{ $authStatus['is_authenticated'] ? '✅ 已認證' : '❌ 未認證' }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">認證 Guard</span>
                            <span class="text-sm text-gray-900 font-mono">{{ $authStatus['guard'] }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Session ID</span>
                            <span class="text-sm text-gray-900 font-mono">{{ substr($authStatus['session_id'], 0, 8) }}...</span>
                        </div>

                        @if($authStatus['user'])
                            <div class="mt-3 pt-3 border-t">
                                <p class="text-sm font-medium text-gray-700 mb-2">已登入用戶：</p>
                                <div class="pl-3 space-y-1">
                                    <p class="text-sm">ID: {{ $authStatus['user']['id'] }}</p>
                                    <p class="text-sm">姓名: {{ $authStatus['user']['name'] ?? 'N/A' }}</p>
                                    <p class="text-sm">Email: {{ $authStatus['user']['email'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 最近的錯誤日誌 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        LINE 相關錯誤日誌
                    </h2>

                    @if(isset($recentErrors['message']))
                        <div class="text-sm text-gray-600">
                            <p>{{ $recentErrors['message'] }}</p>
                        </div>
                    @else
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($recentErrors as $error)
                                <div class="p-2 bg-red-50 rounded text-xs font-mono text-red-800 break-all">
                                    {{ $error }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- 測試步驟說明 -->
                <div class="bg-blue-50 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 011-1V9a1 1 0 00-1-1v-1a1 1 0 011-1H7a1 1 0 00-1 1v1a1 1 0 01-1 1v1a1 1 0 11-2 0v-2.17A3 3 0 019 7a3 3 0 016.417 2.17z" clip-rule="evenodd"/>
                        </svg>
                        測試步驟說明
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-2">1</span>
                            <p class="text-gray-700">檢查上方 LINE 配置是否正確設定</p>
                        </div>

                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-2">2</span>
                            <p class="text-gray-700">點擊「開始 LINE 登入測試」按鈕</p>
                        </div>

                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-2">3</span>
                            <p class="text-gray-700">在 LINE 中授權登入</p>
                        </div>

                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-2">4</span>
                            <p class="text-gray-700">查看測試結果和用戶資料</p>
                        </div>

                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-2">!</span>
                            <p class="text-gray-700">如果失敗，檢查錯誤日誌和配置</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection