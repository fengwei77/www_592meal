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
    ];

    protected $casts = [
        'send_notification' => 'boolean',
        'replied_at' => 'datetime',
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
}