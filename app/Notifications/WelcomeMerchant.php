<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeMerchant extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
        // Assuming the login route is named 'login'
        $loginUrl = route('login');

        return (new MailMessage)
                    ->subject('歡迎加入 592Meal 店家平台！')
                    ->greeting('您好, ' . $notifiable->name . '!')
                    ->line('您的店家老闆帳號已成功建立。歡迎您加入 592Meal 的大家庭。')
                    ->line('請點擊下方按鈕登入您的店家後台，開始新增您的第一家店吧！')
                    ->action('前往店家後台', $loginUrl)
                    ->line('感謝您選擇我們！');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
