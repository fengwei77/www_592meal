<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 用戶實例
     *
     * @var User
     */
    public $user;

    /**
     * 剩餘天數
     *
     * @var int
     */
    public $remainingDays;

    /**
     * 試用到期日
     *
     * @var string
     */
    public $trialEndDate;

    /**
     * 建立新的郵件實例
     *
     * @param User $user
     * @param int $remainingDays
     */
    public function __construct(User $user, int $remainingDays)
    {
        $this->user = $user;
        $this->remainingDays = $remainingDays;
        $this->trialEndDate = $user->trial_ends_at->format('Y-m-d');
    }

    /**
     * 取得郵件信封
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: '🚀 試用期即將結束 - 592meal 美食訂閱服務',
        );
    }

    /**
     * 取得郵件內容定義
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.subscription.trial-expiry-reminder',
            with: [
                'user' => $this->user,
                'remainingDays' => $this->remainingDays,
                'trialEndDate' => $this->trialEndDate,
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'subscriptionUrl' => route('subscription.index'),
                'subscriptionPlans' => [
                    1 => ['months' => 1, 'price' => 50, 'description' => '1個月訂閱'],
                    3 => ['months' => 3, 'price' => 150, 'description' => '3個月訂閱'],
                    6 => ['months' => 6, 'price' => 300, 'description' => '6個月訂閱'],
                    12 => ['months' => 12, 'price' => 600, 'description' => '12個月訂閱'],
                ]
            ]
        );
    }

    /**
     * 取得郵件附件
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
