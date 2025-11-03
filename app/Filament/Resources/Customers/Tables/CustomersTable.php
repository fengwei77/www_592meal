<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\OrderCancellationLog;
use App\Models\Store;
use App\Models\StoreCustomerBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // 只顯示有訂過該店家的客戶
                // Note: This needs to be scoped per store, will be handled in the page class
                $query->whereHas('orders');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('客戶姓名')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('line_id')
                    ->label('LINE ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('已複製 LINE ID'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('電話')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('orders_count')
                    ->label('訂單數')
                    ->getStateUsing(function ($record) {
                        $user = Auth::user();
                        if (!$user) {
                            return 0;
                        }

                        // Super Admin 看所有訂單
                        if ($user->hasRole('super_admin')) {
                            return $record->orders()->count();
                        }

                        // Store Owner 只看自己店家的訂單
                        $storeIds = Store::where('user_id', $user->id)->pluck('id');
                        return $record->orders()->whereIn('store_id', $storeIds)->count();
                    })
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->description(fn () => Auth::user()?->hasRole('super_admin') ? '所有店家' : '您的店家'),

                TextColumn::make('cancellation_count')
                    ->label('取消次數')
                    ->getStateUsing(function ($record) {
                        if (!$record->line_id) {
                            return 0;
                        }

                        $user = Auth::user();
                        if (!$user) {
                            return 0;
                        }

                        // Super Admin 看所有取消次數
                        if ($user->hasRole('super_admin')) {
                            return OrderCancellationLog::where('line_user_id', $record->line_id)->count();
                        }

                        // Store Owner 只看自己店家的取消次數
                        $storeIds = Store::where('user_id', $user->id)->pluck('id');

                        return OrderCancellationLog::where('line_user_id', $record->line_id)
                            ->whereHas('order', function ($q) use ($storeIds) {
                                $q->whereIn('store_id', $storeIds);
                            })
                            ->count();
                    })
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 8 => 'danger',
                        $state >= 5 => 'warning',
                        default => 'gray',
                    })
                    ->description(fn () => Auth::user()?->hasRole('super_admin') ? '所有店家' : '您的店家'),

                IconColumn::make('is_blocked')
                    ->label('封鎖狀態')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        if (!$record->line_id) {
                            return false;
                        }

                        $user = Auth::user();
                        if (!$user) {
                            return false;
                        }

                        // 獲取用戶擁有的店家
                        $storeIds = Store::where('user_id', $user->id)->pluck('id');

                        // 檢查是否被任何一個店家封鎖
                        foreach ($storeIds as $storeId) {
                            if (StoreCustomerBlock::isBlockedByStore($record->line_id, $storeId)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->trueIcon('heroicon-o-shield-exclamation')
                    ->falseIcon('heroicon-o-shield-check')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('blocked_stores_count')
                    ->label('封鎖店家數')
                    ->getStateUsing(function ($record) {
                        if (!$record->line_id) {
                            return 0;
                        }
                        return StoreCustomerBlock::getBlockedStoreCount($record->line_id);
                    })
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 3 => 'danger',
                        $state >= 1 => 'warning',
                        default => 'success',
                    })
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),

                IconColumn::make('is_platform_blocked')
                    ->label('平台封鎖')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        if (!$record->line_id) {
                            return false;
                        }
                        return StoreCustomerBlock::isPlatformBlocked($record->line_id);
                    })
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),

                TextColumn::make('created_at')
                    ->label('註冊時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_blocked')
                    ->label('封鎖狀態')
                    ->options([
                        'blocked' => '已封鎖',
                        'active' => '正常',
                    ])
                    ->query(function (Builder $query, $state) {
                        if ($state['value'] === 'blocked') {
                            $query->whereHas('storeCustomerBlocks');
                        } elseif ($state['value'] === 'active') {
                            $query->whereDoesntHave('storeCustomerBlocks');
                        }
                    }),
            ])
            ->recordActions([
                Action::make('block')
                    ->label('封鎖')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('封鎖客戶')
                    ->modalDescription(fn ($record) => "確定要封鎖客戶 {$record->name} 嗎？")
                    ->visible(function ($record) {
                        if (!$record->line_id) {
                            return false;
                        }

                        $user = Auth::user();
                        if (!$user) {
                            return false;
                        }

                        // 獲取用戶擁有的店家
                        $storeIds = Store::where('user_id', $user->id)->pluck('id');

                        // 檢查是否有任何店家未封鎖此客戶
                        foreach ($storeIds as $storeId) {
                            if (!StoreCustomerBlock::isBlockedByStore($record->line_id, $storeId)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->form(function ($record) {
                        $user = Auth::user();
                        $stores = Store::where('user_id', $user->id)->get();

                        // 過濾出未封鎖此客戶的店家
                        $availableStores = $stores->filter(function ($store) use ($record) {
                            return !StoreCustomerBlock::isBlockedByStore($record->line_id, $store->id);
                        })->pluck('name', 'id')->toArray();

                        return [
                            Select::make('store_ids')
                                ->label('選擇店家')
                                ->options($availableStores)
                                ->multiple()
                                ->required()
                                ->default(array_keys($availableStores))
                                ->helperText('選擇要封鎖此客戶的店家'),

                            Textarea::make('notes')
                                ->label('封鎖原因')
                                ->required()
                                ->placeholder('請輸入封鎖原因，例如：重複取消訂單、惡意下單等')
                                ->rows(3),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $user = Auth::user();
                        $storeIds = $data['store_ids'];
                        $notes = $data['notes'];

                        $blockedCount = 0;
                        foreach ($storeIds as $storeId) {
                            // 獲取該店的取消次數
                            $cancellationCount = OrderCancellationLog::getStoreCancellationCount($record->line_id, $storeId);

                            StoreCustomerBlock::blockCustomer(
                                $storeId,
                                $record->line_id,
                                $record->id,
                                'manual_block',
                                $cancellationCount,
                                $user->name ?? 'admin',
                                $notes
                            );
                            $blockedCount++;
                        }

                        Notification::make()
                            ->success()
                            ->title('封鎖成功')
                            ->body("已成功封鎖客戶 {$record->name}，共 {$blockedCount} 個店家。")
                            ->send();
                    }),

                Action::make('unblock')
                    ->label('解除封鎖')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('解除封鎖')
                    ->modalDescription(fn ($record) => "確定要解除客戶 {$record->name} 的封鎖嗎？")
                    ->visible(function ($record) {
                        if (!$record->line_id) {
                            return false;
                        }

                        $user = Auth::user();
                        if (!$user) {
                            return false;
                        }

                        // 獲取用戶擁有的店家
                        $storeIds = Store::where('user_id', $user->id)->pluck('id');

                        // 檢查是否有任何店家封鎖了此客戶
                        foreach ($storeIds as $storeId) {
                            if (StoreCustomerBlock::isBlockedByStore($record->line_id, $storeId)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->form(function ($record) {
                        $user = Auth::user();
                        $stores = Store::where('user_id', $user->id)->get();

                        // 過濾出已封鎖此客戶的店家
                        $blockedStores = $stores->filter(function ($store) use ($record) {
                            return StoreCustomerBlock::isBlockedByStore($record->line_id, $store->id);
                        })->pluck('name', 'id')->toArray();

                        return [
                            Select::make('store_ids')
                                ->label('選擇店家')
                                ->options($blockedStores)
                                ->multiple()
                                ->required()
                                ->default(array_keys($blockedStores))
                                ->helperText('選擇要解除封鎖的店家'),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $storeIds = $data['store_ids'];

                        $unblockedCount = 0;
                        foreach ($storeIds as $storeId) {
                            if (StoreCustomerBlock::unblockCustomer($record->line_id, $storeId)) {
                                $unblockedCount++;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('解除封鎖成功')
                            ->body("已成功解除客戶 {$record->name} 的封鎖，共 {$unblockedCount} 個店家。")
                            ->send();
                    }),

                // 系統管理員專用：解除所有封鎖
                Action::make('admin_unlock_all')
                    ->label('解除所有封鎖')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('解除所有店家的封鎖')
                    ->modalDescription(fn ($record) => "確定要解除客戶 {$record->name} 在所有店家的封鎖嗎？此操作將移除所有封鎖記錄。")
                    ->visible(function ($record) {
                        if (!Auth::user()?->hasRole('super_admin')) {
                            return false;
                        }

                        if (!$record->line_id) {
                            return false;
                        }

                        return StoreCustomerBlock::where('line_user_id', $record->line_id)->exists();
                    })
                    ->form([
                        Textarea::make('reason')
                            ->label('解鎖原因')
                            ->required()
                            ->placeholder('請輸入解鎖原因，例如：客戶申訴成功、誤封等')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $blocks = StoreCustomerBlock::where('line_user_id', $record->line_id)->get();
                        $unblockedCount = 0;

                        foreach ($blocks as $block) {
                            if ($block->delete()) {
                                $unblockedCount++;

                                // 記錄解鎖操作
                                \Log::info('Admin unlocked customer', [
                                    'customer_id' => $record->id,
                                    'line_user_id' => $record->line_id,
                                    'store_id' => $block->store_id,
                                    'admin_id' => Auth::id(),
                                    'reason' => $data['reason'],
                                ]);
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('解除所有封鎖成功')
                            ->body("已成功解除客戶 {$record->name} 在 {$unblockedCount} 個店家的封鎖。")
                            ->send();
                    }),

                // 系統管理員專用：查看封鎖詳情
                Action::make('view_blocks')
                    ->label('查看封鎖詳情')
                    ->icon('heroicon-o-information-circle')
                    ->color('info')
                    ->visible(function ($record) {
                        if (!Auth::user()?->hasRole('super_admin')) {
                            return false;
                        }

                        if (!$record->line_id) {
                            return false;
                        }

                        return StoreCustomerBlock::where('line_user_id', $record->line_id)->exists();
                    })
                    ->modalContent(function ($record) {
                        $blocks = StoreCustomerBlock::where('line_user_id', $record->line_id)
                            ->with('store')
                            ->get();

                        $html = '<div class="space-y-4">';

                        foreach ($blocks as $block) {
                            $html .= '
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <h4 class="font-semibold text-lg mb-2">' . e($block->store->name) . '</h4>
                                    <dl class="grid grid-cols-2 gap-2 text-sm">
                                        <dt class="font-medium">封鎖時間：</dt>
                                        <dd>' . $block->blocked_at->format('Y-m-d H:i') . '</dd>

                                        <dt class="font-medium">封鎖原因：</dt>
                                        <dd>' . e($block->reason) . '</dd>

                                        <dt class="font-medium">取消次數：</dt>
                                        <dd>' . $block->cancellation_count . '</dd>

                                        <dt class="font-medium">封鎖者：</dt>
                                        <dd>' . e($block->blocked_by) . '</dd>
                                    </dl>
                                    ' . ($block->notes ? '<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">備註：' . e($block->notes) . '</p>' : '') . '
                                </div>
                            ';
                        }

                        $html .= '</div>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('關閉'),
            ])
            ->toolbarActions([
                // Removed bulk actions for now
            ]);
    }
}
