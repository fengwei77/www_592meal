@php
use App\Helpers\StorePhotoHelper;
@endphp

{{-- 商家照片顯示組件 --}}
<div class="store-photos-container">
    {{-- 載入中狀態 --}}
    <div x-data="{ photosLoaded: false }"
         x-init="photosLoaded = true"
         x-show="!photosLoaded"
         class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">載入中...</p>
    </div>

    {{-- 照片內容 --}}
    <div x-data="{ photosLoaded: false }"
         x-init="photosLoaded = true"
         x-show="photosLoaded"
         x-transition
         class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        @php
            $photos = StorePhotoHelper::getStorePhotos($store);
        @endphp

        @if(empty($photos))
            {{-- 沒有照片時顯示預設內容 --}}
            <div class="col-span-full text-center py-8 bg-gray-50 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-2 text-gray-600">尚無商家照片</p>
            </div>
        @else
            @foreach($photos as $photo)
                <div class="relative group overflow-hidden rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <a href="{{ $photo['original_url'] }}"
                       target="_blank"
                       class="block w-full h-48 bg-gray-100">
                        @if($photo['exists'])
                            <img src="{{ $photo['thumb_url'] }}"
                                 alt="商家照片"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy"
                                 onerror="this.src='{{ asset('images/default-store-cover.jpg') }}'; this.onerror=null;">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                        @endif
                    </a>

                    {{-- 照片資訊 --}}
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <p class="text-white text-xs truncate">{{ $photo['file_name'] }}</p>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- 照片數量統計 --}}
    @if(!empty($photos))
        <div class="mt-4 text-center text-sm text-gray-600">
            共 {{ count($photos) }} 張照片
        </div>
    @endif
</div>

{{-- 樣式定義 --}}
<style>
.store-photos-container {
    max-width: 100%;
}

.store-photos-container img {
    transition: transform 0.3s ease;
}

.store-photos-container .group:hover img {
    transform: scale(1.05);
}
</style>