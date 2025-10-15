<x-mail::message>
# 🎉 驗證通過！您的帳號已成功啟用

恭喜您！您的 592Meal 商家帳號已成功通過 Email 驗證，現在可以開始使用後台功能。

## 📧 後台登入資訊

- **完整後台網址**: {{ config('app.admin_url') }}/login
- **您的登入 Email**: {{ $email ?? $notifiable->email }}

## 🔗 立即登入後台

<x-mail::button :url="config('app.admin_url') . '/login'">
立即前往後台登入
</x-mail::button>

## 📋 重要提醒

- ✅ 您的帳號已通過驗證，可以正常使用所有功能
- 🔐 請使用您的 Email 和註冊時設定的密碼登入
- 📝 建議您登入後立即完善店家資訊
- 🆘 如有任何問題，請隨時聯繫客服支援

感謝您選擇 592Meal 平台！<br>
{{ config('app.name') }} 團隊 敬上
</x-mail::message>