<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Contact Model (聯絡表單)
 *
 * 處理前台用戶提交的聯絡表單
 */
class Contact extends Model
{
    use HasFactory;

    // 狀態常數
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_REPLIED = 'replied';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_SPAM = 'spam';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'reply_message',
        'admin_notes',
        'send_notification',
        'replied_at',
        'replied_by',
        'store_id',
        'ip_address',
        'user_agent',
        'phone_notification_sent_at',
    ];

    protected $casts = [
        'send_notification' => 'boolean',
        'replied_at' => 'datetime',
        'phone_notification_sent_at' => 'datetime',
    ];

    /**
     * 狀態選項
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => '待處理',
            self::STATUS_PROCESSING => '處理中',
            self::STATUS_REPLIED => '已回覆',
            self::STATUS_RESOLVED => '已解決',
            self::STATUS_SPAM => '垃圾訊息',
        ];
    }

    /**
     * 獲取狀態標籤
     */
    public function getStatusLabel(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * 檢查是否為待處理狀態
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * 檢查是否已回覆
     */
    public function isReplied(): bool
    {
        return $this->status === self::STATUS_REPLIED;
    }

    /**
     * 檢查是否已解決
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * 標記為已回覆
     */
    public function markAsReplied(string $replyMessage = null, int $repliedBy = null): bool
    {
        $this->status = self::STATUS_REPLIED;
        $this->replied_at = now();
        $this->replied_by = $repliedBy;

        if ($replyMessage) {
            $this->reply_message = $replyMessage;
        }

        return $this->save();
    }

    /**
     * 標記為已解決
     */
    public function markAsResolved(): bool
    {
        $this->status = self::STATUS_RESOLVED;
        return $this->save();
    }

    /**
     * 標記為處理中
     */
    public function markAsProcessing(): bool
    {
        $this->status = self::STATUS_PROCESSING;
        return $this->save();
    }

    /**
     * 獲取回覆者關聯
     */
    public function replier()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * 獲取相關店家（可選）
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 作用域：待處理的聯絡表單
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * 作用域：已回覆的聯絡表單
     */
    public function scopeReplied($query)
    {
        return $query->where('status', self::STATUS_REPLIED);
    }

    /**
     * 作用域：已解決的聯絡表單
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * 檢查是否應該發送電話通知
     */
    public function shouldSendPhoneNotification(): bool
    {
        return !empty($this->phone) &&
               $this->send_notification &&
               empty($this->phone_notification_sent_at);
    }

    /**
     * 發送電話通知（這裡可以整合第三方簡訊服務）
     */
    public function sendPhoneNotification(string $message): bool
    {
        try {
            // 這裡可以整合第三方簡訊服務，如：
            // - 台灣大哥大 mySms
            // - 中華電信 Hami SMS
            // - 遠傳電信 Ezone
            // - Twilio
            // - SendGrid SMS

            // 暫時記錄日誌，實際發送需要第三方服務
            \Log::info("發送電話通知給 {$this->phone}: {$message}");

            // 模擬發送成功
            return true;

        } catch (\Exception $e) {
            \Log::error("電話通知發送失敗: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 記錄通知已發送
     */
    public function recordNotificationSent(): bool
    {
        $this->phone_notification_sent_at = now();
        return $this->save();
    }

    /**
     * 檢查電話通知是否已發送
     */
    public function hasPhoneNotificationSent(): bool
    {
        return !empty($this->phone_notification_sent_at);
    }

    /**
     * 發送回覆通知給用戶
     */
    public function sendReplyNotification(string $replyMessage): bool
    {
        try {
            // 這裡可以發送郵件或簡訊通知給用戶
            // 暫時使用日誌記錄
            \Log::info("發送回覆通知給 {$this->email}: {$replyMessage}");

            return true;
        } catch (\Exception $e) {
            \Log::error("回覆通知發送失敗: " . $e->getMessage());
            return false;
        }
    }
}