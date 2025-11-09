<?php

namespace App\Filament\Widgets;

use App\Models\SubscriptionOrder;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class RevenueStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.revenue-stats-widget';

    protected static ?int $sort = 11;

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
     * 獲取收入統計數據
     */
    protected function getViewData(): array
    {
        try {
            // 總收入金額
            $totalRevenue = SubscriptionOrder::where('status', 'paid')->sum('total_amount');

            // 本月收入
            $monthlyRevenue = SubscriptionOrder::where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');

            // 今日收入
            $dailyRevenue = SubscriptionOrder::where('status', 'paid')
                ->whereDate('created_at', today())
                ->sum('total_amount');

            // 平均訂單金額
            $paidOrdersCount = SubscriptionOrder::where('status', 'paid')->count();
            $averageOrderValue = $paidOrdersCount > 0
                ? round($totalRevenue / $paidOrdersCount, 2)
                : 0;

            // 預計下月收入（基於目前活躍訂閱）
            $activeSubscriptions = DB::table('users')
                ->whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>', now())
                ->count();

            $monthlyPrice = (int) config('ecpay.monthly_price', 50);
            $projectedMonthlyRevenue = $activeSubscriptions * $monthlyPrice;

            // 本月訂單數
            $monthlyOrders = SubscriptionOrder::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // 今日訂單數
            $dailyOrders = SubscriptionOrder::whereDate('created_at', today())->count();

            return [
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'daily_revenue' => $dailyRevenue,
                'average_order_value' => $averageOrderValue,
                'projected_monthly_revenue' => $projectedMonthlyRevenue,
                'monthly_orders' => $monthlyOrders,
                'daily_orders' => $dailyOrders,
                'formatted_total_revenue' => $this->formatAmount($totalRevenue),
                'formatted_monthly_revenue' => $this->formatAmount($monthlyRevenue),
                'formatted_daily_revenue' => $this->formatAmount($dailyRevenue),
                'formatted_average_order_value' => $this->formatAmount($averageOrderValue),
                'formatted_projected_monthly_revenue' => $this->formatAmount($projectedMonthlyRevenue),
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
        $zero = 'NT$ 0';
        return [
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'daily_revenue' => 0,
            'average_order_value' => 0,
            'projected_monthly_revenue' => 0,
            'monthly_orders' => 0,
            'daily_orders' => 0,
            'formatted_total_revenue' => $zero,
            'formatted_monthly_revenue' => $zero,
            'formatted_daily_revenue' => $zero,
            'formatted_average_order_value' => $zero,
            'formatted_projected_monthly_revenue' => $zero,
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