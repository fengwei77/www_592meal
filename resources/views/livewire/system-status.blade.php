<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">系統狀態監控</h1>
            <p class="text-lg text-gray-600">592Meal 環境驗證</p>
            <button
                wire:click="refresh"
                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                重新檢查
            </button>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($services as $key => $service)
                <div class="bg-white overflow-hidden shadow-lg rounded-lg border-2 {{ $service['status'] === 'OK' ? 'border-green-500' : 'border-red-500' }}">
                    <!-- Card Header -->
                    <div class="px-6 py-4 {{ $service['status'] === 'OK' ? 'bg-green-50' : 'bg-red-50' }}">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $this->getServiceName($key) }}
                            </h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $service['status'] === 'OK' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                                {{ $service['status'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="px-6 py-4">
                        <p class="text-gray-700 mb-4">{{ $service['message'] }}</p>

                        @if(!empty($service['details']))
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-600 mb-2">詳細資訊：</h4>
                                <dl class="space-y-1">
                                    @foreach($service['details'] as $detailKey => $detailValue)
                                        <div class="flex flex-col">
                                            <dt class="text-xs font-medium text-gray-500">{{ $this->formatDetailKey($detailKey) }}:</dt>
                                            <dd class="text-sm text-gray-900 font-mono break-all">
                                                @if(is_array($detailValue))
                                                    <pre class="text-xs bg-gray-100 p-2 rounded mt-1">{{ json_encode($detailValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                @else
                                                    {{ $detailValue }}
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                    </div>

                    <!-- Card Footer - Icon -->
                    <div class="px-6 py-3 {{ $service['status'] === 'OK' ? 'bg-green-50' : 'bg-red-50' }}">
                        <div class="flex items-center justify-center">
                            @if($service['status'] === 'OK')
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Summary -->
        <div class="mt-12 bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">環境檢查摘要</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900">{{ count($services) }}</div>
                    <div class="text-sm text-gray-600">總服務數</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-500">{{ collect($services)->where('status', 'OK')->count() }}</div>
                    <div class="text-sm text-gray-600">正常運作</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-red-500">{{ collect($services)->where('status', 'NG')->count() }}</div>
                    <div class="text-sm text-gray-600">異常或未設定</div>
                </div>
            </div>

            @if(collect($services)->where('status', 'NG')->count() === 0)
                <div class="mt-6 p-4 bg-green-50 rounded-lg border-2 border-green-500">
                    <div class="flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-lg font-semibold text-green-700">所有服務運作正常！可以開始進行 Story 1.1 開發</p>
                    </div>
                </div>
            @else
                <div class="mt-6 p-4 bg-yellow-50 rounded-lg border-2 border-yellow-500">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-lg font-semibold text-yellow-700">部分服務異常或未設定，請先修復後再開始開發</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @script
    <script>
        // Helper methods for Livewire component
        Livewire.on('refreshComplete', () => {
            console.log('Status refresh complete');
        });
    </script>
    @endscript
</div>
