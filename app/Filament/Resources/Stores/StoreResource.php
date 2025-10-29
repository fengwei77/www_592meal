<?php

namespace App\Filament\Resources\Stores;

use App\Filament\Resources\Stores\Pages\CreateStore;
use App\Filament\Resources\Stores\Pages\EditStore;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Filament\Resources\Stores\Pages\ManageStoreMenu;
use App\Filament\Traits\HasResourcePermissions;
use App\Models\Store;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StoreResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Store::class;

    protected static string $viewPermission = 'view_store';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = '店家管理';

    protected static ?string $modelLabel = '店家';

    protected static ?string $pluralModelLabel = '店家';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 使用 Grid 來讓每個 Section 佔據一整列，在所有螢幕尺寸都垂直顯示
                Grid::make(1)
                    ->extraAttributes(['class' => 'w-full max-w-none'])
                    ->schema([
                        // 所有 Section 在這裡都會垂直排列，佔據全寬
                        // 基本資訊區塊
                        Section::make('基本資訊')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('店家名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('store_slug_name')
                            ->label('店家網址代碼 (Slug)')
                            ->helperText('自訂店家網址代碼，例如：my-restaurant。如留空則自動生成格式：s000001')
                            ->placeholder('留空自動生成')
                            ->dehydrateStateUsing(function ($state, $get, $record) {
                                // 返回處理後的值，不使用模型mutator
                                return $state;
                            })
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // 實時預覽 URL
                                $baseUrl = config('app.url');
                                $slug = !empty($state) ? strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $state)) : 's000001';
                                $set('url_preview', "{$baseUrl}/{$slug}");
                            })
                            ->maxLength(100)
                            ->reactive(),

                        Forms\Components\Placeholder::make('url_preview')
                            ->label('URL 預覽')
                            ->content(function ($get, $record) {
                                $baseUrl = config('app.url');

                                if (!empty($get('store_slug_name'))) {
                                    // 用戶有輸入時，顯示清理後的slug
                                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $get('store_slug_name')));
                                } elseif ($record) {
                                    // 編輯模式且為空時，顯示預設格式
                                    $slug = 's' . str_pad($record->id, 6, '0', STR_PAD_LEFT);
                                } else {
                                    // 新增模式且為空時，顯示預設範例
                                    $slug = 's000001';
                                }

                                return "{$baseUrl}/{$slug}";
                            })
                            ->columnSpanFull(),

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
                            ->default('other')
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
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),  // 預設縮合

                // 地理位置區塊
                Section::make('地理位置')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('緯度')
                            ->numeric()
                            ->step(0.00000001),

                        Forms\Components\TextInput::make('longitude')
                            ->label('經度')
                            ->numeric()
                            ->step(0.00000001),

                        Forms\Components\Placeholder::make('location_hint')
                            ->label('提示')
                            ->content('您可以通過 Google Maps 取得準確的經緯度坐標'),
                    ])
                    ->columns(1)  // 改為單層佈局，類似手機版
                    ->collapsible()
                    ->collapsed(),  // 預設縮合

                // 營業時間區塊
                Section::make('營業時間')
                    ->schema([
                        Forms\Components\Repeater::make('business_hours_repeater')
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
                                    ->distinct()
                                    ->disableOptionWhen(function ($value, $state, $get) {
                                        // 防止重複選擇同一天
                                        $days = collect($get('../../business_hours_repeater'))
                                            ->pluck('day')
                                            ->filter();
                                        return $days->contains($value) && $value !== $state;
                                    }),

                                Forms\Components\Toggle::make('is_open')
                                    ->label('營業')
                                    ->default(true)
                                    ->reactive(),

                                Forms\Components\TimePicker::make('open_time')
                                    ->label('開始時間')
                                    ->seconds(false)
                                    ->native(false)
                                    ->displayFormat('H:i')
                                    ->requiredIf('is_open', true)
                                    ->default('09:00')
                                    ->visible(fn ($get) => $get('is_open'))
                                    ->placeholder('選擇時間'),

                                Forms\Components\TimePicker::make('close_time')
                                    ->label('結束時間')
                                    ->seconds(false)
                                    ->native(false)
                                    ->displayFormat('H:i')
                                    ->requiredIf('is_open', true)
                                    ->default('22:00')
                                    ->visible(fn ($get) => $get('is_open'))
                                    ->after('open_time')
                                    ->placeholder('選擇時間'),
                            ])
                            ->columns(1)  // 改為單欄，類似手機版
                            ->defaultItems(0)
                            ->addActionLabel('新增營業日')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                isset($state['day'])
                                    ? match($state['day']) {
                                        'monday' => '星期一',
                                        'tuesday' => '星期二',
                                        'wednesday' => '星期三',
                                        'thursday' => '星期四',
                                        'friday' => '星期五',
                                        'saturday' => '星期六',
                                        'sunday' => '星期日',
                                        default => '未設定',
                                    } . ($state['is_open'] ?? true
                                        ? " ({$state['open_time']} - {$state['close_time']})"
                                        : ' (公休)')
                                    : null
                            )
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\Repeater $component, $state, $record) {
                                // 從 business_hours JSON 載入資料到 repeater
                                if ($record && $record->business_hours) {
                                    $items = [];
                                    foreach ($record->business_hours as $day => $hours) {
                                        $items[] = [
                                            'day' => $day,
                                            'is_open' => $hours['is_open'] ?? true,
                                            'open_time' => $hours['open_time'] ?? $hours['opens_at'] ?? '09:00',
                                            'close_time' => $hours['close_time'] ?? $hours['closes_at'] ?? '22:00',
                                        ];
                                    }
                                    $component->state($items);
                                }
                            }),

                        Forms\Components\Hidden::make('business_hours')
                            ->dehydrateStateUsing(function ($state, $get) {
                                // 從 repeater 轉換回 JSON 格式
                                $repeaterData = $get('business_hours_repeater') ?? [];
                                $result = [];
                                foreach ($repeaterData as $item) {
                                    if (isset($item['day'])) {
                                        $result[$item['day']] = [
                                            'is_open' => $item['is_open'] ?? true,
                                            'open_time' => $item['open_time'] ?? '09:00',
                                            'close_time' => $item['close_time'] ?? '22:00',
                                        ];
                                    }
                                }
                                return $result;
                            }),

                        Forms\Components\Placeholder::make('business_hours_hint')
                            ->label('使用提示')
                            ->content('請為每個營業日設定營業時間。如果某天公休，請將「營業」開關關閉。'),
                    ])
                    ->description('設定每週營業時間')
                    ->collapsible()
                    ->collapsed(),  // 預設縮合

                // 特殊節日營業時間區塊
                Section::make('特殊節日營業時間')
                    ->schema([
                        Forms\Components\Repeater::make('special_hours')
                            ->label('特殊日期設定')
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('日期')
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->minDate(now()->startOfDay()),

                                Forms\Components\TextInput::make('name')
                                    ->label('節日名稱')
                                    ->placeholder('例如：春節、中秋節')
                                    ->maxLength(100),

                                Forms\Components\Toggle::make('is_open')
                                    ->label('營業')
                                    ->default(false)
                                    ->reactive()
                                    ->helperText('關閉表示該日公休'),

                                Forms\Components\TimePicker::make('open_time')
                                    ->label('開始時間')
                                    ->seconds(false)
                                    ->native(false)
                                    ->displayFormat('H:i')
                                    ->default('09:00')
                                    ->visible(fn ($get) => $get('is_open'))
                                    ->placeholder('選擇時間'),

                                Forms\Components\TimePicker::make('close_time')
                                    ->label('結束時間')
                                    ->seconds(false)
                                    ->native(false)
                                    ->displayFormat('H:i')
                                    ->default('22:00')
                                    ->visible(fn ($get) => $get('is_open'))
                                    ->after('open_time')
                                    ->placeholder('選擇時間'),
                            ])
                            ->columns(1)  // 改為單欄，類似手機版
                            ->defaultItems(0)
                            ->addActionLabel('新增特殊日期')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                isset($state['date'])
                                    ? ($state['date'] ?? '未設定日期')
                                      . (isset($state['name']) ? " - {$state['name']}" : '')
                                      . ($state['is_open'] ?? false
                                          ? " (營業 {$state['open_time']} - {$state['close_time']})"
                                          : ' (公休)')
                                    : null
                            )
                            ->orderColumn(false),

                        Forms\Components\Placeholder::make('special_hours_hint')
                            ->label('使用提示')
                            ->content('設定特殊節日（如春節、中秋節等）的營業時間。特殊日期的設定會覆蓋當天的正常營業時間。'),
                    ])
                    ->description('設定特殊節日、國定假日等日期的營業時間')
                    ->collapsible()
                    ->collapsed(),  // 預設縮合

                // 服務模式區塊
                Section::make('服務模式')
                    ->schema([
                        Forms\Components\Radio::make('service_mode')
                            ->label('服務模式')
                            ->options([
                                'pickup' => '店址取餐',
                                'onsite' => '駐點服務',
                                'hybrid' => '混合模式',
                            ])
                            ->descriptions([
                                'pickup' => '客戶到店取餐',
                                'onsite' => '商家到企業地點服務',
                                'hybrid' => '同時支援店取與駐點',
                            ])
                            ->default('pickup')
                            ->required()
                            ->inline()
                            ->inlineLabel(false),
                    ])
                    ->description('選擇店家的服務方式')
                    ->collapsible()
                    ->collapsed(),  // 預設縮合

                // 圖片區塊
                Section::make('店家圖片')
                    ->schema([
                        Forms\Components\FileUpload::make('store_logo')
                            ->label('店家 Logo')
                            ->disk('public')
                            ->directory('store-logos')
                            ->image()
                            ->imageEditor()
                            ->maxSize(10240) // 改為 10MB 以匹配 Livewire 配置
                            ->helperText('建議尺寸：正方形，最大 10MB')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->visibility('public'),

                        Forms\Components\FileUpload::make('store_cover_image')
                            ->label('封面圖片')
                            ->disk('public')
                            ->directory('store-covers')
                            ->image()
                            ->imageEditor()
                            ->maxSize(10240) // 改為 10MB 以匹配 Livewire 配置
                            ->helperText('建議尺寸：橫向長方形，最大 10MB')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->visibility('public'),

                        Forms\Components\FileUpload::make('store_photos')
                            ->label('商家照片')
                            ->disk('public')
                            ->directory('store-photos')
                            ->multiple()
                            ->maxFiles(5)
                            ->image()
                            ->imageEditor()
                            ->reorderable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240) // 改為 10MB 以匹配 Livewire 配置
                            ->helperText('最多上傳 5 張照片，單張最大 10MB，支援 JPG、PNG、WEBP 格式')
                            ->columnSpanFull()
                            ->visibility('public')
                            ->formatStateUsing(function ($state) {
                                // 確保狀態格式正確
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    if (is_array($decoded)) {
                                        return $decoded;
                                    }
                                }
                                return is_array($state) ? $state : [];
                            }),
                    ])
                    ->columns(1)  // 改為單列佈局
                    ->collapsible()
                    ->collapsed(),

                // 社群媒體區塊
                Section::make('社群媒體')
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
                    ->columns(1)  // 改為單列佈局
                    ->collapsible()
                    ->collapsed(),  // 預設縮合

                // 設定區塊
                Section::make('設定')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('啟用店家')
                            ->default(true)
                            ->helperText('關閉後店家將不會顯示給客戶'),

                        Forms\Components\TextInput::make('current_staff_password_display')
                            ->label('🔑 目前店員密碼')
                            ->default(fn ($record) => $record?->staff_password ?? '未設定')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null)
                            ->helperText('目前設定的店員登入密碼，顯示在此方便查看'),

                        Forms\Components\TextInput::make('staff_password')
                            ->label('修改店員密碼')
                            ->maxLength(255)
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->helperText('若要修改密碼請在此輸入新密碼，留空表示不修改')
                            ->placeholder('輸入新密碼以修改'),
                    ])
                    ->collapsible()
                    ->collapsed(),  // 預設縮合
                    ])  // 關閉 Grid schema
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('store_logo')
                    ->label('Logo')
                    ->getStateUsing(function (Store $record): string {
                        if ($record->store_logo) {
                            // 使用 CMS 域名生成完整 URL
                            return config('app.admin_url', 'https://cms.oh592meal.test') . '/storage/' . $record->store_logo;
                        }
                        return config('app.admin_url', 'https://cms.oh592meal.test') . '/images/default-store.svg';
                    })
                    ->size(60)
                    ->circular()
                    ->defaultImageUrl(config('app.admin_url', 'https://cms.oh592meal.test') . '/images/default-store.svg'),

                Tables\Columns\TextColumn::make('name')
                    ->label('店家名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('store_slug')
                    ->label('網址代碼')
                    ->copyable()
                    ->copyMessage('已複製')
                    ->copyMessageDuration(1500)
                    ->getStateUsing(fn (Store $record): string => $record->store_slug)
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('store_url')
                    ->label('店家網址')
                    ->copyable()
                    ->copyMessage('已複製網址')
                    ->copyMessageDuration(1500)
                    ->url(fn (Store $record): string => $record->store_url)
                    ->openUrlInNewTab()
                    ->limit(30),

                Tables\Columns\TextColumn::make('store_type_label')
                    ->label('類型')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '餐廳' => 'primary',
                        '咖啡廳' => 'success',
                        '小吃店' => 'warning',
                        '酒吧' => 'danger',
                        '烘焙店' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('service_mode_label')
                    ->label('服務模式')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '店址取餐' => 'success',
                        '駐點服務' => 'warning',
                        '混合模式' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label('電話')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('店家老闆')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('狀態')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_type')
                    ->label('店家類型')
                    ->options([
                        'restaurant' => '餐廳',
                        'cafe' => '咖啡廳',
                        'snack' => '小吃店',
                        'bar' => '酒吧',
                        'bakery' => '烘焙店',
                        'other' => '其他',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('啟用狀態')
                    ->placeholder('全部')
                    ->trueLabel('已啟用')
                    ->falseLabel('已停用'),

                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('店家老闆')
                    ->searchable()
                    ->relationship('owner', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} ({$record->email})"),
            ])
            ->actions([
                // 訂單管理按鈕
                Actions\Action::make('manage_orders')
                    ->label('訂單管理')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('warning')
                    ->url(fn (Store $record): string =>
                        config('app.admin_url') . '/store/' . $record->store_slug . '/manage/orders'
                    )
                    ->openUrlInNewTab()
                    ->tooltip('管理此店家的訂單'),

                // 菜單管理按鈕
                Actions\Action::make('manage_menu')
                    ->label('菜單')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Store $record): string =>
                        static::getUrl('manage-menu', ['record' => $record->id])
                    )
                    ->tooltip('管理此店家的菜單'),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('刪除店家')
                    ->modalDescription('確定要刪除這個店家嗎？此操作無法復原。'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('刪除選中的店家')
                        ->modalDescription('確定要刪除選中的店家嗎？此操作無法復原。'),
                ]),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // 移除菜單相關的關聯管理器
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'edit' => EditStore::route('/{record}/edit'),
            'manage-menu' => ManageStoreMenu::route('/{record}/menu'),
        ];
    }

    /**
     * 權限控制：店家只能建立自己的店家
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        // Super Admin 可以建立店家
        if ($user && $user->hasRole('super_admin')) {
            return true;
        }

        // Store Owner 可以建立店家（限制數量）
        if ($user && $user->hasRole('store_owner')) {
            $storeCount = Store::where('user_id', $user->id)->count();
            return $storeCount < 3; // 限制最多 3 個店家
        }

        return false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Super Admin 可以編輯所有店家
        if ($user && $user->hasRole('super_admin')) {
            return true;
        }

        // Store Owner 只能編輯自己的店家
        return $user && $record->isOwnedBy($user);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        // Super Admin 可以刪除所有店家
        if ($user && $user->hasRole('super_admin')) {
            return true;
        }

        // Store Owner 只能刪除自己的店家
        return $user && $record->isOwnedBy($user);
    }

    /**
     * 查詢範圍：店家只能看到自己的店家
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Super Admin 可以看到所有店家
        if ($user && $user->hasRole('super_admin')) {
            return $query;
        }

        // Store Owner 只能看到自己的店家
        if ($user && $user->hasRole('store_owner')) {
            return $query->where('user_id', $user->id);
        }

        // 其他角色看不到任何店家
        return $query->whereRaw('1 = 0');
    }
}
