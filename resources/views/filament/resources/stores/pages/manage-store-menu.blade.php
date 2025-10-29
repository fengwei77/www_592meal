<x-filament-panels::page>
    <div class="space-y-6">
        <!-- 店家資訊 -->
        <x-filament::section>
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                    <img src="{{ $record->logo_url }}" alt="{{ $record->name }}" class="w-full h-full object-cover"
                         onerror="this.src='{{ asset('images/default-store.svg') }}'">
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-xl font-semibold text-gray-900 truncate">{{ $record->name }}</h3>
                    <p class="text-gray-600">{{ $record->store_type_label }} • {{ $record->service_mode_label }}</p>
                    <p class="text-sm text-gray-500 truncate">{{ $record->address }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>