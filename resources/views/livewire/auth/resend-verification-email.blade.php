<div>
    @if (session()->has('status'))
        <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if (!$emailSent)
        <form wire:submit="resend" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    信箱地址
                </label>
                <div class="mt-1">
                    <input
                        wire:model.blur="email"
                        type="email"
                        id="email"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('email') border-red-500 @enderror"
                        placeholder="請輸入您的註冊信箱"
                        autofocus
                    >
                </div>
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('general')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>重新發送驗證信</span>
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
                <h3 class="text-lg font-medium text-gray-900">驗證信已發送</h3>
                <p class="mt-2 text-sm text-gray-600">
                    我們已經將驗證信重新發送至 <strong>{{ $email }}</strong>
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    請檢查您的信箱（包含垃圾郵件夾），並點擊驗證連結以完成註冊。
                </p>
            </div>

            <div class="pt-4">
                <a href="/" class="text-sm text-indigo-600 hover:text-indigo-500">
                    返回首頁
                </a>
            </div>
        </div>
    @endif
</div>
