<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mailer\Envelope as SymfonyEnvelope;
use Symfony\Component\Mime\Email;

class ContactReplyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Contact $contact;
    public string $replyMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact, string $replyMessage)
    {
        $this->contact = $contact;
        $this->replyMessage = $replyMessage;
    }

    /**
     * Build the email with direct API approach
     */
    public function build()
    {
        // 使用 Laravel 的郵件系統，但改用 markdown 格式（像 VerifyEmail 一樣）
        return $this
            ->subject('回覆：' . $this->contact->subject . ' - 592Meal')
            ->markdown('emails.contact.reply-notification', [
                'contact' => $this->contact,
                'replyMessage' => $this->replyMessage,
            ]);
    }
}