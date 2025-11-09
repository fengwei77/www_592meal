<?php

namespace App\Filament\Widgets;

use App\Services\SubscriptionService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SubscriptionStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.subscription-stats-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    /**
     * 只有 store_owner 可以查看此 Widget
     */
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('store_owner');
    }

    /**
     * 獲取訂閱統計數據
     */
    protected function getViewData(): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->getDefaultStats();
            }

            $subscriptionService = app(SubscriptionService::class);
            $stats = $subscriptionService->getUserSubscriptionStats($user);

            // 取得待付款訂單數量
            $pendingOrders = $subscriptionService->getPendingOrders($user);
            $pendingCount = $pendingOrders->count();

            $totalOrders = (int) ($stats['total_orders'] ?? 0);
            $paidOrders = (int) ($stats['paid_orders'] ?? 0);
            $completionRate = $this->calculateCompletionRate($totalOrders, $paidOrders);

            return [
                'total_orders' => $totalOrders,
                'paid_orders' => $paidOrders,
                'pending_orders' => $pendingCount,
                'total_amount' => (int) ($stats['total_amount'] ?? 0),
                'formatted_total_amount' => $this->formatAmount((int) ($stats['total_amount'] ?? 0)),
                'completion_rate' => $completionRate,
                'pending_orders_color' => $this->getPendingOrdersColor($pendingCount),
                'completion_rate_color' => $this->getCompletionRateColor($completionRate),
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
            'paid_orders' => 0,
            'pending_orders' => 0,
            'total_amount' => 0,
            'formatted_total_amount' => 'NT$ 0',
            'completion_rate' => 0,
            'pending_orders_color' => 'success',
            'completion_rate_color' => 'danger',
        ];
    }

    /**
     * 計算完成率
     */
    private function calculateCompletionRate(int $totalOrders, int $paidOrders): float
    {
        if ($totalOrders > 0) {
            return round(($paidOrders / $totalOrders) * 100, 1);
        }

        return 0;
    }

    /**
     * 格式化金額
     */
    private function formatAmount(int $amount): string
    {
        return 'NT$ ' . number_format($amount);
    }

    /**
     * 獲取待付款訂單的狀態顏色
     */
    private function getPendingOrdersColor(int $pendingCount): string
    {
        if ($pendingCount > 5) {
            return 'danger';
        } elseif ($pendingCount > 2) {
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