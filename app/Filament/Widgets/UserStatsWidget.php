<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class UserStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.user-stats-widget';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 2;

    /**
     * 只有 super_admin 可以查看此 Widget
     */
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    /**
     * 獲取用戶統計數據
     */
    protected function getViewData(): array
    {
        try {
            // 總註冊人數
            $totalUsers = User::count();

            // 有訂閱人數（包含付費訂閱和試用期）
            $usersWithSubscription = User::where(function ($query) {
                $query->whereHasActiveSubscription()
                      ->orWhere('isInTrialPeriod', true);
            })->count();

            // 試用期人數
            $trialUsers = User::where('is_trial_used', true)
                             ->where('trial_ends_at', '>', now())
                             ->count();

            // 付費訂閱人數
            $paidSubscribers = User::whereHasActiveSubscription()->count();

            // 訂閱轉換率
            $conversionRate = $trialUsers > 0
                ? round(($paidSubscribers / ($paidSubscribers + $trialUsers)) * 100, 1)
                : 0;

            // 今日註冊數
            $todayRegistrations = User::whereDate('created_at', today())->count();

            return [
                'total_users' => $totalUsers,
                'paid_subscribers' => $paidSubscribers,
                'trial_users' => $trialUsers,
                'total_with_subscription' => $usersWithSubscription,
                'conversion_rate' => $conversionRate,
                'today_registrations' => $todayRegistrations,
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
            'total_users' => 0,
            'paid_subscribers' => 0,
            'trial_users' => 0,
            'total_with_subscription' => 0,
            'conversion_rate' => 0,
            'today_registrations' => 0,
        ];
    }
}