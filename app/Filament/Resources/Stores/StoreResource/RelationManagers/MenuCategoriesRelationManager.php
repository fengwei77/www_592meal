<?php

namespace App\Filament\Resources\Stores\StoreResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class MenuCategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'menuCategories';

    protected static ?string $title = '菜單類別';

    protected static ?string $modelLabel = '菜單類別';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資訊')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('分類名稱')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('description')
                            ->label('分類描述')
                            ->maxLength(500)
                            ->rows(3),

                        Forms\Components\TextInput::make('display_order')
                            ->label('顯示順序')
                            ->numeric()
                            ->default(0)
                            ->helperText('數字越小越前面'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('啟用')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('分類名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('順序')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('啟用')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('menuItems_count')
                    ->label('項目數量')
                    ->counts('menuItems')
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
