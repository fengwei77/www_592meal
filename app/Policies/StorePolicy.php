<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin 可以查看所有店家
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 可以查看自己的店家
        return $user->hasRole('Store Owner');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Store $store): bool
    {
        // Super Admin 可以查看所有店家
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 只能查看自己的店家
        return $store->isOwnedBy($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin 可以建立店家
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 可以建立店家（限制數量）
        if ($user->hasRole('Store Owner')) {
            $storeCount = Store::where('user_id', $user->id)->count();
            return $storeCount < 3; // 限制最多 3 個店家
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Store $store): bool
    {
        // Super Admin 可以編輯所有店家
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 只能編輯自己的店家
        return $store->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Store $store): bool
    {
        // Super Admin 可以刪除所有店家
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 只能刪除自己的店家
        return $store->isOwnedBy($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Store $store): bool
    {
        // Super Admin 可以恢復所有店家
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 只能恢復自己的店家
        return $store->isOwnedBy($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Store $store): bool
    {
        // 只有 Super Admin 可以永久刪除店家
        return $user->hasRole('Super Admin');
    }

    /**
     * 確認店家是否屬於用戶
     */
    private function ownsStore(User $user, Store $store): bool
    {
        return $store->user_id === $user->id;
    }
}