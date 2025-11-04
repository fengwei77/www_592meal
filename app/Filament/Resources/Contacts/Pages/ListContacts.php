<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    
    public function getTitle(): string
    {
        return '聯絡表單管理';
    }

    public function getSubheading(): ?string
    {
        $total = $this->getTableRecords()->count();
        $pending = $this->getTableRecords()->where('status', 'pending')->count();

        return "共 {$total} 筆聯絡記錄，其中 {$pending} 筆待處理";
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->latest();
    }
}