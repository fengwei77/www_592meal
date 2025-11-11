<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\SubscriptionPaymentLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * System Statistics Service
 *
 * 提供系統管理所需的各種統計數據
 * 僅供 Super Admin 使用
 */
class SystemStatisticsService
{
    /**
     * 獲取總體統計數據
     */
    public function getOverallStats(): array
    {
        return [
            'total_users' => User::count(),
            'subscribed_users' => User::whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>', Carbon::now())
                ->count(),
            'total_revenue' => SubscriptionPaymentLog::where('rtn_code', 1)->sum('trade_amt'),
            'total_orders' => Order::count(),
        ];
    }

    /**
     * 獲取訂單狀態統計
     */
    public function getOrderStats(): array
    {
        return [
            'completed' => Order::where('status', 'completed')->count(),
            'pending' => Order::where('status', 'pending')->count(),
            'failed' => Order::where('status', 'failed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];
    }

    /**
     * 獲取今日統計數據
     */
    public function getTodayStats(): array
    {
        $today = Carbon::today();

        return [
            'new_registrations' => User::whereDate('created_at', $today)->count(),
            'website_logins' => DB::table('website_login_logs')
                ->whereDate('created_at', $today)
                ->where('success', true)
                ->count(),
            'admin_logins_success' => DB::table('admin_login_logs')
                ->whereDate('created_at', $today)
                ->where('success', true)
                ->count(),
            'admin_logins_failed' => DB::table('admin_login_logs')
                ->whereDate('created_at', $today)
                ->where('success', false)
                ->count(),
        ];
    }

    /**
     * 獲取每日統計（最近7天）
     */
    public function getDailyStats(int $days = 7): array
    {
        $stats = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $stats[] = [
                'date' => $date->format('Y-m-d'),
                'new_registrations' => User::whereDate('created_at', $date)->count(),
                'website_logins' => DB::table('website_login_logs')
                    ->whereDate('created_at', $date)
                    ->where('success', true)
                    ->count(),
                'admin_logins_success' => DB::table('admin_login_logs')
                    ->whereDate('created_at', $date)
                    ->where('success', true)
                    ->count(),
                'admin_logins_failed' => DB::table('admin_login_logs')
                    ->whereDate('created_at', $date)
                    ->where('success', false)
                    ->count(),
            ];
        }

        return $stats;
    }

    /**
     * 獲取月度統計數據
     */
    public function getMonthlyStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'new_registrations' => User::where('created_at', '>=', $startOfMonth)->count(),
            'new_subscriptions' => SubscriptionPaymentLog::where('rtn_code', 1)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'monthly_revenue' => SubscriptionPaymentLog::where('rtn_code', 1)
                ->where('created_at', '>=', $startOfMonth)
                ->sum('trade_amt'),
        ];
    }

    /**
     * 獲取最近30天註冊趨勢
     */
    public function getRegistrationTrend(int $days = 30): array
    {
        $stats = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $stats[] = [
                'date' => $date->format('Y-m-d'),
                'count' => User::whereDate('created_at', $date)->count(),
            ];
        }

        return $stats;
    }

    /**
     * 獲取訂閱相關統計
     */
    public function getSubscriptionStats(): array
    {
        $now = Carbon::now();

        return [
            'total_subscriptions' => SubscriptionPaymentLog::where('rtn_code', 1)->count(),
            'active_subscriptions' => User::whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>', $now)
                ->count(),
            'expired_subscriptions' => User::whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '<=', $now)
                ->count(),
            'expiring_soon_7d' => User::whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>', $now)
                ->where('subscription_ends_at', '<=', $now->copy()->addDays(7))
                ->count(),
            'expiring_soon_30d' => User::whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '>', $now)
                ->where('subscription_ends_at', '<=', $now->copy()->addDays(30))
                ->count(),
        ];
    }

    /**
     * 獲取收入統計
     */
    public function getRevenueStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        return [
            'total_revenue' => SubscriptionPaymentLog::where('rtn_code', 1)->sum('trade_amt'),
            'this_month_revenue' => SubscriptionPaymentLog::where('rtn_code', 1)
                ->where('created_at', '>=', $startOfMonth)
                ->sum('trade_amt'),
            'last_month_revenue' => SubscriptionPaymentLog::where('rtn_code', 1)
                ->where('created_at', '>=', $startOfLastMonth)
                ->where('created_at', '<=', $endOfLastMonth)
                ->sum('trade_amt'),
            'average_revenue_per_month' => SubscriptionPaymentLog::where('rtn_code', 1)
                ->selectRaw('DATE_TRUNC(\'month\', created_at) as month, SUM(trade_amt) as revenue')
                ->groupByRaw('DATE_TRUNC(\'month\', created_at)')
                ->get()
                ->avg('revenue'),
        ];
    }
}