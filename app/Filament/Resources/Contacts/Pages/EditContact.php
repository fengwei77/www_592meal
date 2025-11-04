<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    public function getTitle(): string
    {
        return '編輯聯絡表單';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterSave(): void
    {
        // 如果狀態變更為已回覆且有回覆訊息，記錄回覆時間和回覆者
        if (
            $this->getRecord()->isReplied() &&
            $this->getRecord()->reply_message &&
            !$this->getRecord()->replied_at
        ) {
            $this->getRecord()->update([
                'replied_at' => now(),
                'replied_by' => Auth::id(),
            ]);
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/admin/contacts' => '聯絡表單管理',
            $this->getResource()::getUrl('view', ['record' => $this->getRecord()]) => $this->getRecord()->subject,
            '#' => '編輯',
        ];
    }
}