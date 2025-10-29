<?php

namespace App\Filament\Resources\Stores\Pages;

use App\Filament\Resources\Stores\StoreResource;
use App\Models\Store;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Widgets;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Support\Facades\Auth;

class ManageStoreMenu extends Page
{
    protected static string $resource = StoreResource::class;

    protected string $view = 'filament.resources.stores.pages.manage-store-menu';

    public Store $record;

    public function mount($record): void
    {
        // 如果 $record 是物件或陣列，取得 ID
        if (is_object($record) && isset($record->id)) {
            $recordId = $record->id;
        } elseif (is_array($record) && isset($record['id'])) {
            $recordId = $record['id'];
        } else {
            $recordId = $record; // 假設是 ID
        }

        $this->record = Store::findOrFail($recordId);

        // 檢查權限
        $user = Auth::user();
        if (!$user->hasRole('super_admin') && !$this->record->isOwnedBy($user)) {
            abort(403);
        }
    }

    public function getTitle(): string
    {
        return "管理菜單 - {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_categories')
                ->label('管理分類')
                ->url('/menu/menu-categories')
                ->color('primary')
                ->icon('heroicon-o-rectangle-stack'),

            Action::make('manage_items')
                ->label('管理餐點')
                ->url('/menu/menu-items')
                ->color('success')
                ->icon('heroicon-o-list-bullet'),

            Actions\Action::make('back_to_store')
                ->label('返回店家')
                ->url(fn () => StoreResource::getUrl('edit', ['record' => $this->record->id]))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        // 暫時註釋掉 Widget，避免類別找不到的錯誤
        // return [
        //     \App\Filament\Resources\Stores\Widgets\StoreMenuStatsWidget::make([
        //         'record' => $this->record,
        //     ]),
        // ];

        return [];
    }
}