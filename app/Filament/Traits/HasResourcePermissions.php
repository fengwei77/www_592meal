<?php

namespace App\Filament\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * HasResourcePermissions Trait
 *
 * 為 Filament Resources 提供統一的權限控制邏輯
 *
 * 使用方式：
 * 1. 在 Resource 中 use HasResourcePermissions
 * 2. 設定 protected static string $viewPermission = 'view_resource_name'
 * 3. Trait 會自動實作 canViewAny() 方法控制選單顯示
 *
 * 範例：
 * ```php
 * class MenuCategoryResource extends Resource
 * {
 *     use HasResourcePermissions;
 *
 *     protected static string $viewPermission = 'view_menu_categories';
 *     // ...
 * }
 * ```
 *
 * 進階用法 - 自訂權限檢查邏輯：
 * ```php
 * protected static function hasViewPermission(): bool
 * {
 *     return auth()->user()?->hasRole('super_admin')
 *         || auth()->user()?->can('view_custom_permission');
 * }
 * ```
 */
trait HasResourcePermissions
{
    /**
     * 控制是否在導航選單中顯示此 Resource
     *
     * 優先級：
     * 1. 如果有自訂 hasViewPermission() 方法，使用自訂邏輯
     * 2. 如果有設定 $viewPermission 屬性，檢查該權限
     * 3. Super Admin 永遠可見
     * 4. 預設：不可見
     *
     * @return bool
     */
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        // 未登入使用者無法存取
        if (!$user) {
            return false;
        }

        // Super Admin 可以看到所有選單
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // 如果 Resource 有自訂權限檢查方法，使用自訂邏輯
        if (method_exists(static::class, 'hasViewPermission')) {
            return static::hasViewPermission();
        }

        // 如果有設定 viewPermission 屬性，檢查該權限
        if (property_exists(static::class, 'viewPermission')) {
            return $user->can(static::$viewPermission);
        }

        // 預設：不顯示（安全優先）
        return false;
    }

    /**
     * 輔助方法：檢查使用者是否為 Super Admin
     *
     * @return bool
     */
    protected static function isSuperAdmin(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    /**
     * 輔助方法：檢查使用者是否為 Store Owner
     *
     * @return bool
     */
    protected static function isStoreOwner(): bool
    {
        return Auth::user()?->hasRole('store_owner') ?? false;
    }

    /**
     * 輔助方法：檢查使用者是否有指定權限
     *
     * @param string $permission
     * @return bool
     */
    protected static function hasPermission(string $permission): bool
    {
        return Auth::user()?->can($permission) ?? false;
    }
}
