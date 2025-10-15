<div class="container mx-auto max-w-md p-6">
    <div class="bg-white p-8 rounded-lg shadow-md">
        {{-- 驗證成功狀態 --}}
        @if (session('verification_success'))
            <div class="text-center">
                <div class="mb-6">
                    <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-3xl font-bold mb-4 text-center text-green-600">驗證成功！</h1>

                <div class="mb-6 p-4 bg-green-50 rounded-lg">
                    <p class="text-lg font-medium text-green-800 mb-2">
                        🎉 恭喜！您的 Email 已成功驗證
                    </p>
                    <p class="text-sm text-green-700">
                        您的帳號已啟用，現在可以登入後台開始使用 592Meal 平台
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <h3 class="font-semibold text-blue-900 mb-2">📧 後台登入資訊</h3>
                        <div class="text-left space-y-2">
                            <div class="flex items-center justify-between p-2 bg-white rounded border">
                                <span class="text-sm text-gray-600">登入 Email：</span>
                                <span class="font-medium">{{ session('verified_email') }}</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-white rounded border">
                                <span class="text-sm text-gray-600">後台網址：</span>
                                <span class="font-medium text-blue-600">{{ config('app.admin_url') }}</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ config('app.admin_url') }}/login"
                       class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg text-center transition duration-200">
                        立即前往後台登入
                    </a>

                    <p class="text-xs text-gray-500 text-center">
                        我們也已將後台登入連結發送至您的郵箱
                    </p>
                </div>
            </div>

        {{-- 一般驗證狀態 --}}
        @else
            {{-- Debug: 顯示 email 變數的值 --}}
            @if(isset($email))
                <div class="text-xs text-gray-500 mb-2">Debug: Email={{ $email }}</div>
            @else
                <div class="text-xs text-red-500 mb-2">Debug: Email variable not set</div>
            @endif

            <h1 class="text-2xl font-bold mb-6 text-center">驗證您的 Email</h1>

            <div class="mb-4 text-sm text-gray-600">
                感謝您的註冊！在開始之前，請點擊我們剛剛寄給您的 Email 中的連結，或輸入 6 位數驗證碼來驗證您的 Email 地址。如果您沒有收到郵件，我們很樂意重新寄送一封。
            </div>

            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            {{-- 驗證碼輸入表單 --}}
            <form wire:submit="verify" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           wire:model="email"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="請輸入您的 Email"
                           readonly>
                    @error('email')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                        6 位數驗證碼
                    </label>
                    <input type="text"
                           id="code"
                           name="code"
                           wire:model="code"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-lg font-mono"
                           placeholder="請輸入 6 位數驗證碼"
                           maxlength="6"
                           pattern="[0-9]{6}">
                    @error('code')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                    驗證 Email
                </button>
            </form>

            <div class="mt-6 border-t pt-6">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <p class="text-center text-sm text-gray-600">
                        沒有收到驗證信？
                        <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            點此重新發送
                        </button>
                    </p>
                </form>
            </div>
        @endif
    </div>
</div>