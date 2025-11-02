<?php

namespace App\Filament\Resources\StoreCustomerBlocks\Pages;

use App\Filament\Resources\StoreCustomerBlocks\StoreCustomerBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreCustomerBlocks extends ListRecords
{
    protected static string $resource = StoreCustomerBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
