<x-layouts.app>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900">重新發送驗證信</h2>
                <p class="mt-2 text-sm text-gray-600">
                    沒有收到驗證信嗎？輸入您的信箱，我們將重新發送給您。
                </p>
            </div>

            {{-- Livewire Resend Verification Email Form --}}
            @livewire('auth.resend-verification-email')

            <div class="text-center text-sm">
                <a href="/merchant-register" class="text-indigo-600 hover:text-indigo-500">
                    還沒有帳號？立即註冊
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
