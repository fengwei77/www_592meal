<?php

namespace App\Filament\Resources\Menu;

use App\Filament\Resources\Menu\MenuItemResource\Pages;
use App\Filament\Traits\HasResourcePermissions;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MenuItemResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = MenuItem::class;

    protected static string $viewPermission = 'view_menu_items';

    // 隱藏在導航選單中（改由 StoreResource 的 RelationManager 管理）
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->hasRole('super_admin');

        return $schema
            ->components([
                // Super Admin 需要選擇店家
                Section::make('店家資訊')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('店家')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('選擇此餐點所屬的店家'),
                    ])
                    ->visible($isSuperAdmin),

                Section::make('基本資訊')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('分類')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->label('餐點名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('餐點描述')
                            ->rows(3),

                        Forms\Components\TextInput::make('price')
                            ->label('價格')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(1),
                    ])
                    ->columns(2),

                Section::make('餐點照片')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('menu-item-photos')
                            ->label('餐點照片')
                            ->collection('menu-item-photos')
                            ->multiple()
                            ->maxFiles(5)
                            ->image()
                            ->imageEditor()
                            ->reorderable()
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->helperText('最多上傳 5 張照片，單張最大 5MB')
                            ->columnSpanFull(),
                    ]),

                Section::make('狀態設定')
                    ->schema([
                        Forms\Components\TextInput::make('display_order')
                            ->label('顯示排序')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('上架')
                            ->default(true),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('推薦')
                            ->default(false),

                        Forms\Components\Toggle::make('is_sold_out')
                            ->label('已售完')
                            ->default(false),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['category', 'store', 'media'])
            )
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image_url')
                    ->label('照片')
                    ->size(60)
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('餐點名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分類')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('price')
                    ->label('價格')
                    ->money('TWD')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('推薦')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('上架')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_sold_out')
                    ->label('售完')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('分類')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('上架狀態')
                    ->placeholder('全部')
                    ->trueLabel('已上架')
                    ->falseLabel('已下架'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('推薦')
                    ->placeholder('全部')
                    ->trueLabel('推薦')
                    ->falseLabel('一般'),

                Tables\Filters\TernaryFilter::make('is_sold_out')
                    ->label('售完')
                    ->placeholder('全部')
                    ->trueLabel('已售完')
                    ->falseLabel('供應中'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),

                    Actions\BulkAction::make('toggleActive')
                        ->label('批次上架/下架')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records, $data) {
                            $records->each->update(['is_active' => !$records->first()->is_active]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }

    /**
     * 權限控制：店家只能管理自己的餐點
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && $user->hasRole('super_admin')) {
            return $query;
        }

        if ($user && $user->hasRole('Store Owner')) {
            $storeIds = \App\Models\Store::where('user_id', $user->id)->pluck('id');
            return $query->whereIn('store_id', $storeIds);
        }

        return $query->whereRaw('1 = 0');
    }
}
