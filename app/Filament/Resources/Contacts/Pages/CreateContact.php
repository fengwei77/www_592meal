<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string
    {
        return '新增聯絡表單';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        // 記錄 IP 地址和 User Agent
        $this->getRecord()->update([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/admin/contacts' => '聯絡表單管理',
            '#' => '新增聯絡表單',
        ];
    }
}