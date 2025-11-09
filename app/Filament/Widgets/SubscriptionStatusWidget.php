<?php

namespace App\Filament\Widgets;

use App\Services\SubscriptionService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SubscriptionStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.subscription-status-widget';

    protected static ?int $sort = 1;

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
     * 獲取訂閱狀態數據
     */
    protected function getViewData(): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->getDefaultStatus();
            }

            $subscriptionService = app(SubscriptionService::class);
            $stats = $subscriptionService->getUserSubscriptionStats($user);

            return [
                'status' => $stats['subscription_status'] ?? 'none',
                'label' => $stats['subscription_label'] ?? '無訂閱',
                'remaining_days' => (int) ($stats['remaining_days'] ?? 0),
                'is_trial' => $user->isInTrialPeriod(),
                'trial_remaining_days' => $user->isInTrialPeriod() ? $user->getTrialRemainingDays() : 0,
                'status_color' => $this->getStatusColor($stats['subscription_status'] ?? 'none'),
                'status_text' => $this->getStatusText($stats['subscription_status'] ?? 'none', (int) ($stats['remaining_days'] ?? 0), $user->isInTrialPeriod(), $user->isInTrialPeriod() ? $user->getTrialRemainingDays() : 0),
            ];
        } catch (\Exception $e) {
            return $this->getDefaultStatus();
        }
    }

    /**
     * 獲取默認狀態
     */
    private function getDefaultStatus(): array
    {
        return [
            'status' => 'none',
            'label' => '無訂閱',
            'remaining_days' => 0,
            'is_trial' => false,
            'trial_remaining_days' => 0,
            'status_color' => 'gray',
            'status_text' => '無訂閱',
        ];
    }

    /**
     * 獲取狀態顏色
     */
    private function getStatusColor(string $status): string
    {
        return match($status) {
            'trial' => 'primary',
            'active' => 'success',
            'expired' => 'danger',
            default => 'gray'
        };
    }

    /**
     * 獲取狀態文字
     */
    private function getStatusText(string $status, int $remainingDays, bool $isTrial, int $trialRemainingDays): string
    {
        if ($isTrial) {
            return '試用期中，剩餘 ' . $trialRemainingDays . ' 天';
        } elseif ($status === 'active') {
            return '訂閱中，剩餘 ' . $remainingDays . ' 天';
        } else {
            return '無訂閱';
        }
    }
}