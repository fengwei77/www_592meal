<?php

namespace App\Filament\Pages;

use App\Services\SystemStatisticsService;
use App\Models\Order;
use App\Models\User;
use App\Models\Customer;
use App\Models\Store;
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
 * 餐點訂單管理頁面
 *
 * 僅限 Super Admin 訪問
 * 管理所有店家的客戶餐點訂單、訂單統計和處理流程
 */
class OrderManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithHeaderActions;

    protected string $view = 'filament.pages.order-management';

    protected static ?string $slug = 'order-management';

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
        return '餐點訂單管理';
    }

    /**
     * 導航標籤
     */
    public static function getNavigationLabel(): string
    {
        return '餐點訂單管理';
    }

    /**
     * 導航圖標
     */
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-home';
    }

    /**
     * 導航分組
     */
    public static function getNavigationGroup(): ?string
    {
        return '訂單管理';
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
            'orderStats' => $statsService->getOrderStats(),
            'todayStats' => $statsService->getTodayStats(),
            'dailyStats' => $statsService->getDailyStats(7),
            'monthlyStats' => $statsService->getMonthlyStats(),
            'revenueStats' => $statsService->getRevenueStats(),
            'pendingOrders' => Order::with(['user', 'customer', 'store', 'items.menuItem'])
                ->where('status', 'pending')
                ->latest()
                ->limit(10)
                ->get(),
            'recentOrders' => Order::with(['user', 'customer', 'store', 'items.menuItem'])
                ->latest()
                ->limit(10)
                ->get(),
            'topStores' => Store::withCount(['orders' => function ($query) {
                    $query->whereDate('created_at', '>=', Carbon::now()->subDays(30));
                }])
                ->orderBy('orders_count', 'desc')
                ->limit(10)
                ->get(),
        ]);
    }

    /**
     * 餐點訂單表格配置
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with(['user', 'customer', 'store', 'items.menuItem'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('訂單編號')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('店家名稱')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return $record->store ? $record->store->name : '未指定店家';
                    }),

                Tables\Columns\TextColumn::make('customer_info')
                    ->label('顧客資訊')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function (Builder $subQuery) use ($search) {
                            $subQuery->whereHas('customer', function (Builder $customerQuery) use ($search) {
                                $customerQuery->where('name', 'like', "%{$search}%")
                                           ->orWhere('phone', 'like', "%{$search}%");
                            })
                            ->orWhere('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_phone', 'like', "%{$search}%");
                        });
                    })
                    ->formatStateUsing(function ($record) {
                        $name = $record->customer?->name ?? $record->customer_name ?? '未知顧客';
                        $phone = $record->customer?->phone ?? $record->customer_phone ?? '';

                        return $phone ? "{$name} ({$phone})" : $name;
                    }),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('品項數量')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        return $record->items ? $record->items->count() : 0;
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('訂單金額')
                    ->money('TWD')
                    ->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('訂單狀態')
                    ->options([
                        'pending' => '待處理',
                        'confirmed' => '已確認',
                        'preparing' => '準備中',
                        'ready' => '待取餐',
                        'completed' => '已完成',
                        'cancelled' => '已取消',
                        'failed' => '失敗',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('付款方式')
                    ->formatStateUsing(function ($state) {
                        $methods = [
                            'cash' => '現金',
                            'credit_card' => '信用卡',
                            'bank_transfer' => '銀行轉帳',
                            'mobile_payment' => '行動支付',
                            'other' => '其他',
                        ];
                        return $methods[$state] ?? $state;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('付款狀態')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state) {
                        $statuses = [
                            'paid' => '已付款',
                            'pending' => '待付款',
                            'failed' => '付款失敗',
                            'refunded' => '已退款',
                        ];
                        return $statuses[$state] ?? $state;
                    }),

                Tables\Columns\TextColumn::make('order_type')
                    ->label('訂單類型')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivery' => 'info',
                        'pickup' => 'success',
                        'dine_in' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state) {
                        $types = [
                            'delivery' => '外送',
                            'pickup' => '自取',
                            'dine_in' => '內用',
                        ];
                        return $types[$state] ?? $state;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('下單時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('完成時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('未完成'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('訂單狀態')
                    ->options([
                        'pending' => '待處理',
                        'confirmed' => '已確認',
                        'preparing' => '準備中',
                        'ready' => '待取餐',
                        'completed' => '已完成',
                        'cancelled' => '已取消',
                        'failed' => '失敗',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('付款狀態')
                    ->options([
                        'paid' => '已付款',
                        'pending' => '待付款',
                        'failed' => '付款失敗',
                        'refunded' => '已退款',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('付款方式')
                    ->options([
                        'cash' => '現金',
                        'credit_card' => '信用卡',
                        'bank_transfer' => '銀行轉帳',
                        'mobile_payment' => '行動支付',
                        'other' => '其他',
                    ]),

                Tables\Filters\SelectFilter::make('order_type')
                    ->label('訂單類型')
                    ->options([
                        'delivery' => '外送',
                        'pickup' => '自取',
                        'dine_in' => '內用',
                    ]),

                Tables\Filters\SelectFilter::make('store')
                    ->label('店家')
                    ->searchable()
                    ->options(function () {
                        return Store::pluck('name', 'id')->toArray();
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->label('下單時間')
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

                Tables\Filters\Filter::make('amount_range')
                    ->label('金額範圍')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('最小金額')
                            ->numeric()
                            ->prefix('NT$'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('最大金額')
                            ->numeric()
                            ->prefix('NT$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Actions\Action::make('confirm_order')
                    ->label('確認訂單')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'pending')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'confirmed']);
                        Notification::make()
                            ->title('訂單已確認')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('mark_as_completed')
                    ->label('標記為已完成')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['confirmed', 'preparing', 'ready']))
                    ->action(function (Order $record): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => Carbon::now(),
                        ]);
                        Notification::make()
                            ->title('訂單已完成')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('cancel_order')
                    ->label('取消訂單')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['pending', 'confirmed']))
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('取消原因')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'status' => 'cancelled',
                            'cancellation_reason' => $data['cancellation_reason'],
                            'cancelled_at' => Carbon::now(),
                        ]);
                        Notification::make()
                            ->title('訂單已取消')
                            ->body('原因: ' . $data['cancellation_reason'])
                            ->warning()
                            ->send();
                    }),

                Actions\Action::make('mark_as_paid')
                    ->label('標記為已付款')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->payment_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('付款備註')
                            ->rows(2),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'payment_status' => 'paid',
                            'paid_at' => Carbon::now(),
                            'payment_notes' => $data['payment_notes'] ?? null,
                        ]);
                        Notification::make()
                            ->title('付款狀態已更新')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('view_details')
                    ->label('查看詳情')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Order $record) =>
                        view('filament.modals.food-order-details', [
                            'order' => $record->load(['user', 'customer', 'store', 'items.menuItem'])
                        ])
                    ),
            ])
            ->bulkActions([
                Actions\BulkAction::make('confirm_orders')
                    ->label('批量確認訂單')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (): bool => true)
                    ->action(function ($records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
                                $record->update(['status' => 'confirmed']);
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("已確認 {$count} 個訂單")
                            ->success()
                            ->send();
                    }),

                Actions\BulkAction::make('mark_as_completed')
                    ->label('批量標記為已完成')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if (in_array($record->status, ['confirmed', 'preparing', 'ready'])) {
                                $record->update([
                                    'status' => 'completed',
                                    'completed_at' => Carbon::now(),
                                ]);
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("已完成 {$count} 個訂單")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
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

            Actions\Action::make('daily_summary')
                ->label('每日統計報表')
                ->icon('heroicon-o-chart-bar')
                ->action(function () {
                    // 實作每日統計報表功能
                    Notification::make()
                        ->title('統計報表功能開發中')
                        ->info()
                        ->send();
                }),

            Actions\Action::make('process_pending_orders')
                ->label('處理待處理訂單')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->action(function () {
                    $pendingCount = Order::where('status', 'pending')->count();
                    Notification::make()
                        ->title('待處理訂單')
                        ->body("目前有 {$pendingCount} 個待處理訂單")
                        ->warning()
                        ->send();
                }),
        ];
    }
}