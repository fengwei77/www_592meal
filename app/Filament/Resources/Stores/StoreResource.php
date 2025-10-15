<?php

namespace App\Filament\Resources\Stores;

use App\Filament\Resources\Stores\Pages\CreateStore;
use App\Filament\Resources\Stores\Pages\EditStore;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Models\Store;
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

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = '店家管理';

    protected static ?string $modelLabel = '店家';

    protected static ?string $pluralModelLabel = '店家';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 基本資訊區塊
                Section::make('基本資訊')
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
                    ->columns(2),

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
                    ->columns(2)
                    ->collapsible(),

                // 營業時間區塊
                Section::make('營業時間')
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
                    ])
                    ->collapsible(),

                // 圖片區塊
                Section::make('店家圖片')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('店家 Logo')
                            ->image()
                            ->directory('stores/logos')
                            ->visibility('public')
                            ->maxSize(2048),

                        Forms\Components\FileUpload::make('cover_image_url')
                            ->label('封面圖片')
                            ->image()
                            ->directory('stores/covers')
                            ->visibility('public')
                            ->maxSize(4096),
                    ])
                    ->columns(2)
                    ->collapsible(),

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
                    ->columns(2)
                    ->collapsible(),

                // 設定區塊
                Section::make('設定')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('啟用店家')
                            ->default(true)
                            ->helperText('關閉後店家將不會顯示給客戶'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image_url')
                    ->label('圖片')
                    ->size(60)
                    ->circular()
                    ->defaultImageUrl(url('/images/default-store.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('店家名稱')
                    ->searchable()
                    ->sortable(),

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'edit' => EditStore::route('/{record}/edit'),
        ];
    }

    /**
     * 權限控制：店家只能管理自己的店家
     */
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        // Super Admin 可以查看所有店家
        if ($user && $user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 可以查看自己的店家
        return $user && $user->hasRole('Store Owner');
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        // Super Admin 可以建立店家
        if ($user && $user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 可以建立店家（限制數量）
        if ($user && $user->hasRole('Store Owner')) {
            $storeCount = Store::where('user_id', $user->id)->count();
            return $storeCount < 3; // 限制最多 3 個店家
        }

        return false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Super Admin 可以編輯所有店家
        if ($user && $user->hasRole('Super Admin')) {
            return true;
        }

        // Store Owner 只能編輯自己的店家
        return $user && $record->isOwnedBy($user);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        // Super Admin 可以刪除所有店家
        if ($user && $user->hasRole('Super Admin')) {
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
        if ($user && $user->hasRole('Super Admin')) {
            return $query;
        }

        // Store Owner 只能看到自己的店家
        if ($user && $user->hasRole('Store Owner')) {
            return $query->where('user_id', $user->id);
        }

        // 其他角色看不到任何店家
        return $query->whereRaw('1 = 0');
    }
}
