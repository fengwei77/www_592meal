<?php

namespace App\Filament\Widgets;

use App\Models\SubscriptionOrder;
use Filament\Widgets\Widget;

class OrderStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.order-stats-widget';

    protected static ?int $sort = 12;

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
     * 獲取訂單統計數據
     */
    protected function getViewData(): array
    {
        try {
            // 總訂單數量
            $totalOrders = SubscriptionOrder::count();

            // 已完成訂單（已付款）
            $completedOrders = SubscriptionOrder::where('status', 'paid')->count();

            // 未完成訂單（待付款）
            $pendingOrders = SubscriptionOrder::where('status', 'pending')->count();

            // 失敗訂單（已取消或付款失敗）
            $failedOrders = SubscriptionOrder::whereIn('status', ['cancelled', 'failed'])->count();

            // 過期訂單
            $expiredOrders = SubscriptionOrder::where('status', 'expired')->count();

            // 今日訂單
            $todayOrders = SubscriptionOrder::whereDate('created_at', today())->count();

            // 今日已完成訂單
            $todayCompletedOrders = SubscriptionOrder::where('status', 'paid')
                ->whereDate('created_at', today())
                ->count();

            // 計算完成率
            $completionRate = $totalOrders > 0
                ? round(($completedOrders / $totalOrders) * 100, 1)
                : 0;

            // 今日完成率
            $todayCompletionRate = $todayOrders > 0
                ? round(($todayCompletedOrders / $todayOrders) * 100, 1)
                : 0;

            return [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'pending_orders' => $pendingOrders,
                'failed_orders' => $failedOrders,
                'expired_orders' => $expiredOrders,
                'today_orders' => $todayOrders,
                'today_completed_orders' => $todayCompletedOrders,
                'completion_rate' => $completionRate,
                'today_completion_rate' => $todayCompletionRate,
                'pending_color' => $this->getPendingOrdersColor($pendingOrders),
                'completion_color' => $this->getCompletionRateColor($completionRate),
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
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'failed_orders' => 0,
            'expired_orders' => 0,
            'today_orders' => 0,
            'today_completed_orders' => 0,
            'completion_rate' => 0,
            'today_completion_rate' => 0,
            'pending_color' => 'success',
            'completion_color' => 'danger',
        ];
    }

    /**
     * 獲取待付款訂單的狀態顏色
     */
    private function getPendingOrdersColor(int $pendingCount): string
    {
        if ($pendingCount > 20) {
            return 'danger';
        } elseif ($pendingCount > 10) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * 獲取完成率的顏色
     */
    private function getCompletionRateColor(float $rate): string
    {
        if ($rate >= 80) {
            return 'success';
        } elseif ($rate >= 60) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}