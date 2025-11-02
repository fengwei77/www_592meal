<?php

namespace App\Filament\Resources\StoreCustomerBlocks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StoreCustomerBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                TextInput::make('line_user_id')
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                TextInput::make('reason')
                    ->required()
                    ->default('exceed_cancellation_limit'),
                TextInput::make('cancellation_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('blocked_at')
                    ->required(),
                TextInput::make('blocked_by')
                    ->required()
                    ->default('system'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
