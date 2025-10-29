<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;

class AdminPasswordResetNotification extends ResetPasswordNotification
{
    use Queueable;

    /**
     * Constructor - 設定為同步發送
     */
    public function __construct($token)
    {
        parent::__construct($token);
        $this->connection = 'sync';
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('重設您的 592Meal 店家後台密碼')
            ->greeting('您好 ' . $notifiable->name)
            ->line('我們收到您要重設密碼的請求。')
            ->line('請點擊下方按鈕來重設您的密碼：')
            ->action('重設密碼', $this->resetUrl($notifiable))
            ->line('此密碼重設連結將在 60 分鐘後失效。')
            ->line('如果您沒有請求重設密碼，請忽略此郵件。')
            ->salutation('592Meal 團隊');
    }

    /**
     * 為 Filament 管理面板提供重設 URL
     * 使用 Filament Panel 的內建方法產生簽名 URL
     */
    protected function resetUrl($notifiable)
    {
        try {
            $panel = \Filament\Facades\Filament::getPanel('admin');
            $url = $panel->getResetPasswordUrl($this->token, $notifiable);

            // 除錯日誌
            \Log::info('Password Reset URL Generated via Filament', [
                'url' => $url,
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
                'panel_id' => $panel->getId(),
            ]);

            return $url;
        } catch (\Exception $e) {
            \Log::error('Failed to generate Filament reset URL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 降級使用手動構建的 URL
            return \Illuminate\Support\Facades\URL::signedRoute(
                'filament.admin.auth.password-reset.reset',
                [
                    'email' => $notifiable->getEmailForPasswordReset(),
                    'token' => $this->token,
                ]
            );
        }
    }
}
