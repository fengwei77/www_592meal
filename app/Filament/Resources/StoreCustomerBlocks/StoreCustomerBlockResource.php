<?php

namespace App\Filament\Resources\StoreCustomerBlocks;

use App\Filament\Resources\StoreCustomerBlocks\Pages\CreateStoreCustomerBlock;
use App\Filament\Resources\StoreCustomerBlocks\Pages\EditStoreCustomerBlock;
use App\Filament\Resources\StoreCustomerBlocks\Pages\ListStoreCustomerBlocks;
use App\Filament\Resources\StoreCustomerBlocks\Pages\ViewStoreCustomerBlock;
use App\Filament\Resources\StoreCustomerBlocks\Schemas\StoreCustomerBlockForm;
use App\Filament\Resources\StoreCustomerBlocks\Schemas\StoreCustomerBlockInfolist;
use App\Filament\Resources\StoreCustomerBlocks\Tables\StoreCustomerBlocksTable;
use App\Models\StoreCustomerBlock;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StoreCustomerBlockResource extends Resource
{
    protected static ?string $model = StoreCustomerBlock::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = '客戶封鎖管理';

    protected static ?string $modelLabel = '客戶封鎖';

    protected static ?string $pluralModelLabel = '客戶封鎖';

    protected static string | \UnitEnum | null $navigationGroup = '店家管理';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return StoreCustomerBlockForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StoreCustomerBlockInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreCustomerBlocksTable::configure($table);
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
            'index' => ListStoreCustomerBlocks::route('/'),
            'create' => CreateStoreCustomerBlock::route('/create'),
            'view' => ViewStoreCustomerBlock::route('/{record}'),
            'edit' => EditStoreCustomerBlock::route('/{record}/edit'),
        ];
    }
}
