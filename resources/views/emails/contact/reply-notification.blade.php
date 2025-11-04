<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $contact->subject }} - 592Meal 回覆</title>
    <style>
        body {
            font-family: 'Chocolate Classical Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: white;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .message-box {
            background: #f9fafb;
            padding: 15px;
            border-left: 4px solid #3b82f6;
            margin: 15px 0;
            border-radius: 4px;
        }
        .original-message {
            background: #fef3c7;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📧 <span class="brand-592meal">592Meal</span> 回覆</h1>
        <p>感謝您的聯絡！</p>
    </div>

    <div class="content">
        <p>親愛的 {{ $contact->name }}：</p>

        <div class="message-box">
            <strong>我們的回覆：</strong>
            <div style="white-space: pre-wrap; margin-top: 10px;">
                {{ $replyMessage }}
            </div>
        </div>

        <p>如果您還有其他問題，請隨時聯繫我們。</p>

        <div class="original-message">
            <strong>您的原始訊息：</strong>
            <div style="margin-top: 8px;">
                <p><strong>主題：</strong>{{ $contact->subject }}</p>
                <p><strong>時間：</strong>{{ $contact->created_at->format('Y-m-d H:i:s') }}</p>
                <p><strong>內容：</strong></p>
                <div style="white-space: pre-wrap; background: white; padding: 10px; border-radius: 4px; margin-top: 5px;">
                    {{ $contact->message }}
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>此郵件由 <span class="brand-592meal">592Meal</span> 系統自動發送</p>
        <p>如有任何問題，請直接回覆此郵件</p>
    </div>
</body>
</html>