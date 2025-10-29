<?php

namespace App\Filament\Resources\Menu\MenuCategoryResource\Pages;

use App\Filament\Resources\Menu\MenuCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateMenuCategory extends CreateRecord
{
    protected static string $resource = MenuCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 自動設定 store_id
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('使用者未登入');
        }

        $roles = $user->roles->pluck('name')->toArray();
        $isSuperAdmin = $user->hasRole('super_admin');
        $isStoreOwner = $user->hasRole('store_owner');

        // Super Admin 需要有 store_id，應該在表單中選擇
        if ($isSuperAdmin) {
            // Super Admin 必須選擇店家
            if (empty($data['store_id'])) {
                throw new \Exception('Super Admin 必須選擇店家');
            }
        } else {
            // Store Owner 或沒有角色的使用者，都嘗試自動使用自己的店家
            $store = \App\Models\Store::where('user_id', $user->id)->first();
            if (!$store) {
                // 如果沒有店家，且使用者也沒有角色，給出提示訊息
                if (empty($roles)) {
                    throw new \Exception('您的帳號尚未設定角色和店家，請聯繫管理員。使用者 ID: ' . $user->id);
                }
                throw new \Exception('找不到您的店家資料，請聯繫管理員。使用者 ID: ' . $user->id);
            }
            $data['store_id'] = $store->id;
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
