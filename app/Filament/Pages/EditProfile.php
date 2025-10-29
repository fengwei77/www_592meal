<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.edit-profile';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = '個人資料';

    protected static ?string $title = '編輯個人資料';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => Auth::user()->name,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('基本資料')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Placeholder::make('email')
                            ->label('Email')
                            ->content(fn () => Auth::user()->email)
                            ->helperText('Email 無法修改'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('變更密碼')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('目前密碼')
                            ->password()
                            ->dehydrated(false)
                            ->requiredWith('password')
                            ->currentPassword()
                            ->helperText('若要變更密碼，請先輸入目前的密碼'),

                        Forms\Components\TextInput::make('password')
                            ->label('新密碼')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->rule(Password::default())
                            ->same('password_confirmation')
                            ->helperText('至少 8 個字元'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('確認新密碼')
                            ->password()
                            ->dehydrated(false)
                            ->requiredWith('password'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $user = Auth::user();

            // 更新名稱
            $user->name = $data['name'];

            // 如果有填寫新密碼，則更新密碼
            if (filled($data['password'] ?? null)) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            // 重新填充表單（清除密碼欄位）
            $this->form->fill([
                'name' => $user->name,
            ]);

            Notification::make()
                ->success()
                ->title('個人資料已更新')
                ->body('您的個人資料已成功更新。')
                ->send();

        } catch (Halt $exception) {
            return;
        }
    }
}
