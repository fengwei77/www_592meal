<?php

namespace App\Filament\Resources\StoreCustomerBlocks\Pages;

use App\Filament\Resources\StoreCustomerBlocks\StoreCustomerBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreCustomerBlock extends EditRecord
{
    protected static string $resource = StoreCustomerBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
