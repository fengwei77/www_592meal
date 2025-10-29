<?php

namespace App\Filament\Resources\Stores\Pages;

use App\Filament\Resources\Stores\StoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStore extends EditRecord
{
    protected static string $resource = StoreResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'full'; // 使用全寬
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 處理 store_slug_name 欄位
        if (isset($data['store_slug_name'])) {
            // 判斷是否為空
            if (empty(trim($data['store_slug_name']))) {
                // 如果為空，直接帶入預設格式：s + 6位補零ID
                $data['store_slug_name'] = 's' . str_pad($this->record->id, 6, '0', STR_PAD_LEFT);
            } else {
                // 清理使用者輸入的 slug
                $cleanedSlug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $data['store_slug_name']));

                // 確保唯一性
                $originalSlug = $cleanedSlug;
                $counter = 1;

                while (\App\Models\Store::where('store_slug_name', $cleanedSlug)
                                     ->where('id', '!=', $this->record->id)
                                     ->exists()) {
                    $cleanedSlug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $data['store_slug_name'] = $cleanedSlug;
            }
        }

        return $data;
    }
}
