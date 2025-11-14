<?php

namespace App\Filament\Pages;

use App\Services\SystemStatisticsService;
use App\Models\User;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPaymentLog;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * 老闆訂閱管理頁面
 *
 * 僅限 Super Admin 訪問
 * 管理所有店家老闆的訂閱狀態、付款記錄和統計數據
 */
class SubscriptionManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithHeaderActions;

    protected string $view = 'filament.pages.subscription-management';

    protected static ?string $slug = 'subscription-management';

    /**
     * 權限檢查 - 僅限 Super Admin 且需要系統管理權限
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user &&
               $user->hasRole('super_admin') &&
               $user->hasPermissionTo('access_system_management');
    }

    /**
     * 頁面標題
     */
    public function getTitle(): string
    {
        return '老闆訂閱管理';
    }

    /**
     * 導航標籤
     */
    public static function getNavigationLabel(): string
    {
        return '老闆訂閱管理';
    }

    /**
     * 導航圖標
     */
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-credit-card';
    }

    /**
     * 導航分組
     */
    public static function getNavigationGroup(): ?string
    {
        return '訂閱管理';
    }

    /**
     * 導航排序
     */
    protected static ?int $navigationSort = 1;

    /**
     * 頁面內容
     */
    public function getViewData(): array
    {
        $statsService = new SystemStatisticsService();

        return array_merge(parent::getViewData(), [
            'subscriptionStats' => $statsService->getSubscriptionStats(),
            'activeSubscriptions' => User::where('subscription_ends_at', '>', Carbon::now())
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'store_owner');
                })
                ->latest()
                ->limit(10)
                ->get(),
            'expiringSoonSubscriptions' => User::where('subscription_ends_at', '>', Carbon::now())
                ->where('subscription_ends_at', '<=', Carbon::now()->addDays(7))
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'store_owner');
                })
                ->latest()
                ->limit(10)
                ->get(),
            'recentSubscriptionOrders' => SubscriptionOrder::with('user')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    /**
     * 訂閱訂單表格配置
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                SubscriptionOrder::query()
                    ->with(['user'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('訂單編號')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('老闆姓名')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return $record->user ? $record->user->name : '未知用戶';
                    }),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('信箱')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return $record->user ? $record->user->email : 'N/A';
                    }),

                Tables\Columns\TextColumn::make('months')
                    ->label('訂閱月數')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('單價')
                    ->money('TWD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('總金額')
                    ->money('TWD')
                    ->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('狀態')
                    ->options([
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'failed' => '失敗',
                        'cancelled' => '已取消',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription_start_date')
                    ->label('訂閱開始日')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription_end_date')
                    ->label('訂閱結束日')
                    ->date('Y-m-d')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return '未設定';

                        $isExpired = Carbon::parse($state)->isPast();
                        return $state . ($isExpired ? ' (已過期)' : '');
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('付款時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('訂單狀態')
                    ->options([
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'failed' => '失敗',
                        'cancelled' => '已取消',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->label('創建時間')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('開始日期'),
                        Forms\Components\DatePicker::make('until')
                            ->label('結束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('expiry_range')
                    ->label('到期日範圍')
                    ->form([
                        Forms\Components\DatePicker::make('expiry_from')
                            ->label('到期開始日'),
                        Forms\Components\DatePicker::make('expiry_until')
                            ->label('到期結束日'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['expiry_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('subscription_end_date', '>=', $date),
                            )
                            ->when(
                                $data['expiry_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('subscription_end_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Actions\Action::make('mark_as_paid')
                    ->label('標記為已付款')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (SubscriptionOrder $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('備註')
                            ->rows(3),
                    ])
                    ->action(function (SubscriptionOrder $record, array $data): void {
                        $this->markSubscriptionOrderAsPaid($record, $data['notes'] ?? null);
                    }),

                Actions\Action::make('extend_subscription')
                    ->label('延長訂閱')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->visible(fn (SubscriptionOrder $record): bool => $record->status === 'paid')
                    ->form([
                        Forms\Components\TextInput::make('additional_months')
                            ->label('增加月數')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->default(1)
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('原因')
                            ->rows(2),
                    ])
                    ->action(function (SubscriptionOrder $record, array $data): void {
                        $this->extendSubscription($record, $data['additional_months'], $data['reason'] ?? '');
                    }),

                Actions\Action::make('view_details')
                    ->label('查看詳情')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (SubscriptionOrder $record) =>
                        view('filament.modals.subscription-order-details', ['order' => $record->load(['user'])])
                    ),
            ])
            ->bulkActions([
                Actions\BulkAction::make('mark_as_paid')
                    ->label('批量標記為已付款')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
                                $this->markSubscriptionOrderAsPaid($record);
                            }
                        }
                        Notification::make()
                            ->title('批量操作完成')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    /**
     * 標記訂閱訂單為已付款
     */
    private function markSubscriptionOrderAsPaid(SubscriptionOrder $order, ?string $notes = null): void
    {
        if (!$order->user) {
            Notification::make()
                ->title('錯誤')
                ->body('訂單沒有關聯用戶，無法處理付款')
                ->danger()
                ->send();
            return;
        }

        DB::transaction(function () use ($order, $notes) {
            // 更新訂單狀態
            $order->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'notes' => $notes,
            ]);

            // 設定訂閱時間範圍
            $user = $order->user;
            $now = Carbon::now();

            // 如果沒有指定開始時間，從今天開始
            $startDate = $order->subscription_start_date ?: $now;

            // 計算結束時間
            $endDate = $startDate->copy()->addMonths($order->months);

            $order->update([
                'subscription_start_date' => $startDate,
                'subscription_end_date' => $endDate,
            ]);

            // 記錄訂閱付款日誌
            SubscriptionPaymentLog::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'months' => $order->months,
                'status' => 'paid',
                'payment_method' => $order->payment_type ?? 'manual',
                'payment_notes' => $notes,
                'expires_at' => $endDate,
            ]);
        });

        Notification::make()
            ->title('訂閱訂單已標記為已付款')
            ->body("用戶 {$order->user->name} 的 {$order->months} 個月訂閱已生效")
            ->success()
            ->send();
    }

    /**
     * 延長訂閱
     */
    private function extendSubscription(SubscriptionOrder $order, int $additionalMonths, string $reason): void
    {
        if (!$order->user) {
            Notification::make()
                ->title('錯誤')
                ->body('訂單沒有關聯用戶，無法延長訂閱')
                ->danger()
                ->send();
            return;
        }

        DB::transaction(function () use ($order, $additionalMonths, $reason) {
            $currentEndDate = $order->subscription_end_date;
            $now = Carbon::now();

            // 新的結束日期
            $newEndDate = $currentEndDate && $currentEndDate->greaterThan($now)
                ? $currentEndDate->copy()->addMonths($additionalMonths)
                : $now->copy()->addMonths($additionalMonths);

            // 更新訂單的結束日期
            $order->update([
                'subscription_end_date' => $newEndDate,
                'notes' => ($order->notes ?? '') . "\n[延長] " . $additionalMonths . " 個月 - " . $reason,
            ]);

            // 記錄延期日誌
            SubscriptionPaymentLog::create([
                'user_id' => $order->user->id,
                'order_id' => $order->id,
                'amount' => 0, // 延期通常不另外收費
                'months' => $additionalMonths,
                'status' => 'extension',
                'payment_method' => 'manual',
                'payment_notes' => '延長 ' . $additionalMonths . ' 個月 - ' . $reason,
                'expires_at' => $newEndDate,
            ]);
        });

        Notification::make()
            ->title('訂閱已延長')
            ->body("用戶 {$order->user->name} 的訂閱已延長 {$additionalMonths} 個月")
            ->success()
            ->send();
    }

    /**
     * 頁面標題操作
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_stats')
                ->label('重新整理統計')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->js('window.location.reload()');
                }),

            Actions\Action::make('export_subscriptions')
                ->label('匯出訂閱資料')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    // 實作訂閱資料匯出功能
                    Notification::make()
                        ->title('訂閱資料匯出功能開發中')
                        ->info()
                        ->send();
                }),

            Actions\Action::make('send_reminders')
                ->label('發送到期提醒')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->action(function () {
                    $expiringCount = User::where('subscription_ends_at', '>', Carbon::now())
                        ->where('subscription_ends_at', '<=', Carbon::now()->addDays(7))
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'store_owner');
                        })
                        ->count();

                    Notification::make()
                        ->title('提醒功能開發中')
                        ->body("找到 {$expiringCount} 個即將到期的訂閱")
                        ->info()
                        ->send();
                }),
        ];
    }
}