<div>
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="login" class="space-y-6">
        {{-- Email Address --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">電子郵件</label>
            <div class="mt-1">
                <input id="email" type="email" wire:model.lazy="email" required autofocus autocomplete="username"
                       class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-500 @enderror">
            </div>
            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">密碼</label>
            <div class="mt-1">
                <input id="password" type="password" wire:model.lazy="password" required autocomplete="current-password"
                       class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-500 @enderror">
            </div>
            @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember_me" type="checkbox" wire:model.lazy="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="remember_me" class="block ml-2 text-sm text-gray-900">記住我</label>
            </div>
        </div>

        <div>
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="flex items-center justify-center w-full px-4 py-3 text-white font-medium bg-blue-600 rounded-lg transition-all duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                <span wire:loading.remove wire:target="login">
                    登入
                </span>
                <span wire:loading wire:target="login">
                    處理中...
                </span>
            </button>
        </div>
    </form>
</div>
