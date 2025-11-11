<?php

namespace App\Filament\Widgets;

use App\Models\SubscriptionOrder;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OrderManagementWidget extends Widget
{
    protected string $view = 'filament.widgets.order-management-widget';

    protected static ?int $sort = 15;

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
     * 獲取訂單管理數據
     */
    protected function getViewData(): array
    {
        try {
            // 最新訂單（最近10筆）
            $recentOrders = SubscriptionOrder::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // 有問題的訂單（付款失敗、長時間待付款等）
            $problematicOrders = SubscriptionOrder::with('user')
                ->where(function ($query) {
                    $query->whereIn('status', ['failed', 'cancelled'])
                          ->orWhere(function ($q) {
                              $q->where('status', 'pending')
                                ->where('created_at', '<', now()->subHours(24));
                          });
                })
                ->orderBy('created_at', 'desc')
                ->limit(0)
                ->get();

            // 統計數據
            $pendingCount = SubscriptionOrder::where('status', 'pending')->count();
            $failedCount = SubscriptionOrder::whereIn('status', ['failed', 'cancelled'])->count();
            $expiredCount = SubscriptionOrder::where('status', 'expired')->count();

            // 今日有問題的訂單數
            $todayProblematicOrders = SubscriptionOrder::whereDate('created_at', today())
                ->where(function ($query) {
                    $query->whereIn('status', ['failed', 'cancelled'])
                          ->orWhere('status', 'pending');
                })
                ->count();

            return [
                'recent_orders' => $recentOrders,
                'problematic_orders' => $problematicOrders,
                'pending_count' => $pendingCount,
                'failed_count' => $failedCount,
                'expired_count' => $expiredCount,
                'today_problematic_count' => $todayProblematicOrders,
                'current_user' => Auth::user(),
            ];
        } catch (\Exception $e) {
            return [
                'recent_orders' => collect([]),
                'problematic_orders' => collect([]),
                'pending_count' => 0,
                'failed_count' => 0,
                'expired_count' => 0,
                'today_problematic_count' => 0,
                'current_user' => Auth::user(),
            ];
        }
    }

    /**
     * 格式化金額
     */
    public function formatAmount(int $amount): string
    {
        return 'NT$ ' . number_format($amount);
    }

    /**
     * 格式化日期時間
     */
    public function formatDateTime($dateTime): string
    {
        if (!$dateTime) return '未知';
        return $dateTime->format('m/d H:i');
    }

    /**
     * 獲取狀態顏色
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'cancelled' => 'danger',
            'expired' => 'gray',
            default => 'gray',
        };
    }

    /**
     * 獲取狀態文字
     */
    public function getStatusText(string $status): string
    {
        return match ($status) {
            'paid' => '已付款',
            'pending' => '待付款',
            'failed' => '付款失敗',
            'cancelled' => '已取消',
            'expired' => '已過期',
            default => '未知',
        };
    }
}