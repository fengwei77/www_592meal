<?php

namespace App\Mail;

use App\Models\SubscriptionOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 訂單紀錄實例
     *
     * @var SubscriptionOrder
     */
    public $order;

    /**
     * 訂閱到期日
     *
     * @var string
     */
    public $subscriptionEndDate;

    /**
     * 建立新的郵件實例
     *
     * @param SubscriptionOrder $order
     */
    public function __construct(SubscriptionOrder $order)
    {
        $this->order = $order;
        $this->subscriptionEndDate = $order->user->getSubscriptionEndDate();
    }

    /**
     * 取得郵件信封
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: '🎉 訂閱成功 - 592meal 美食訂閱服務',
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
            view: 'emails.subscription.success',
            with: [
                'order' => $this->order,
                'subscriptionEndDate' => $this->subscriptionEndDate,
                'userName' => $this->order->user->name,
                'userEmail' => $this->order->user->email,
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