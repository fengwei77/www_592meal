<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Schemas\CustomerInfolist;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = '客戶管理';

    protected static ?string $modelLabel = '客戶';

    protected static ?string $pluralModelLabel = '客戶';

    protected static string | \UnitEnum | null $navigationGroup = '店家管理';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
            // 客戶不應手動建立或編輯
            // 'create' => CreateCustomer::route('/create'),
            // 'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    /**
     * 查詢範圍：只顯示有訂過該店家的客戶
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = \Illuminate\Support\Facades\Auth::user();

        // Super Admin 可以看到所有客戶
        if ($user && $user->hasRole('super_admin')) {
            return $query;
        }

        // Store Owner 只能看到在自己店家訂過餐的客戶
        if ($user && $user->hasRole('store_owner')) {
            $storeIds = \App\Models\Store::where('user_id', $user->id)->pluck('id');

            return $query->whereHas('orders', function ($q) use ($storeIds) {
                $q->whereIn('store_id', $storeIds);
            });
        }

        // 其他角色看不到任何客戶
        return $query->whereRaw('1 = 0');
    }
}
