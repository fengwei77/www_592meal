@extends('frontend.layouts.app')

@section('title', '帳號已被封鎖')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- 封鎖圖標 -->
            <div class="mx-auto h-24 w-24 flex items-center justify-center rounded-full bg-red-100">
                <i class="fas fa-ban text-red-600 text-5xl"></i>
            </div>

            <!-- 標題 -->
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                帳號已被封鎖
            </h2>

            <!-- 說明文字 -->
            <p class="mt-4 text-sm text-gray-600">
                您的帳號因違反使用規範，已被多個店家封鎖，目前無法使用本平台服務。
            </p>
        </div>

        <!-- 封鎖詳情卡片 -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-red-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-red-600 mr-2"></i>封鎖原因
            </h3>

            <div class="space-y-3 text-sm text-gray-700">
                @if(session('line_logged_in') && session('line_user'))
                    @php
                        $lineUserId = session('line_user.user_id');
                        $blockedStores = \App\Models\StoreCustomerBlock::getBlockedStores($lineUserId);
                        $blockCount = $blockedStores->count();
                    @endphp

                    <div class="flex items-start">
                        <i class="fas fa-store text-gray-400 mt-1 mr-2"></i>
                        <div>
                            <span class="font-medium">被封鎖店家數量：</span>
                            <span class="text-red-600 font-bold">{{ $blockCount }} 個店家</span>
                        </div>
                    </div>

                    @if($blockedStores->isNotEmpty())
                        <div class="mt-4">
                            <p class="font-medium mb-2">被封鎖的店家列表：</p>
                            <ul class="space-y-2">
                                @foreach($blockedStores as $block)
                                    <li class="flex items-center p-2 bg-gray-50 rounded">
                                        <i class="fas fa-ban text-red-500 mr-2"></i>
                                        <div class="flex-1">
                                            <span class="font-medium">{{ $block->store->name }}</span>
                                            <span class="text-xs text-gray-500 block">
                                                封鎖時間：{{ $block->blocked_at->format('Y/m/d H:i') }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-xs text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            當您被3個或以上店家封鎖時，將無法使用平台的任何服務。
                        </p>
                    </div>
                @else
                    <p class="text-gray-600">請先登入以查看詳細資訊。</p>
                @endif
            </div>
        </div>

        <!-- 解決方案 -->
        <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>如何解除封鎖？
            </h3>

            <div class="space-y-3 text-sm text-gray-700">
                <div class="flex items-start">
                    <i class="fas fa-phone text-blue-600 mt-1 mr-2"></i>
                    <div>
                        <span class="font-medium">聯絡店家：</span>
                        <p class="text-gray-600 mt-1">
                            請分別聯絡封鎖您的店家，說明情況並請求解除封鎖。
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <i class="fas fa-headset text-blue-600 mt-1 mr-2"></i>
                    <div>
                        <span class="font-medium">聯絡平台客服：</span>
                        <p class="text-gray-600 mt-1">
                            如有疑問，可聯絡平台客服尋求協助。
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作按鈕 -->
        <div class="space-y-2">
            <a href="{{ route('frontend.stores.index') }}"
               class="w-full flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <i class="fas fa-home mr-2"></i>返回首頁
            </a>

            @if(!session('line_logged_in'))
                <a href="{{ route('line.login') }}"
                   class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fab fa-line mr-2"></i>LINE 登入
                </a>
            @endif
        </div>

        <!-- 注意事項 -->
        <div class="text-center text-xs text-gray-500">
            <p>
                <i class="fas fa-shield-alt mr-1"></i>
                請遵守平台使用規範，避免再次被封鎖
            </p>
        </div>
    </div>
</div>
@endsection
