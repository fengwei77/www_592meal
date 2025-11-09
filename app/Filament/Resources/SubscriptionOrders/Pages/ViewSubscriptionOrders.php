<?php

namespace App\Filament\Resources\SubscriptionOrders\Pages;

use App\Filament\Resources\SubscriptionOrders\SubscriptionOrderResource;
use Filament\Resources\Pages\ListRecords;

class ViewSubscriptionOrders extends ListRecords
{
    protected static string $resource = SubscriptionOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}