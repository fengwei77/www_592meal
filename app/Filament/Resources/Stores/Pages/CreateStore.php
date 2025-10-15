<?php

namespace App\Filament\Resources\Stores\Pages;

use App\Filament\Resources\Stores\StoreResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['subdomain'] = $this->generateUniqueSubdomain($data['name']);

        return $data;
    }

    /**
     * 生成唯一的子域名
     */
    private function generateUniqueSubdomain(string $storeName): string
    {
        // 轉換為小寫並移除特殊字符
        $subdomain = strtolower($storeName);
        $subdomain = preg_replace('/[^a-z0-9]/', '', $subdomain);
        $subdomain = substr($subdomain, 0, 20); // 限制長度

        // 確保唯一性
        $originalSubdomain = $subdomain;
        $counter = 1;

        while (\App\Models\Store::where('subdomain', $subdomain)->exists()) {
            $subdomain = $originalSubdomain . $counter;
            $counter++;
        }

        return $subdomain;
    }
}
