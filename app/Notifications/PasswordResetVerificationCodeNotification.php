<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetVerificationCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $code;
    public int $expiresInMinutes;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code, int $expiresInMinutes = 10)
    {
        $this->code = $code;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('密碼重設驗證碼 - 592Meal')
            ->greeting('您好 ' . $notifiable->name)
            ->line('您正在請求重設 592Meal 店家後台的密碼。')
            ->line('請使用以下 6 位數驗證碼來驗證您的身份：')
            ->line('')
            ->line('**驗證碼：' . chunk_split($this->code, 3, ' ') . '**')
            ->line('')
            ->line("此驗證碼將在 {$this->expiresInMinutes} 分鐘後失效。")
            ->line('驗證成功後，系統會發送密碼重設連結到您的信箱。')
            ->line('')
            ->line('如果您沒有請求重設密碼，請忽略此郵件。')
            ->salutation('592Meal 團隊');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'expires_in_minutes' => $this->expiresInMinutes,
        ];
    }
}
