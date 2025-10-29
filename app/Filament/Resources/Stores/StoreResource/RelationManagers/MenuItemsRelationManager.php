<?php

namespace App\Filament\Resources\Stores\StoreResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class MenuItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'menuItems';

    protected static ?string $title = '菜單項目';

    protected static ?string $modelLabel = '菜單項目';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資訊')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('菜單類別')
                            ->relationship(
                                'category',
                                'name',
                                fn ($query) => $query->where('store_id', $this->ownerRecord->id)
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('分類名稱')
                                    ->required()
                                    ->maxLength(100),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $category = $this->ownerRecord->menuCategories()->create($data);
                                return $category->id;
                            }),

                        Forms\Components\TextInput::make('name')
                            ->label('項目名稱')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\Textarea::make('description')
                            ->label('項目描述')
                            ->maxLength(1000)
                            ->rows(3),

                        Forms\Components\TextInput::make('price')
                            ->label('價格')
                            ->required()
                            ->numeric()
                            ->prefix('NT$')
                            ->minValue(0),

                        Forms\Components\TextInput::make('display_order')
                            ->label('顯示順序')
                            ->numeric()
                            ->default(0)
                            ->helperText('數字越小越前面'),

                        Forms\Components\Toggle::make('is_available')
                            ->label('可供應')
                            ->default(true)
                            ->helperText('關閉後顧客無法點選此項目'),
                    ])
                    ->columns(2),

                Section::make('圖片')
                    ->schema([
                        Forms\Components\FileUpload::make('image_url')
                            ->label('項目圖片')
                            ->image()
                            ->directory('menu-items')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->imageEditor(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('圖片')
                    ->disk('public')
                    ->size(50)
                    ->defaultImageUrl(asset('images/default-food.svg')),

                Tables\Columns\TextColumn::make('name')
                    ->label('項目名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分類')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('price')
                    ->label('價格')
                    ->money('TWD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('順序')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('可供應')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('分類')
                    ->relationship(
                        'category',
                        'name',
                        fn ($query) => $query->where('store_id', $this->ownerRecord->id)
                    )
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('供應狀態')
                    ->placeholder('全部')
                    ->trueLabel('可供應')
                    ->falseLabel('停止供應'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // 自動填入店家 ID
                        $data['store_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order');
    }
}
