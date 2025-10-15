<?php

namespace App\Filament\Resources\Stores\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('subdomain')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('settings')
                    ->required()
                    ->default('{}'),
                TextInput::make('line_pay_settings')
                    ->required()
                    ->default('{}'),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('store_type')
                    ->required()
                    ->default('other'),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                TextInput::make('business_hours'),
                TextInput::make('logo_url')
                    ->url(),
                FileUpload::make('cover_image_url')
                    ->image(),
                TextInput::make('social_links'),
            ]);
    }
}
