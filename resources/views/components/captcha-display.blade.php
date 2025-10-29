@props(['code'])

<div class="space-y-3">
    <div class="flex items-center gap-3">
        {{-- 驗證碼顯示區塊 --}}
        <div class="relative inline-block">
            <div
                class="flex items-center justify-center px-6 py-3 bg-gray-100 border-2 border-gray-300 rounded-lg font-mono text-2xl font-bold tracking-widest select-none"
                style="min-width: 150px; letter-spacing: 0.5em;"
            >
                {{ $code }}
            </div>
        </div>

        {{-- 重新整理按鈕 --}}
        <button
            type="button"
            wire:click="refreshCaptcha"
            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            wire:loading.attr="disabled"
        >
            {{-- 預設圖示 --}}
            <svg
                wire:loading.remove
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>

            {{-- 載入中動畫 --}}
            <svg
                wire:loading
                class="w-5 h-5 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            {{-- 按鈕文字 --}}
            <span class="ml-2" wire:loading.remove>重新整理驗證碼</span>
            <span class="ml-2" wire:loading>更新中...</span>
        </button>
    </div>

    {{-- 輔助說明 --}}
    <p class="text-sm text-gray-500">
        點擊右側按鈕可重新產生驗證碼
    </p>
</div>
