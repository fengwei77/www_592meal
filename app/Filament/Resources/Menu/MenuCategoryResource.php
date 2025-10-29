<?php

namespace App\Filament\Resources\Menu;

use App\Filament\Resources\Menu\MenuCategoryResource\Pages;
use App\Filament\Traits\HasResourcePermissions;
use App\Models\MenuCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MenuCategoryResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = MenuCategory::class;

    protected static string $viewPermission = 'view_menu_categories';

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
                            ->helperText('選擇此分類所屬的店家'),
                    ])
                    ->visible($isSuperAdmin),

                Section::make('基本資訊')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('分類名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('分類描述')
                            ->rows(3),

                        Forms\Components\TextInput::make('icon')
                            ->label('圖示 (emoji)')
                            ->maxLength(255)
                            ->placeholder('🍚'),
                    ])
                    ->columns(2),

                Section::make('排序與狀態')
                    ->schema([
                        Forms\Components\TextInput::make('display_order')
                            ->label('顯示排序')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('啟用')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['store', 'menuItems'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('icon')
                    ->label('圖示'),

                Tables\Columns\TextColumn::make('name')
                    ->label('分類名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('店家')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('menuItems_count')
                    ->label('餐點數')
                    ->counts('menuItems')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('狀態')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('排序')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('啟用狀態')
                    ->placeholder('全部')
                    ->trueLabel('已啟用')
                    ->falseLabel('已停用'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('刪除分類')
                    ->modalDescription('確定要刪除這個分類嗎？此操作無法復原。'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('display_order');
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
            'index' => Pages\ListMenuCategories::route('/'),
            'create' => Pages\CreateMenuCategory::route('/create'),
            'edit' => Pages\EditMenuCategory::route('/{record}/edit'),
        ];
    }

    /**
     * 權限控制：店家只能管理自己的分類
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
