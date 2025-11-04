<x-mail::message>
# 592Meal 回覆通知

親愛的 {{ $contact->name }}：

感謝您的聯絡！我們已經回覆您的訊息。

## 我們的回覆：

{{ $replyMessage }}

---

如果您還有其他問題，請隨時聯繫我們。

### 您的原始訊息：

**主題：** {{ $contact->subject }}
**時間：** {{ $contact->created_at->format('Y-m-d H:i:s') }}

**內容：**
{{ $contact->message }}

---

此郵件由 592Meal 系統自動發送

如有任何問題，請直接回覆此郵件
</x-mail::message>