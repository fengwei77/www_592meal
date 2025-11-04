<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新的聯絡表單提交</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
            border-radius: 0 0 8px 8px;
        }
        .info-row {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 4px;
        }
        .info-label {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📧 新的聯絡表單提交</h1>
        <p>592Meal 平台收到新的聯絡表單</p>
    </div>

    <div class="content">
        <div class="info-row">
            <div class="info-label">提交時間：</div>
            <div>{{ $contact->created_at->format('Y-m-d H:i:s') }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">姓名：</div>
            <div>{{ $contact->name }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">Email：</div>
            <div>{{ $contact->email }}</div>
        </div>

        @if($contact->phone)
        <div class="info-row">
            <div class="info-label">電話：</div>
            <div>{{ $contact->phone }}</div>
        </div>
        @endif

        <div class="info-row">
            <div class="info-label">主題：</div>
            <div>{{ $contact->subject }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">訊息內容：</div>
            <div style="white-space: pre-wrap;">{{ $contact->message }}</div>
        </div>

        @if($contact->store)
        <div class="info-row">
            <div class="info-label">相關店家：</div>
            <div>{{ $contact->store->name }}</div>
        </div>
        @endif

        <div class="info-row">
            <div class="info-label">IP 位址：</div>
            <div>{{ $contact->ip_address }}</div>
        </div>
    </div>

    <div class="footer">
        <p>此郵件由 592Meal 系統自動發送</p>
        <p>請盡快回覆用戶的聯絡需求</p>
    </div>
</body>
</html>