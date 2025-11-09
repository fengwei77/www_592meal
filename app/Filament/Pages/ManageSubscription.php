<?php

namespace App\Filament\Pages;

use App\Services\SubscriptionService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Action;

class ManageSubscription extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public ?int $months = null;

    public ?int $total_amount = 0;

    public ?string $notes = null;

    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected string $view = 'filament.pages.manage-subscription';

    protected static ?string $title = '訂閱管理';

    protected static ?string $navigationLabel = '訂閱服務';

    protected static ?int $navigationSort = 2;

    // 只允許老闆角色存取
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('store_owner');
    }

    public function mount(): void
    {
        try {
            $this->form->fill([
                'months' => null,
                'notes' => '',
                'total_amount' => 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mount ManageSubscription page', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // 訂閱狀態顯示
                \Filament\Schemas\Components\Section::make('目前訂閱狀態')
                    ->description('您目前的訂閱服務狀態')
                    ->schema([
                        Forms\Components\Placeholder::make('subscription_status')
                            ->label('訂閱狀態')
                            ->content(function () {
                                try {
                                    $subscriptionStats = $this->getSubscriptionStats();
                                    $status = $subscriptionStats['subscription_status'] ?? 'none';

                                    $statusText = match($status) {
                                        'trial' => '試用期中',
                                        'active' => '訂閱有效',
                                        'expired' => '訂閱已過期',
                                        default => '無訂閱'
                                    };

                                    return (string) $statusText;
                                } catch (\Exception $e) {
                                    return '無訂閱';
                                }
                            }),

                        Forms\Components\Placeholder::make('remaining_days')
                            ->label('剩餘天數')
                            ->content(function () {
                                $stats = $this->getSubscriptionStats();
                                return (string) ($stats['remaining_days'] ?? 0) . ' 天';
                            }),

                        Forms\Components\Placeholder::make('expiry_date')
                            ->label('到期日期')
                            ->content(function () {
                                $stats = $this->getSubscriptionStats();
                                return $stats['expiry_date'] ?? '-';
                            }),

                        Forms\Components\Placeholder::make('total_orders')
                            ->label('總訂單數')
                            ->content(function () {
                                $stats = $this->getSubscriptionStats();
                                return (string) ($stats['total_orders'] ?? 0) . ' 筆';
                            }),

                        Forms\Components\Placeholder::make('total_amount')
                            ->label('總消費金額')
                            ->content(function () {
                                $stats = $this->getSubscriptionStats();
                                return 'NT$ ' . number_format($stats['total_amount'] ?? 0);
                            }),
                    ])
                    ->columns(3),

                // 訂閱表單
                \Filament\Schemas\Components\Section::make('建立訂單紀錄')
                    ->description('選擇您的訂閱方案並立即付款')
                    ->schema([
                        Forms\Components\Select::make('months')
                            ->label('訂閱月數')
                            ->options(function () {
                                $options = [];
                                $pricePerMonth = 50;

                                for ($i = 1; $i <= 18; $i++) {
                                    $totalPrice = $i * $pricePerMonth;
                                    $label = "{$i}個月 - NT${totalPrice}";

                                    // 添加特別標籤給常見選項
                                    switch ($i) {
                                        case 1:
                                            $label .= " (月付)";
                                            break;
                                        case 3:
                                            $label .= " (季度)";
                                            break;
                                        case 6:
                                            $label .= " (半年)";
                                            break;
                                        case 12:
                                            $label .= " (年付)";
                                            break;
                                        case 18:
                                            $label .= " (最優惠)";
                                            break;
                                    }

                                    $options[$i] = $label;
                                }

                                return $options;
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $months = (int) $state ?: 0;
                                $total = $months * 50;
                                $set('total_amount', $total);
                            })
                            ->default(null),

                        Forms\Components\Placeholder::make('total_amount_display')
                            ->label('總金額')
                            ->content(function ($get) {
                                $months = (int) $get('months') ?: 0;
                                $total = $months * 50;
                                return 'NT$ ' . number_format($total);
                            })
                            ->extraAttributes(['class' => 'text-lg font-semibold text-gray-900']),

                        Forms\Components\Hidden::make('total_amount')
                            ->default(0),

                        
                        Forms\Components\Textarea::make('notes')
                            ->label('備註')
                            ->rows(3)
                            ->placeholder('可輸入特殊需求或備註說明'),
                    ])
                    ->columns(2),
            ]);
    }

    public function getSubscriptionStats(): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->getDefaultSubscriptionStats();
            }

            $subscriptionService = app(SubscriptionService::class);
            $stats = $subscriptionService->getUserSubscriptionStats($user);

            // Ensure all values are properly formatted
            return [
                'subscription_status' => $stats['subscription_status'] ?? 'none',
                'remaining_days' => (int) ($stats['remaining_days'] ?? 0),
                'expiry_date' => $stats['expiry_date'] ?? null,
                'total_orders' => (int) ($stats['total_orders'] ?? 0),
                'total_amount' => (int) ($stats['total_amount'] ?? 0),
                'paid_orders' => (int) ($stats['paid_orders'] ?? 0),
                'total_months' => (int) ($stats['total_months'] ?? 0),
                'subscription_label' => $stats['subscription_label'] ?? '無訂閱',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get subscription stats', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultSubscriptionStats();
        }
    }

    private function getDefaultSubscriptionStats(): array
    {
        return [
            'subscription_status' => 'none',
            'remaining_days' => 0,
            'expiry_date' => null,
            'total_orders' => 0,
            'total_amount' => 0,
            'paid_orders' => 0,
            'total_months' => 0,
            'subscription_label' => '無訂閱',
        ];
    }

    public function getStatusBadge(): string
    {
        $subscriptionStats = $this->getSubscriptionStats();
        $status = $subscriptionStats['subscription_status'];

        $statusText = match($status) {
            'trial' => '試用期中',
            'active' => '訂閱有效',
            'expired' => '訂閱已過期',
            default => '無訂閱'
        };

        $color = match($status) {
            'trial' => 'primary',
            'active' => 'success',
            'expired' => 'danger',
            default => 'gray'
        };

        return "<span class='inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{$color}-100 text-{$color}-800'>{$statusText}</span>";
    }

    protected function getViewData(): array
    {
        return [
            'subscriptionStats' => $this->getSubscriptionStats(),
            'user' => Auth::user(),
        ];
    }

    // 創建訂單紀錄
    public function createSubscriptionOrder(array $data): array
    {
        try {
            $user = Auth::user();
            $subscriptionService = app(SubscriptionService::class);

            // 檢查用戶是否可以建立新訂單（保留最後3筆待付款訂單）
            if (!$subscriptionService->canCreateNewOrder($user)) {
                return [
                    'success' => false,
                    'message' => '您已有3筆待付款訂單，請先完成部分訂單的付款或等待訂單過期後再建立新訂單',
                ];
            }

            // 建立訂單
            $result = $subscriptionService->createSubscriptionOrder($user, $data['months'], $data['notes'] ?? null);

            if ($result['success']) {
                return [
                    'success' => true,
                    'order' => $result['order'],
                    'redirect_url' => route('subscription.confirm', $result['order'])
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to create subscription from admin page', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '訂單建立失敗，請稍後再試或聯繫客服。'
            ];
        }
    }

    
    public function createOrder(): void
    {
        try {
            $user = Auth::user();
            $subscriptionService = app(SubscriptionService::class);
            // 嘗試多種方法獲取表單數據
            try {
                $data = $this->form->getState();
            } catch (\Exception $e) {
                Log::error('Failed to get form state', ['error' => $e->getMessage()]);
                $data = [];
            }

            // 如果 getState() 沒有返回數據，嘗試直接從組件獲取
            if (empty($data) || !isset($data['months'])) {
                $data = $this->data ?? [];
            }

            // 調試：檢查表單數據
            Log::info('Form data received', [
                'data' => $data,
                'has_months' => isset($data['months']),
                'months_value' => $data['months'] ?? 'NOT_SET',
                'this_data' => $this->data ?? 'NO_DATA'
            ]);

            // 確保有月數數據
            if (!isset($data['months']) || empty($data['months'])) {
                // 如果還是沒有數據，使用默認值
                $data['months'] = 3;
                Log::warning('Using default months value', ['months' => 3]);
            }

            // 確保計算正確的總金額
            $months = (int) $data['months'];
            $data['total_amount'] = $months * 50;

            // 檢查用戶是否可以建立新訂單（保留最後3筆待付款訂單）
            if (!$subscriptionService->canCreateNewOrder($user)) {
                Notification::make()
                    ->warning()
                    ->title('待付款訂單已達上限')
                    ->body('您已有3筆待付款訂單，請先完成部分訂單的付款或等待訂單過期後再建立新訂單。')
                    ->persistent()
                    ->send();
                return;
            }

            // 檢查是否需要取消最舊的訂單
            $pendingOrders = $subscriptionService->getPendingOrders($user);
            if ($pendingOrders->count() >= 2) { // 如果已有2筆，新的會是第3筆
                // 不需要取消任何訂單，直接建立新訂單
                Notification::make()
                    ->info()
                    ->title('建立新訂單')
                    ->body('系統將為您建立新的訂單紀錄，您最多可保留3筆待付款訂單。')
                    ->send();
            }

            // 驗證月數範圍
            $months = (int) $data['months'];
            if ($months < 1 || $months > 18) {
                Notification::make()
                    ->danger()
                    ->title('訂閱月數錯誤')
                    ->body('訂閱月數必須在 1 到 18 個月之間')
                    ->send();
                return;
            }

            // 建立訂單
            $result = $subscriptionService->createSubscriptionOrder($user, $months, $data['notes'] ?? null);

            if (!$result['success']) {
                Notification::make()
                    ->danger()
                    ->title('訂單建立失敗')
                    ->body($result['message'])
                    ->persistent()
                    ->send();
                return;
            }

            // 建立前往付款確認頁面的連結
            $confirmUrl = route('subscription.confirm', $result['order']);

            Notification::make()
                ->success()
                ->title('訂單建立成功')
                ->body('訂單編號：' . $result['order']->order_number . PHP_EOL . PHP_EOL . '請點擊下方「前往付款」按鈕進行付款確認')
                ->actions([
                    \Filament\Actions\Action::make('前往付款')
                        ->url($confirmUrl)
                        ->button()
                        ->color('success')
                        ->openUrlInNewTab(), // 在新標籤頁開啟
                ])
                ->send();

            // 重新載入當前頁面
            $this->redirectRoute('filament.admin.pages.manage-subscription');

        } catch (\Exception $e) {
            Log::error('Failed to create subscription from admin page', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('系統錯誤')
                ->body('訂單建立失敗，請稍後再試或聯繫客服。')
                ->send();
        }
    }
}