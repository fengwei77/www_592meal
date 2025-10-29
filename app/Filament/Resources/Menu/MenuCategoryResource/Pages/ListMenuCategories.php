<?php

namespace App\Filament\Resources\Menu\MenuCategoryResource\Pages;

use App\Filament\Resources\Menu\MenuCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenuCategories extends ListRecords
{
    protected static string $resource = MenuCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
