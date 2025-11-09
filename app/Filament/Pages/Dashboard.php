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

    /**
     * 配置儀表板網格列數
     * 設置響應式網格佈局以適應不同螢幕尺寸
     */
    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
            '2xl' => 4,
        ];
    }

    /**
     * 定義儀表板要顯示的 Widgets
     * 由於我們使用 discoverWidgets，所以不需要在這裡手動定義
     */
    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
