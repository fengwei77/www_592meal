<?php

namespace App\Filament\Widgets;

use App\Services\SubscriptionService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SubscriptionPeriodWidget extends Widget
{
    protected string $view = 'filament.widgets.subscription-period-widget';

    protected static ?int $sort = 2;

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
     * 獲取訂閱期間數據
     */
    protected function getViewData(): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->getDefaultPeriod();
            }

            $subscriptionService = app(SubscriptionService::class);
            $stats = $subscriptionService->getUserSubscriptionStats($user);

            $startDate = $stats['start_date'] ?? null;
            $endDate = $stats['expiry_date'] ?? null;
            $totalMonths = (int) ($stats['total_months'] ?? 0);

            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_months' => $totalMonths,
                'period_text' => $this->getPeriodText($startDate, $endDate),
                'total_months_text' => $this->getTotalMonthsText($totalMonths),
            ];
        } catch (\Exception $e) {
            return $this->getDefaultPeriod();
        }
    }

    /**
     * 獲取默認期間
     */
    private function getDefaultPeriod(): array
    {
        return [
            'start_date' => null,
            'end_date' => null,
            'total_months' => 0,
            'period_text' => '未設定訂閱期間',
            'total_months_text' => '總計 0 個月',
        ];
    }

    /**
     * 格式化日期
     */
    private function formatDate(?string $date): string
    {
        return $date ? date('Y-m-d', strtotime($date)) : '未設定';
    }

    /**
     * 獲取期間文字
     */
    private function getPeriodText(?string $startDate, ?string $endDate): string
    {
        if ($startDate && $endDate) {
            return $this->formatDate($startDate) . ' 至 ' . $this->formatDate($endDate);
        } else {
            return '未設定訂閱期間';
        }
    }

    /**
     * 獲取總月份文字
     */
    private function getTotalMonthsText(int $totalMonths): string
    {
        if ($totalMonths > 0) {
            return '總計 ' . $totalMonths . ' 個月';
        } else {
            return '總計 0 個月';
        }
    }
}