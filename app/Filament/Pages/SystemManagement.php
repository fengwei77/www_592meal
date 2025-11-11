<?php

namespace App\Filament\Pages;

use App\Services\SystemStatisticsService;
use App\Models\Order;
use App\Models\User;
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
 * 系統管理頁面
 *
 * 僅限 Super Admin 訪問
 * 提供完整的系統統計數據和訂單管理功能
 */
class SystemManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithHeaderActions;

    protected string $view = 'filament.pages.system-management';

    protected static ?string $slug = 'system-management';

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

    public function mount(): void
    {
        // 移除未初始化的類型屬性，改為方法內實例化
    }

    /**
     * 頁面標題
     */
    public function getTitle(): string
    {
        return '系統管理面板';
    }

    /**
     * 導航標籤
     */
    public static function getNavigationLabel(): string
    {
        return '系統管理';
    }

    /**
     * 導航圖標
     */
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    /**
     * 導航分組
     */
    public static function getNavigationGroup(): ?string
    {
        return '系統管理';
    }

    /**
     * 頁面內容
     */
    public function getViewData(): array
    {
        $statsService = new SystemStatisticsService();

        return array_merge(parent::getViewData(), [
            'overallStats' => $statsService->getOverallStats(),
            'orderStats' => $statsService->getOrderStats(),
            'todayStats' => $statsService->getTodayStats(),
            'dailyStats' => $statsService->getDailyStats(7),
            'monthlyStats' => $statsService->getMonthlyStats(),
            'subscriptionStats' => $statsService->getSubscriptionStats(),
            'revenueStats' => $statsService->getRevenueStats(),
        ]);
    }

    /**
     * 訂單表格配置
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with(['user', 'items'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('訂單編號')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('用戶')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record->user ? $record->user->name : 'N/A'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('金額')
                    ->money('TWD')
                    ->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('狀態')
                    ->options([
                        'pending' => '待處理',
                        'paid' => '已付款',
                        'completed' => '已完成',
                        'failed' => '失敗',
                        'cancelled' => '已取消',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('付款方式')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('創建時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('付款時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('訂單狀態')
                    ->options([
                        'pending' => '待處理',
                        'paid' => '已付款',
                        'completed' => '已完成',
                        'failed' => '失敗',
                        'cancelled' => '已取消',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('付款方式')
                    ->options([
                        'credit_card' => '信用卡',
                        'cvs' => '超商代碼',
                        'barcode' => '超商條碼',
                        'manual' => '手動處理',
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
            ])
            ->actions([
                Actions\Action::make('mark_as_paid')
                    ->label('標記為已付款')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('備註')
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $this->markOrderAsPaid($record, $data['notes'] ?? null);
                    }),

                Actions\Action::make('view_details')
                    ->label('查看詳情')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Order $record): string =>
                        view('filament.modals.order-details', ['order' => $record])->render()
                    ),

                Actions\DeleteAction::make()
                    ->label('刪除訂單')
                    ->color('danger'),
            ])
            ->bulkActions([
                Actions\BulkAction::make('mark_as_paid')
                    ->label('批量標記為已付款')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
                                $this->markOrderAsPaid($record);
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
     * 標記訂單為已付款並更新訂閱期限
     */
    private function markOrderAsPaid(Order $order, ?string $notes = null): void
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
                'payment_notes' => $notes,
            ]);

            // 計算訂閱月數（基於金額，每月50元）
            $months = (int) ($order->total_amount / 50);

            // 更新用戶訂閱期限
            $user = $order->user;
            $currentExpiry = $user->subscription_ends_at;
            $now = Carbon::now();

            if ($currentExpiry && $currentExpiry->greaterThan($now)) {
                // 從現有到期日往後加
                $newExpiry = $currentExpiry->copy()->addMonths($months);
            } else {
                // 從今天開始計算
                $newExpiry = $now->copy()->addMonths($months);
            }

            $user->update([
                'subscription_ends_at' => $newExpiry,
            ]);

            // 記錄訂閱付款日誌
            SubscriptionPaymentLog::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'months' => $months,
                'status' => 'paid',
                'payment_method' => $order->payment_method,
                'payment_notes' => $notes,
                'expires_at' => $newExpiry,
            ]);
        });

        Notification::make()
            ->title('訂單已標記為已付款')
            ->body("用戶 {$order->user->name} 的訂閱期限已更新 " . ($order->total_amount/50) . " 個月")
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

            Actions\Action::make('export_orders')
                ->label('匯出訂單')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    // 實作訂單匯出功能
                    Notification::make()
                        ->title('訂單匯出功能開發中')
                        ->info()
                        ->send();
                }),
        ];
    }
}