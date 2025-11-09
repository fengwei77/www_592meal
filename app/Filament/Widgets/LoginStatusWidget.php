<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

class LoginStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.login-status-widget';

    protected static ?int $sort = 14;

    protected int | string | array $columnSpan = 1;

    /**
     * 只有 super_admin 可以查看此 Widget
     */
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    /**
     * 獲取登入狀態數據
     */
    protected function getViewData(): array
    {
        try {
            // 活躍用戶（最近7天有登入的用戶）
            $activeUsers = User::whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->subDays(7))
                ->count();

            // 今日活躍用戶
            $todayActiveUsers = User::whereNotNull('last_login_at')
                ->whereDate('last_login_at', today())
                ->count();

            // 本週活躍用戶
            $weekActiveUsers = User::whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->startOfWeek())
                ->count();

            // 本月活躍用戶
            $monthActiveUsers = User::whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->startOfMonth())
                ->count();

            // 總用戶數
            $totalUsers = User::count();

            // 活躍率計算
            $dailyActiveRate = $totalUsers > 0 ? round(($todayActiveUsers / $totalUsers) * 100, 1) : 0;
            $weeklyActiveRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;
            $monthlyActiveRate = $totalUsers > 0 ? round(($monthActiveUsers / $totalUsers) * 100, 1) : 0;

            // 最近登入的用戶（取前5名）
            $recentLogins = User::whereNotNull('last_login_at')
                ->orderBy('last_login_at', 'desc')
                ->limit(5)
                ->get(['name', 'email', 'last_login_at']);

            return [
                'active_users' => $activeUsers,
                'today_active_users' => $todayActiveUsers,
                'week_active_users' => $weekActiveUsers,
                'month_active_users' => $monthActiveUsers,
                'total_users' => $totalUsers,
                'daily_active_rate' => $dailyActiveRate,
                'weekly_active_rate' => $weeklyActiveRate,
                'monthly_active_rate' => $monthlyActiveRate,
                'recent_logins' => $recentLogins,
            ];
        } catch (\Exception $e) {
            return $this->getDefaultStats();
        }
    }

    /**
     * 獲取默認統計資料
     */
    private function getDefaultStats(): array
    {
        return [
            'active_users' => 0,
            'today_active_users' => 0,
            'week_active_users' => 0,
            'month_active_users' => 0,
            'total_users' => 0,
            'daily_active_rate' => 0,
            'weekly_active_rate' => 0,
            'monthly_active_rate' => 0,
            'recent_logins' => collect([]),
        ];
    }

    /**
     * 格式化日期時間
     */
    private function formatDateTime($dateTime): string
    {
        if (!$dateTime) return '未知';
        return $dateTime->format('m/d H:i');
    }
}