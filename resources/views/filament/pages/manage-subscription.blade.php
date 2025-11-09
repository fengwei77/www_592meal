<x-filament-panels::page>
    <form wire:submit="createOrder">
        {{ $this->form }}

        <div class="flex justify-end mt-6">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <x-filament::loading-indicator wire:loading wire:target="createOrder" class="h-5 w-5" />
                <span wire:loading.remove wire:target="createOrder">
                    建立訂單
                </span>
                <span wire:loading wire:target="createOrder">
                    處理中...
                </span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>