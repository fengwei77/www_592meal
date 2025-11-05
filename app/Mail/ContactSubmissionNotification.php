<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;

    /**
     * 創建新的郵件實例
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * 獲取郵件信封
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '新的聯絡表單提交 - ' . $this->contact->subject,
        );
    }

    /**
     * 獲取郵件內容定義
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.submission-notification',
            with: [
                'contact' => $this->contact,
            ],
        );
    }

    /**
     * 獲取郵件附件
     */
    public function attachments(): array
    {
        return [];
    }
}