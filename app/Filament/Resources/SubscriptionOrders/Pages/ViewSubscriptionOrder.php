<?php

namespace App\Filament\Resources\SubscriptionOrders\Pages;

use App\Filament\Resources\SubscriptionOrders\SubscriptionOrderResource;
use App\Models\SubscriptionOrder;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSubscriptionOrder extends ViewRecord
{
    protected static string $resource = SubscriptionOrderResource::class;

    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pay')
                ->label('前往付款')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'pending' && !$this->record->isExpired())
                ->url(fn (): string => route('subscription.confirm', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('back')
                ->label('返回')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => $this->getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return '訂單詳情 - ' . $this->record->order_number;
    }

    
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        // 檢查權限 - 用戶只能查看自己的訂單，Super Admin 可以查看所有訂單
        $user = Auth::user();
        if ($this->record->user_id !== $user->id && !$user->hasRole('super_admin')) {
            abort(403, '無權存取此訂單');
        }
    }
}