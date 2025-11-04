<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;

class SendTestResendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-test-resend-email {--contact_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test email using Resend API directly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $contactId = $this->option('contact_id', 1);
            $contact = Contact::find($contactId);

            if (!$contact) {
                $this->error("Contact with ID {$contactId} not found");
                return 1;
            }

            // 直接使用 Resend API
            $resendKey = env('RESEND_API_KEY');
            $email = $contact->email;
            $subject = '測試郵件 - 592Meal 聯絡回覆';
            $htmlContent = $this->buildReplyEmail($contact, '這是一封測試郵件內容');

            $this->info("Sending email to: {$email}");
            $this->info("Subject: {$subject}");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $resendKey,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'from' => 'noreply@592meal.online',
                'to' => [$email],
                'subject' => $subject,
                'html' => $htmlContent,
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response, true);

            if ($httpCode === 200) {
                $this->info('✅ Email sent successfully!');
                $this->info('Response ID: ' . ($responseData['id'] ?? 'N/A'));

                // 記錄發送成功
                $contact->recordNotificationSent();
            } else {
                $this->error('❌ Failed to send email. HTTP Code: ' . $httpCode);
                $this->error('Response: ' . $response);
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function buildReplyEmail($contact, $replyMessage)
    {
        return '
        <!DOCTYPE html>
        <html lang="zh-TW">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>592Meal 回覆通知</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3b82f6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: white; padding: 20px; border: 1px solid #e5e7eb; border-top: none; }
                .message-box { background: #f9fafb; padding: 15px; border-left: 4px solid #3b82f6; margin: 15px 0; border-radius: 4px; }
                .footer { text-align: center; margin-top: 20px; color: #6b7280; font-size: 14px; border-top: 1px solid #e5e7eb; padding-top: 15px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📧 592Meal 回覆</h1>
                <p>感謝您的聯絡！</p>
            </div>
            <div class="content">
                <p>親愛的 ' . htmlspecialchars($contact->name) . '：</p>
                <div class="message-box">
                    <strong>測試回覆內容：</strong>
                    <div style="white-space: pre-wrap; margin-top: 10px;">' . $replyMessage . '</div>
                </div>
                <div class="footer">
                    <p>此郵件由 592Meal 系統直接發送</p>
                </div>
            </div>
        </body>
        </html>
        ';
    }
}
