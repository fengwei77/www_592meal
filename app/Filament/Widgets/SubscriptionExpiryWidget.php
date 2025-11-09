<?php

namespace App\Filament\Widgets;

use App\Services\SubscriptionService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SubscriptionExpiryWidget extends Widget
{
    protected string $view = 'filament.widgets.subscription-expiry-widget';

    protected static ?int $sort = 3;

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
     * 獲取到期日期數據
     */
    protected function getViewData(): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->getDefaultExpiry();
            }

            $subscriptionService = app(SubscriptionService::class);
            $stats = $subscriptionService->getUserSubscriptionStats($user);

            $expiryDate = $stats['expiry_date'] ?? null;
            $remainingDays = (int) ($stats['remaining_days'] ?? 0);

            // 檢查是否即將到期（30天內）
            $isExpiringSoon = $remainingDays > 0 && $remainingDays <= 30;
            $isExpired = $remainingDays <= 0;

            return [
                'expiry_date' => $expiryDate,
                'formatted_expiry_date' => $this->getFormattedExpiryDate($expiryDate),
                'remaining_days' => $remainingDays,
                'is_expiring_soon' => $isExpiringSoon,
                'is_expired' => $isExpired,
                'status_color' => $this->getStatusColor($isExpired, $isExpiringSoon, $remainingDays),
                'status_text' => $this->getStatusText($isExpired, $isExpiringSoon, $remainingDays),
            ];
        } catch (\Exception $e) {
            return $this->getDefaultExpiry();
        }
    }

    /**
     * 獲取默認到期資料
     */
    private function getDefaultExpiry(): array
    {
        return [
            'expiry_date' => null,
            'formatted_expiry_date' => '未設定',
            'remaining_days' => 0,
            'is_expiring_soon' => false,
            'is_expired' => true,
            'status_color' => 'gray',
            'status_text' => '未設定',
        ];
    }

    /**
     * 格式化到期日期
     */
    private function getFormattedExpiryDate(?string $date): string
    {
        return $date ? date('Y-m-d', strtotime($date)) : '未設定';
    }

    /**
     * 獲取狀態顏色
     */
    private function getStatusColor(bool $isExpired, bool $isExpiringSoon, int $remainingDays): string
    {
        if ($isExpired) {
            return 'danger';
        } elseif ($isExpiringSoon) {
            return 'warning';
        } elseif ($remainingDays > 0) {
            return 'success';
        } else {
            return 'gray';
        }
    }

    /**
     * 獲取狀態文字
     */
    private function getStatusText(bool $isExpired, bool $isExpiringSoon, int $remainingDays): string
    {
        if ($isExpired) {
            return '已到期';
        } elseif ($isExpiringSoon) {
            return '即將到期 (' . $remainingDays . ' 天)';
        } elseif ($remainingDays > 0) {
            return '正常 (' . $remainingDays . ' 天)';
        } else {
            return '未設定';
        }
    }
}