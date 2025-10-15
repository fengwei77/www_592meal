<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? '592Meal - 系統狀態' }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="antialiased bg-gray-50 flex flex-col min-h-screen">
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <footer class="bg-white py-6 mt-auto border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <a href="{{ route('merchant.register') }}" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                店家註冊
            </a>
            <p class="mt-4 text-xs text-gray-400">&copy; {{ date('Y') }} 592Meal. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>