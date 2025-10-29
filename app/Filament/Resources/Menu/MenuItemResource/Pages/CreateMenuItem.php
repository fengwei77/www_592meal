<?php

namespace App\Filament\Resources\Menu\MenuItemResource\Pages;

use App\Filament\Resources\Menu\MenuItemResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 自動設定 store_id
        $user = Auth::user();

        // Super Admin 需要有 store_id，應該在表單中選擇
        if ($user && $user->hasRole('super_admin')) {
            // Super Admin 必須選擇店家
            if (empty($data['store_id'])) {
                throw new \Exception('Super Admin 必須選擇店家');
            }
        } elseif ($user && $user->hasRole('store_owner')) {
            // Store Owner 自動使用自己的店家
            $store = \App\Models\Store::where('user_id', $user->id)->first();
            if (!$store) {
                throw new \Exception('找不到您的店家資料，請聯繫管理員');
            }
            $data['store_id'] = $store->id;
        } else {
            throw new \Exception('無權建立餐點');
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // 確保 store_id 存在
        $user = Auth::user();

        if (empty($data['store_id'])) {
            if ($user && $user->hasRole('super_admin')) {
                throw new \Exception('Super Admin 必須選擇店家');
            } elseif ($user && $user->hasRole('store_owner')) {
                $store = \App\Models\Store::where('user_id', $user->id)->first();
                if (!$store) {
                    throw new \Exception('找不到您的店家資料，請聯繫管理員');
                }
                $data['store_id'] = $store->id;
            }
        }

        return parent::handleRecordCreation($data);
    }
}
