<x-mail::message>
# 歡迎來到 592Meal！

感謝您的註冊！請使用以下驗證碼來啟用您的帳號：

## {{ $user->email_verification_code }}

或者，您可以點擊下方的按鈕直接前往驗證頁面。

<x-mail::button :url="route('verification.notice', ['email' => $user->email])">
前往驗證
</x-mail::button>

此驗證碼將在 60 分鐘後過期。

感謝您,<br>
{{ config('app.name') }}
</x-mail::message>