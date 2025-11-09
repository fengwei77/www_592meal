<div class="fi-wi-widget">
    <div class="fi-section">
        <div class="fi-section-content-ctn">
            <div class="fi-section-content">
                <div class="flex items-start space-x-3">
                    <div class="fi-status-icon mt-1">
                        <svg class="fi-icon fi-size-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">到期日期</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $formatted_expiry_date }}</p>
                        <p class="text-xs text-{{ $status_color }} mt-1">{{ $status_text }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>