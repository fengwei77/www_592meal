<?php

namespace App\Filament\Resources\StoreCustomerBlocks\Pages;

use App\Filament\Resources\StoreCustomerBlocks\StoreCustomerBlockResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreCustomerBlock extends ViewRecord
{
    protected static string $resource = StoreCustomerBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
