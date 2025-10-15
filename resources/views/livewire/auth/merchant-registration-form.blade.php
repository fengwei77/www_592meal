<div>
    @if (session('status') && $registrationComplete)
        <!-- 註冊成功訊息 -->
        <div class="mb-6 p-6 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-green-800">🎉 註冊成功！</h3>
                    <div class="mt-2">
                        <p class="text-sm text-green-700">{{ session('status') }}</p>
                        <p class="text-xs text-green-600 mt-2">請前往您的信箱收取驗證碼，並按照指示完成帳號啟用。</p>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('verification.notice', ['email' => session('registered_email')]) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            前往驗證頁面
                        </a>
                        <a href="{{ route('home') }}"
                           class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            返回首頁
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- 註冊表單 -->
        @if (session('status'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-green-700 font-medium">{{ session('status') }}</p>
                        <p class="text-xs text-green-600 mt-1">請前往您的信箱收取驗證碼，並按照指示完成帳號啟用。</p>
                    </div>
                </div>
            </div>
        @endif

        @error('general')
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700">{{ $message }}</p>
            </div>
        @enderror

        <form wire:submit.prevent="submit" class="space-y-6">
        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">名稱</label>
            <div class="mt-1">
                <input id="name" type="text" wire:model.lazy="name" required autofocus autocomplete="name"
                       class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-500 @enderror">
            </div>
            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Email Address --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">電子郵件</label>
            <div class="mt-1">
                <input id="email" type="email" wire:model.lazy="email" required autocomplete="username"
                       class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-500 @enderror">
            </div>
            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">密碼</label>
            <div class="mt-1">
                <input id="password" type="password" wire:model.lazy="password" required autocomplete="new-password"
                       class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-500 @enderror">
            </div>
            @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">確認密碼</label>
            <div class="mt-1">
                <input id="password_confirmation" type="password" wire:model.lazy="password_confirmation" required autocomplete="new-password"
                       class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div>
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="flex items-center justify-center w-full px-4 py-3 text-white font-medium bg-blue-600 rounded-lg transition-all duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                <span wire:loading.remove wire:target="submit">
                    註冊
                </span>
                <span wire:loading wire:target="submit">
                    處理中...
                </span>
            </button>
        </div>
    </form>
    @endif
</div>