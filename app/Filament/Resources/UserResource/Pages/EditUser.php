<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\DeleteAction::make(),
        ];

        $user = $this->record;

        // 如果店家有啟用 2FA 且已確認
        if ($user && $user->two_factor_enabled && $user->two_factor_confirmed_at) {
            // 如果正在臨時關閉中，顯示「立即恢復」按鈕
            if ($user->isTwoFactorTempDisabled()) {
                $actions[] = Actions\Action::make('restoreTwoFactor')
                    ->label('立即恢復 2FA')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('立即恢復雙因素認證')
                    ->modalDescription('確定要立即恢復此店家的 2FA 嗎？')
                    ->action(function () use ($user) {
                        $user->restoreTwoFactor();

                        Notification::make()
                            ->title('2FA 已恢復')
                            ->body('此店家的雙因素認證已恢復正常')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $user]));
                    });
            } else {
                // 如果沒有被臨時關閉，顯示「臨時關閉」按鈕
                $actions[] = Actions\Action::make('tempDisableTwoFactor')
                    ->label('臨時關閉 2FA (24小時)')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('臨時關閉雙因素認證')
                    ->modalDescription('確定要臨時關閉此店家的 2FA 嗎？此操作用於店家遺失手機等緊急情況。系統將在 24 小時後自動恢復，或店家重新設定 2FA 後立即恢復。')
                    ->action(function () use ($user) {
                        $user->tempDisableTwoFactor();

                        $restoreAt = now()->addHours(24);
                        Notification::make()
                            ->title('2FA 已臨時關閉')
                            ->body("將於 {$restoreAt->format('Y-m-d H:i')} 自動恢復")
                            ->warning()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $user]));
                    });
            }
        }

        return $actions;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 如果密碼欄位有值，則加密；否則移除該欄位以保持原密碼
        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
