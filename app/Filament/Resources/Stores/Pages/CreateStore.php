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

        // 處理 store_slug_name 欄位
        if (isset($data['store_slug_name'])) {
            // 判斷是否為空
            if (empty(trim($data['store_slug_name']))) {
                // 如果為空，先設為 null，保存後再處理
                $data['store_slug_name'] = null;
            } else {
                // 清理使用者輸入的 slug
                $cleanedSlug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $data['store_slug_name']));

                // 確保唯一性
                $originalSlug = $cleanedSlug;
                $counter = 1;

                while (\App\Models\Store::where('store_slug_name', $cleanedSlug)->exists()) {
                    $cleanedSlug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $data['store_slug_name'] = $cleanedSlug;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        parent::afterCreate();

        // 如果 store_slug_name 為空，生成預設格式
        if (empty($this->record->store_slug_name)) {
            $this->record->update([
                'store_slug_name' => 's' . str_pad($this->record->id, 6, '0', STR_PAD_LEFT)
            ]);
        }
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
