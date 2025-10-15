<?php

namespace App\Filament\Pages;

use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class StoreSettings extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.store-settings';

    protected static ?string $navigationLabel = '店家設定';

    protected static ?string $title = '店家設定';

    // protected static ?string $navigationGroup = 'Store Management';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public ?Store $store;

    public function mount(): void
    {
        $user = Auth::user();
        $this->store = $user->stores()->firstOrFail();

        $this->form->fill($this->store->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // 基本資訊區塊
                Forms\Components\Section::make('基本資訊')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('店家名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('店家描述')
                            ->rows(3),

                        Forms\Components\Select::make('store_type')
                            ->label('店家類型')
                            ->options([
                                'restaurant' => '餐廳',
                                'cafe' => '咖啡廳',
                                'snack' => '小吃店',
                                'bar' => '酒吧',
                                'bakery' => '烘焙店',
                                'other' => '其他',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('phone')
                            ->label('聯絡電話')
                            ->tel()
                            ->required()
                            ->maxLength(20),

                        Forms\Components\Textarea::make('address')
                            ->label('店家地址')
                            ->required()
                            ->rows(2),
                    ])
                    ->columns(2),

                // 營業時間區塊
                Forms\Components\Section::make('營業時間')
                    ->schema([
                        Forms\Components\Repeater::make('business_hours')
                            ->label('每週營業時間')
                            ->schema([
                                Forms\Components\Select::make('day')
                                    ->label('星期')
                                    ->options([
                                        'monday' => '星期一',
                                        'tuesday' => '星期二',
                                        'wednesday' => '星期三',
                                        'thursday' => '星期四',
                                        'friday' => '星期五',
                                        'saturday' => '星期六',
                                        'sunday' => '星期日',
                                    ])
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\Toggle::make('is_open')
                                    ->label('營業')
                                    ->default(true)
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\TimePicker::make('opens_at')
                                    ->label('開始時間')
                                    ->required(fn ($get) => $get('is_open'))
                                    ->columnSpan(1),

                                Forms\Components\TimePicker::make('closes_at')
                                    ->label('結束時間')
                                    ->required(fn ($get) => $get('is_open'))
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->collapsible(),
                    ]),

                // 圖片區塊
                Forms\Components\Section::make('店家圖片')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('店家 Logo')
                            ->image()
                            ->imageEditor()
                            ->directory('stores/logos')
                            ->visibility('public')
                            ->maxSize(2048),

                        Forms\Components\FileUpload::make('cover_image_url')
                            ->label('封面圖片')
                            ->image()
                            ->imageEditor()
                            ->directory('stores/covers')
                            ->visibility('public')
                            ->maxSize(4096),
                    ])
                    ->columns(2),

                // 地理位置區塊
                Forms\Components\Section::make('地理位置')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('緯度')
                            ->numeric()
                            ->step(0.00000001)
                            ->decimalPlaces(8),

                        Forms\Components\TextInput::make('longitude')
                            ->label('經度')
                            ->numeric()
                            ->step(0.00000001)
                            ->decimalPlaces(8),

                        Forms\Components\Placeholder::make('location_helper')
                            ->label('快速定位')
                            ->content('點擊下方按鈕自動獲取地址的經緯度坐標')
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Action::make('get_coordinates')
                                ->label('自動獲取坐標')
                                ->icon('heroicon-o-map-pin')
                                ->action(function () {
                                    $this->getCoordinatesFromAddress();
                                }),
                        ]),
                    ])
                    ->columns(2),

                // 社群媒體區塊
                Forms\Components\Section::make('社群媒體')
                    ->schema([
                        Forms\Components\TextInput::make('social_links.facebook')
                            ->label('Facebook')
                            ->url()
                            ->prefix('https://facebook.com/'),

                        Forms\Components\TextInput::make('social_links.instagram')
                            ->label('Instagram')
                            ->url()
                            ->prefix('https://instagram.com/'),

                        Forms\Components\TextInput::make('social_links.line')
                            ->label('LINE')
                            ->helperText('LINE ID 或連結'),

                        Forms\Components\TextInput::make('social_links.website')
                            ->label('官方網站')
                            ->url(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->store->update($data);

        Notification::make()
            ->success()
            ->title('店家設定已更新')
            ->body('您的店家資訊已成功更新。')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('儲存變更')
                ->submit('save')
                ->icon('heroicon-o-check'),
        ];
    }

    /**
     * 從地址獲取經緯度坐標（模擬功能，實際使用時需要整合地圖 API）
     */
    private function getCoordinatesFromAddress(): void
    {
        // 這裡是模擬功能，實際使用時需要整合 Google Maps API 或其他地理編碼服務
        Notification::make()
            ->info()
            ->title('坐標獲取功能')
            ->body('此功能需要整合地圖 API，目前為模擬版本。請手動輸入坐標。')
            ->send();
    }

    /**
     * 靜態方法：檢查用戶是否有權限訪問此頁面
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        // Super Admin 和 Store Owner 可以訪問
        return $user && ($user->hasRole('Super Admin') || $user->hasRole('Store Owner'));
    }

    /**
     * 只對有店家的 Store Owner 顯示此頁面
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin 總是能看到
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 只有在有店家時才顯示
        return $user->hasRole('Store Owner') && $user->stores()->exists();
    }
}