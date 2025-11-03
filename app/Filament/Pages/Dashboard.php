<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * 完全禁用权限检查
     */
    public static function canAccess(): bool
    {
        // 临时禁用所有权限检查 - 允许所有已登录用户访问
        return auth()->check();

        /* 原始逻辑（已禁用）
        return parent::canAccess();
        */
    }

    /**
     * 自定义标题
     */
    public function getTitle(): string
    {
        return '控制台';
    }

    /**
     * 自定义导航标签
     */
    public static function getNavigationLabel(): string
    {
        return '控制台';
    }
}
