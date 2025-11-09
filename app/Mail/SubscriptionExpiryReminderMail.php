<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryReminderMail extends Mailable
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
     * 訂閱到期日
     *
     * @var string
     */
    public $expiryDate;

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
        $this->expiryDate = $user->getSubscriptionEndDate();
    }

    /**
     * 取得郵件信封
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: '⚠️ 訂閱即將到期 - 592meal 美食訂閱服務',
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
            view: 'emails.subscription.expiry-reminder',
            with: [
                'user' => $this->user,
                'remainingDays' => $this->remainingDays,
                'expiryDate' => $this->expiryDate,
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'subscriptionUrl' => route('subscription.index'),
                'renewalUrl' => route('subscription.renew'),
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