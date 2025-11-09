<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\SubscriptionOrder;
use Filament\Widgets\Widget;

class DailyStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.daily-stats-widget';

    protected static ?int $sort = 13;

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
     * 獲取日統計數據
     */
    protected function getViewData(): array
    {
        try {
            $today = now()->today();
            $yesterday = now()->subDay()->today();

            // 今日註冊數
            $todayRegistrations = User::whereDate('created_at', $today)->count();
            $yesterdayRegistrations = User::whereDate('created_at', $yesterday)->count();
            $registrationGrowthRate = $yesterdayRegistrations > 0
                ? round((($todayRegistrations - $yesterdayRegistrations) / $yesterdayRegistrations) * 100, 1)
                : ($todayRegistrations > 0 ? 100 : 0);

            // 今日訂單數
            $todayOrders = SubscriptionOrder::whereDate('created_at', $today)->count();
            $yesterdayOrders = SubscriptionOrder::whereDate('created_at', $yesterday)->count();
            $orderGrowthRate = $yesterdayOrders > 0
                ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
                : ($todayOrders > 0 ? 100 : 0);

            // 今日收入
            $todayRevenue = SubscriptionOrder::where('status', 'paid')
                ->whereDate('created_at', $today)
                ->sum('total_amount');
            $yesterdayRevenue = SubscriptionOrder::where('status', 'paid')
                ->whereDate('created_at', $yesterday)
                ->sum('total_amount');
            $revenueGrowthRate = $yesterdayRevenue > 0
                ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
                : ($todayRevenue > 0 ? 100 : 0);

            // 本週統計
            $weekStart = now()->startOfWeek();
            $weekRegistrations = User::whereDate('created_at', '>=', $weekStart)
                ->whereDate('created_at', '<=', $today)
                ->count();
            $weekOrders = SubscriptionOrder::whereDate('created_at', '>=', $weekStart)
                ->whereDate('created_at', '<=', $today)
                ->count();
            $weekRevenue = SubscriptionOrder::where('status', 'paid')
                ->whereDate('created_at', '>=', $weekStart)
                ->whereDate('created_at', '<=', $today)
                ->sum('total_amount');

            // 本月統計
            $monthStart = now()->startOfMonth();
            $monthRegistrations = User::whereDate('created_at', '>=', $monthStart)
                ->whereDate('created_at', '<=', $today)
                ->count();
            $monthOrders = SubscriptionOrder::whereDate('created_at', '>=', $monthStart)
                ->whereDate('created_at', '<=', $today)
                ->count();
            $monthRevenue = SubscriptionOrder::where('status', 'paid')
                ->whereDate('created_at', '>=', $monthStart)
                ->whereDate('created_at', '<=', $today)
                ->sum('total_amount');

            return [
                'today_registrations' => $todayRegistrations,
                'yesterday_registrations' => $yesterdayRegistrations,
                'registration_growth_rate' => $registrationGrowthRate,
                'today_orders' => $todayOrders,
                'yesterday_orders' => $yesterdayOrders,
                'order_growth_rate' => $orderGrowthRate,
                'today_revenue' => $todayRevenue,
                'yesterday_revenue' => $yesterdayRevenue,
                'revenue_growth_rate' => $revenueGrowthRate,
                'week_registrations' => $weekRegistrations,
                'week_orders' => $weekOrders,
                'week_revenue' => $weekRevenue,
                'month_registrations' => $monthRegistrations,
                'month_orders' => $monthOrders,
                'month_revenue' => $monthRevenue,
                'formatted_today_revenue' => $this->formatAmount($todayRevenue),
                'formatted_week_revenue' => $this->formatAmount($weekRevenue),
                'formatted_month_revenue' => $this->formatAmount($monthRevenue),
                'registration_trend' => $registrationGrowthRate >= 0 ? 'up' : 'down',
                'order_trend' => $orderGrowthRate >= 0 ? 'up' : 'down',
                'revenue_trend' => $revenueGrowthRate >= 0 ? 'up' : 'down',
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
            'today_registrations' => 0,
            'yesterday_registrations' => 0,
            'registration_growth_rate' => 0,
            'today_orders' => 0,
            'yesterday_orders' => 0,
            'order_growth_rate' => 0,
            'today_revenue' => 0,
            'yesterday_revenue' => 0,
            'revenue_growth_rate' => 0,
            'week_registrations' => 0,
            'week_orders' => 0,
            'week_revenue' => 0,
            'month_registrations' => 0,
            'month_orders' => 0,
            'month_revenue' => 0,
            'formatted_today_revenue' => 'NT$ 0',
            'formatted_week_revenue' => 'NT$ 0',
            'formatted_month_revenue' => 'NT$ 0',
            'registration_trend' => 'neutral',
            'order_trend' => 'neutral',
            'revenue_trend' => 'neutral',
        ];
    }

    /**
     * 格式化金額
     */
    private function formatAmount(int $amount): string
    {
        return 'NT$ ' . number_format($amount);
    }
}