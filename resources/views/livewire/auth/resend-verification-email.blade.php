<div>
    @if (session()->has('status'))
        <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-start" role="alert">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (!$emailSent)
        <form wire:submit="resend" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    信箱地址 <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 relative">
                    <input
                        wire:model="email"
                        type="email"
                        id="email"
                        class="block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-offset-0 sm:text-sm transition-colors @error('email') border-red-500 bg-red-50 focus:ring-red-500 focus:border-red-500 @else border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 @enderror"
                        placeholder="例如：user@example.com"
                        autocomplete="email"
                        autofocus
                        required
                    >
                    @error('email')
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @enderror
                </div>

                @error('email')
                    <div class="mt-2 p-3 text-sm rounded-md @if(str_contains($message, '✅')) bg-green-50 text-green-800 border border-green-200 @elseif(str_contains($message, '⏰')) bg-yellow-50 text-yellow-800 border border-yellow-200 @else bg-red-50 text-red-800 border border-red-200 @endif">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                @if(str_contains($message, '✅'))
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                @elseif(str_contains($message, '⏰'))
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                @else
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                @endif
                            </svg>
                            <span>{{ $message }}</span>
                        </div>

                        {{-- 如果已驗證，顯示登入連結 --}}
                        @if(str_contains($message, '✅'))
                            <div class="mt-3 pt-3 border-t border-green-200">
                                <a href="{{ route('login') }}" class="inline-flex items-center text-sm font-medium text-green-700 hover:text-green-600">
                                    前往登入頁面
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </a>
                            </div>
                        @endif

                        {{-- 如果尚未註冊，顯示註冊連結 --}}
                        @if(str_contains($message, '尚未在系統中註冊'))
                            <div class="mt-3 pt-3 border-t border-red-200">
                                <a href="{{ route('merchant.register') }}" class="inline-flex items-center text-sm font-medium text-red-700 hover:text-red-600">
                                    前往註冊頁面
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                @enderror

                @error('general')
                    <div class="mt-2 p-3 text-sm bg-red-50 text-red-800 border border-red-200 rounded-md">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    </div>
                @enderror

                {{-- 輸入提示 --}}
                @if(!$errors->has('email'))
                    <p class="mt-2 text-xs text-gray-500 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        請輸入您註冊時使用的信箱地址
                    </p>
                @endif
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        重新發送驗證信
                    </span>
                    <span wire:loading class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        發送中...
                    </span>
                </button>
            </div>
        </form>
    @else
        <div class="text-center space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <div>
                <h3 class="text-lg font-medium text-gray-900">✉️ 驗證信已發送</h3>
                <p class="mt-2 text-sm text-gray-600">
                    我們已經將驗證信重新發送至 <strong class="text-indigo-600">{{ $email }}</strong>
                </p>
                <div class="mt-3 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800">
                        <svg class="inline w-5 h-5 mr-1 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        請檢查您的信箱（包含垃圾郵件夾），並點擊驗證連結以完成註冊。
                    </p>
                </div>
            </div>

            <div class="pt-4 space-x-4">
                <a href="/" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    返回首頁
                </a>
                <button wire:click="$set('emailSent', false)" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    使用其他信箱
                </button>
            </div>
        </div>
    @endif
</div>
