<?php

namespace App\Filament\Widgets;

use App\Services\SystemStatisticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * System Overview Widget
 *
 * 在系統管理頁面頂部顯示關鍵統計數據
 */
class SystemOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $statsService = new SystemStatisticsService();
        $stats = $statsService->getOverallStats();
        $todayStats = $statsService->getTodayStats();

        return [
            Stat::make('總註冊人數', number_format($stats['total_users']))
                ->description("今日新增: {$todayStats['new_registrations']}")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('活躍訂閱', number_format($stats['subscribed_users']))
                ->description('訂閱用戶')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),

            Stat::make('總收入', 'NT$ ' . number_format($stats['total_revenue']))
                ->description('累計收入')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('總訂單數', number_format($stats['total_orders']))
                ->description('系統訂單')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),
        ];
    }

    /**
     * Widget 權限檢查 - 僅限 Super Admin
     */
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user &&
               $user->hasRole('super_admin') &&
               $user->hasPermissionTo('view_system_statistics');
    }
}