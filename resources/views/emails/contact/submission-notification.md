<x-mail::message>
# 新的聯絡表單通知 - 592Meal

親愛的 {{ $contact->name }}：

感謝您聯繫 592Meal！我們已經收到您的訊息。

## 聯絡詳情：

**主題：** {{ $contact->subject }}
**時間：** {{ $contact->created_at->format('Y-m-d H:i:s') }}

**內容：**
{{ $contact->message }}

---

我們會盡快處理您的聯絡，並在必要時回覆您。

如有緊急事項，請直接聯繫我們的客服團隊。

歡迎來到 592Meal！
</x-mail::message>