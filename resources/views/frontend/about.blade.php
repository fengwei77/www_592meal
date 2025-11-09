@extends('frontend.layouts.app')

@section('title', '關於我們 - ' . ($store->name ?? '592Meal'))

@section('content')
<div class="bg-white">
    <!-- 頁面標題橫幅 -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4 frontend-title">關於我們</h1>
                <p class="text-xl text-orange-100 frontend-content">了解我們的故事與理念</p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- 主要內容 -->
            <div class="lg:col-span-2">
                <!-- 我們的故事 -->
                <section class="mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 frontend-title">我們的故事</h2>
                    <div class="prose prose-lg text-gray-600 frontend-content">
                        <p class="mb-4">
                            <span class="site-name">592Meal</span> 成立於 2024 年，致力於為街邊小吃店家提供數位化解決方案。我們深刻理解傳統小吃店家在數位時代面臨的挑戰，包括線上訂餐、客戶管理、營運效率等議題。
                        </p>
                        <p class="mb-4">
                            透過我們的平台，店家可以輕鬆建立自己的線上訂餐系統，讓顧客能夠方便地瀏覽菜單、下單取餐。我們相信，科技的運用能夠幫助傳統小吃業者在保持獨特風味的同时，提升營運效率和客戶體驗。
                        </p>
                        <p>
                            「592」不僅僅是數字，它代表著台灣街頭巷尾無數美食的可能性。我們希望每一個有夢想的小吃店家，都能透過我們的平台，讓更多人品嚐到他們用心製作的美食。
                        </p>
                    </div>
                </section>

                <!-- 我們的使命 -->
                <section class="mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 frontend-title">我們的使命</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-utensils text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 frontend-title">賦能店家</h3>
                            </div>
                            <p class="text-gray-600 frontend-content">
                                提供簡單易用的數位工具，讓傳統小吃店家能夠輕鬆轉型線上，擴大服務範圍。
                            </p>
                        </div>

                        <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 frontend-title">服務顧客</h3>
                            </div>
                            <p class="text-gray-600 frontend-content">
                                讓顧客能夠方便地發現和訂購優質的街邊美食，享受更好的用餐體驗。
                            </p>
                        </div>

                        <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-heart text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 frontend-title">傳承文化</h3>
                            </div>
                            <p class="text-gray-600 frontend-content">
                                協助保存和推廣台灣豐富的小吃文化，讓這些美味能夠持續傳承下去。
                            </p>
                        </div>

                        <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-lightbulb text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 frontend-title">創新服務</h3>
                            </div>
                            <p class="text-gray-600 frontend-content">
                                不斷創新和改進服務，為店家與顧客創造更大的價值。
                            </p>
                        </div>
                    </div>
                </section>

                <!-- 核心價值 -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 frontend-title">核心價值</h2>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 frontend-title">簡單易用</h3>
                                <p class="text-gray-600 frontend-content">
                                    我們的平台設計簡單直觀，讓店家無需技術背景也能快速上手，顧客也能輕鬆完成訂餐流程。
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 frontend-title">信賴可靠</h3>
                                <p class="text-gray-600 frontend-content">
                                    我們重視每一個店家與顧客的信任，提供穩定可靠的服務，確保資訊安全與交易保障。
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 frontend-title">持續改進</h3>
                                <p class="text-gray-600 frontend-content">
                                    我們不斷收集使用者回饋，持續改進產品功能與使用體驗，為用戶創造更大價值。
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- 側邊欄 - 快速連結 -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 space-y-6">
                    <!-- 快速導航 -->
                    <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 frontend-title">快速導航</h3>
                        <nav class="space-y-2">
                            <a href="#story" class="block text-gray-600 hover:text-orange-600 py-2">
                                <i class="fas fa-chevron-right mr-2"></i>我們的故事
                            </a>
                            <a href="#mission" class="block text-gray-600 hover:text-orange-600 py-2">
                                <i class="fas fa-chevron-right mr-2"></i>我們的使命
                            </a>
                            <a href="#values" class="block text-gray-600 hover:text-orange-600 py-2">
                                <i class="fas fa-chevron-right mr-2"></i>核心價值
                            </a>
                        </nav>
                    </div>

                    <!-- 聯絡資訊 -->
                    @if(isset($store))
                        <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 frontend-title">聯絡我們</h3>
                            @if($store->phone)
                                <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">電話</p>
                                    <p class="font-medium">{{ $store->phone }}</p>
                                </div>
                            @endif
                            @if($store->address)
                                <div class="mb-3">
                                    <p class="text-sm text-gray-600 mb-1">地址</p>
                                    <p class="font-medium">{{ $store->address }}</p>
                                </div>
                            @endif
                            <div class="mb-3">
                                <p class="text-sm text-gray-600 mb-1">營業時間</p>
                                @if($store->isCurrentlyOpen())
                                    <p class="text-green-600 font-medium">營業中</p>
                                @else
                                    <p class="text-red-600 font-medium">休息中</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- 加入我們 -->
                    <div class="bg-orange-50 rounded-lg p-6 border border-orange-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 frontend-title">加入我們</h3>
                        <p class="text-gray-600 text-sm mb-4 frontend-content">
                            如果您也是小吃店家，歡迎加入 <span class="site-name">592Meal</span> 平台，讓更多人品嚐您的美食！
                        </p>
                        <a href="{{ route('merchant.register') }}" class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm">
                            <i class="fas fa-store mr-2"></i>店家註冊
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection