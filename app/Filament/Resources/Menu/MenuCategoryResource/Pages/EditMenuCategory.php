<?php

namespace App\Filament\Resources\Menu\MenuCategoryResource\Pages;

use App\Filament\Resources\Menu\MenuCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMenuCategory extends EditRecord
{
    protected static string $resource = MenuCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * 確保用戶只能編輯有權限的記錄
     */
    protected function checkEditPermission(): void
    {
        $user = auth()->user();

        // 如果不是超級管理員，檢查是否可以編輯此特定記錄
        if (!$user->hasRole('super_admin') && $user->hasRole('store_owner')) {
            $record = $this->getRecord();
            $userStores = \App\Models\Store::where('user_id', $user->id)->pluck('id');

            if (!in_array($record->store_id, $userStores->toArray())) {
                abort(403, '您沒有權限編輯此菜單分類');
            }
        }
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->checkEditPermission();
    }
}
