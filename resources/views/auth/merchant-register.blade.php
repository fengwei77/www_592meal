<x-layouts.app>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-center text-gray-900">成為店家老闆</h2>

            {{-- Livewire Merchant Registration Form --}}
            @livewire('auth.merchant-registration-form')

            <div class="text-center text-sm">
                <a href="{{ route('verification.resend') }}" class="text-indigo-600 hover:text-indigo-500">
                    沒有收到驗證信？點此重新發送
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
