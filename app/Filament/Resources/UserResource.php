<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * User Resource (店家/管理員管理)
 *
 * 用於 Super Admin 管理所有店家的資料與安全設定
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = '店家管理';

    protected static ?string $modelLabel = '店家';

    protected static ?string $pluralModelLabel = '店家';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資料')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('密碼')
                            ->password()
                            ->required(fn($context) => $context === 'create')
                            ->dehydrated(fn($state) => filled($state))
                            ->maxLength(255)
                            ->helperText('建立時必填，編輯時留空則不更新密碼'),

                        Forms\Components\Select::make('roles')
                            ->label('角色')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('安全設定')
                    ->schema([
                        Forms\Components\Toggle::make('ip_whitelist_enabled')
                            ->label('啟用 IP 白名單')
                            ->helperText('啟用後，只有白名單內的 IP 可以登入')
                            ->reactive()
                            ->default(false),

                        Forms\Components\TagsInput::make('ip_whitelist')
                            ->label('IP 白名單')
                            ->placeholder('輸入 IP 位址 (例如: 192.168.1.100)')
                            ->helperText('每行一個 IP 位址，按 Enter 新增')
                            ->visible(fn (callable $get) => $get('ip_whitelist_enabled')),

                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('啟用 2FA (雙因素認證)')
                            ->helperText('啟用後，登入時需要輸入 Google Authenticator 驗證碼')
                            ->reactive()
                            ->default(false),

                        Forms\Components\Placeholder::make('two_factor_status')
                            ->label('2FA 狀態')
                            ->content(function ($record) {
                                if (!$record) {
                                    return '尚未設定';
                                }

                                // 檢查是否臨時關閉
                                if ($record->isTwoFactorTempDisabled()) {
                                    $disabledAt = $record->two_factor_temp_disabled_at;
                                    $restoreAt = $disabledAt->copy()->addHours(24);
                                    $remaining = now()->diffInHours($restoreAt, true);

                                    return "🔒 臨時關閉中 (還有 {$remaining} 小時後自動恢復)\n" .
                                           "關閉時間: {$disabledAt->format('Y-m-d H:i')}\n" .
                                           "恢復時間: {$restoreAt->format('Y-m-d H:i')}";
                                }

                                if ($record->two_factor_confirmed_at) {
                                    return '✅ 已確認 ('. $record->two_factor_confirmed_at->format('Y-m-d H:i') .')';
                                }

                                if ($record->two_factor_secret) {
                                    return '⚠️ 已設定但未確認';
                                }

                                return '❌ 未設定';
                            })
                            ->visible(fn (callable $get) => $get('two_factor_enabled')),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('角色')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'store_owner',
                    ]),

                Tables\Columns\IconColumn::make('ip_whitelist_enabled')
                    ->label('IP 白名單')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('two_factor_enabled')
                    ->label('2FA')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('角色')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('ip_whitelist_enabled')
                    ->label('IP 白名單')
                    ->placeholder('全部')
                    ->trueLabel('已啟用')
                    ->falseLabel('未啟用'),

                Tables\Filters\TernaryFilter::make('two_factor_enabled')
                    ->label('2FA')
                    ->placeholder('全部')
                    ->trueLabel('已啟用')
                    ->falseLabel('未啟用'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * 僅 Super Admin 可以訪問此 Resource
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
