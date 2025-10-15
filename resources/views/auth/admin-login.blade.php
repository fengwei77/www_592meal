<x-layouts.app>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-center text-gray-900">店家後台登入</h2>
            
            {{-- Livewire Admin Login Form --}}
            @livewire('auth.admin-login-form')

        </div>
    </div>
</x-layouts.app>
