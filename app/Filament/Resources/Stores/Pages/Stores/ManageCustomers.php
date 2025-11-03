<?php

namespace App\Filament\Resources\Stores\Pages\Stores;

use App\Filament\Resources\Stores\StoreResource;
use Filament\Resources\Pages\Page;

class ManageCustomers extends Page
{
    protected static string $resource = StoreResource::class;

    protected string $view = 'filament.resources.stores.pages.stores.manage-customers';
}
