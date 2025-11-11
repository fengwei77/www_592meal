<?php

namespace App\Filament\Resources\SubscriptionOrders;

use App\Filament\Resources\SubscriptionOrders\Pages\ViewSubscriptionOrders;
use App\Filament\Resources\SubscriptionOrders\Pages\ViewSubscriptionOrder;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SubscriptionOrderResource extends Resource
{
    protected static ?string $model = SubscriptionOrder::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = '訂單紀錄';

    protected static ?string $modelLabel = '訂單紀錄';

    protected static ?string $pluralModelLabel = '訂單紀錄';

    protected static ?int $navigationSort = 1;

    // 只允許老闆和超級管理員存取
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('store_owner') || $user->hasRole('super_admin'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('訂單資訊')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('用戶')
                            ->options(function () {
                                return User::query()
                                    ->whereHas('roles', function ($query) {
                                        $query->where('name', 'store_owner');
                                    })
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TextInput::make('order_number')
                            ->label('訂單編號')
                            ->disabled()
                            ->default(function () {
                                return 'SUB' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                            }),

                        Forms\Components\Select::make('months')
                            ->label('訂閱月數')
                            ->options(function () {
                                $options = [];
                                for ($i = 1; $i <= 18; $i++) {
                                    $options[$i] = "{$i}個月";
                                }
                                return $options;
                            })
                            ->required()
                            ->default(1),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('單價')
                            ->numeric()
                            ->default(50)
                            ->required()
                            ->prefix('NT$'),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('總金額')
                            ->numeric()
                            ->prefix('NT$')
                            ->default(50),

                        Forms\Components\Select::make('status')
                            ->label('狀態')
                            ->options([
                                'pending' => '待付款',
                                'paid' => '已付款',
                                'expired' => '已過期',
                                'cancelled' => '已取消',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Textarea::make('notes')
                            ->label('備註')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('訂單編號')
                    ->searchable()
                    ->copyable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('用戶')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('months')
                    ->label('月數')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('總金額')
                    ->money('TWD')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'expired' => '已過期',
                        'cancelled' => '已取消',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('狀態')
                    ->options([
                        'pending' => '待付款',
                        'paid' => '已付款',
                        'expired' => '已過期',
                        'cancelled' => '已取消',
                        'failed' => '付款失敗',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('用戶')
                    ->searchable()
                    ->options(function () {
                        return User::query()
                            ->whereHas('roles', function ($query) {
                                $query->whereIn('name', ['store_owner', 'super_admin']);
                            })
                            ->pluck('name', 'id')
                            ->toArray();
                    }),

                Tables\Filters\Filter::make('order_number')
                    ->label('訂單編號')
                    ->form([
                        Forms\Components\TextInput::make('order_number')
                            ->label('訂單編號')
                            ->placeholder('輸入訂單編號'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['order_number'],
                            fn (Builder $query) => $query->where('order_number', 'like', "%{$data['order_number']}%")
                        );
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->label('日期範圍')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('開始日期'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('結束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query) => $query->whereDate('created_at', '>=', $data['start_date'])
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query) => $query->whereDate('created_at', '<=', $data['end_date'])
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
                                fn (Builder $query) => $query->where('total_amount', '>=', $data['min_amount'])
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query) => $query->where('total_amount', '<=', $data['max_amount'])
                            );
                    }),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label('查看詳情')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (SubscriptionOrder $record): string => route('filament.admin.resources.subscription-orders.view', $record)),

                Actions\Action::make('edit_status')
                    ->label('修改狀態')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->visible(fn (SubscriptionOrder $record): bool => auth()->user()->hasRole('super_admin'))
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('訂單狀態')
                            ->options([
                                'pending' => '待付款',
                                'paid' => '已付款',
                                'expired' => '已過期',
                                'cancelled' => '已取消',
                                'failed' => '付款失敗',
                            ])
                            ->required()
                            ->default(fn (SubscriptionOrder $record) => $record->status),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('管理員備註')
                            ->placeholder('請輸入修改原因或備註...')
                            ->rows(3)
                            ->helperText('此備註會記錄到訂單歷史中'),
                    ])
                    ->action(function (SubscriptionOrder $record, array $data) {
                        $oldStatus = $record->status;
                        $newStatus = $data['status'];

                        // 如果狀態改為已付款，需要添加訂閱期間
                        if ($oldStatus !== 'paid' && $newStatus === 'paid') {
                            $user = $record->user;
                            if ($user) {
                                // 計算新的訂閱到期日
                                $currentEndDate = $user->subscription_ends_at;
                                $startDate = $currentEndDate && $currentEndDate->isFuture()
                                    ? $currentEndDate
                                    : now();

                                $newEndDate = $startDate->copy()->addDays($record->months * 30);

                                // 更新用戶訂閱資訊
                                $user->subscription_ends_at = $newEndDate;
                                $user->save();

                                \Log::info('Subscription extended by admin', [
                                    'user_id' => $user->id,
                                    'order_id' => $record->id,
                                    'order_number' => $record->order_number,
                                    'months' => $record->months,
                                    'start_date' => $startDate->format('Y-m-d'),
                                    'end_date' => $newEndDate->format('Y-m-d'),
                                    'admin_id' => auth()->id(),
                                ]);
                            }
                        }

                        // 更新訂單狀態
                        $record->status = $newStatus;

                        // 添加管理員備註
                        if (!empty($data['admin_notes'])) {
                            $record->notes = ($record->notes ?? '') . "\n[管理員 " . auth()->user()->name . " 於 " . now()->format('Y-m-d H:i:s') . "]\n狀態變更: {$oldStatus} → {$newStatus}\n備註: " . $data['admin_notes'];
                        }

                        $record->save();

                        // 發送通知
                        $statusText = match($newStatus) {
                            'pending' => '待付款',
                            'paid' => '已付款',
                            'expired' => '已過期',
                            'cancelled' => '已取消',
                            'failed' => '付款失敗',
                            default => $newStatus,
                        };

                        \Filament\Notifications\Notification::make()
                            ->title('訂單狀態已更新')
                            ->body("訂單 {$record->order_number} 狀態已更新為 {$statusText}")
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('pay')
                    ->label('前往付款')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (SubscriptionOrder $record): bool => $record->status === 'pending' && !$record->isExpired() && !auth()->user()->hasRole('super_admin'))
                    ->url(fn (SubscriptionOrder $record): string => route('subscription.confirm', $record))
                    ->openUrlInNewTab(),

                Actions\Action::make('cancel')
                    ->label('取消訂單')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SubscriptionOrder $record): bool => $record->status === 'pending' && !auth()->user()->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->modalHeading('確認取消訂單')
                    ->modalDescription('確定要取消這個訂單嗎？此操作無法復原。')
                    ->modalSubmitActionLabel('確認取消')
                    ->modalCancelActionLabel('取消')
                    ->action(function (SubscriptionOrder $record) {
                        $record->status = 'cancelled';
                        $record->notes = ($record->notes ?? '') . "\n[系統] 訂單於 " . now()->format('Y-m-d H:i:s') . " 由用戶取消";
                        $record->save();

                        \Log::info('Subscription order cancelled', [
                            'order_id' => $record->id,
                            'order_number' => $record->order_number,
                            'user_id' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('訂單已取消')
                            ->body("訂單 {$record->order_number} 已成功取消")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('暫無訂單紀錄')
            ->emptyStateDescription('訂單紀錄將會顯示在這裡');
    }

    // 老闆只能看到自己的訂單，管理員可以看到所有訂單
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        // 如果是老闆角色，只能看到自己的訂單，Super Admin 可以看到所有訂單
        if ($user && $user->hasRole('store_owner') && !$user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ViewSubscriptionOrders::route('/'),
            'view' => ViewSubscriptionOrder::route('/{record}'),
        ];
    }
}
