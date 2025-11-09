<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂閱成功 - 592meal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .success-icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 30px;
        }
        .order-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            font-weight: 700;
            color: #212529;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            transition: transform 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .footer-links {
            margin: 20px 0;
        }
        .footer-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 標題區域 -->
        <div class="header">
            <h1>🎉 訂閱成功</h1>
            <p>感謝您選擇 592meal 美食訂閱服務</p>
        </div>

        <!-- 內容區域 -->
        <div class="content">
            <div class="success-icon">✅</div>

            <h2 style="text-align: center; color: #28a745; margin-bottom: 30px;">
                親愛的 {{ $userName }}，您的訂閱已成功開通！
            </h2>

            <!-- 訂單詳情 -->
            <div class="order-details">
                <h3 style="margin-top: 0; color: #495057;">訂單詳情</h3>

                <div class="detail-row">
                    <span class="detail-label">訂單編號</span>
                    <span class="detail-value">{{ $order->order_number }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">訂閱月數</span>
                    <span class="detail-value">{{ $order->months }} 個月</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">月費</span>
                    <span class="detail-value">NT$ {{ number_format($order->unit_price) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">總金額</span>
                    <span class="detail-value" style="color: #dc3545;">NT$ {{ number_format($order->total_amount) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">付款時間</span>
                    <span class="detail-value">{{ $order->paid_at->format('Y-m-d H:i:s') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">訂閱到期日</span>
                    <span class="detail-value" style="color: #007bff;">{{ $subscriptionEndDate }}</span>
                </div>
            </div>

            <!-- 服務說明 -->
            <div style="margin: 30px 0;">
                <h3 style="color: #495057;">🎁 您現在可以享受的服務</h3>
                <ul style="line-height: 2;">
                    <li>✨ 無限制餐廳資料管理</li>
                    <li>📊 專業營運分析報表</li>
                    <li>🎯 客戶群組精準行銷</li>
                    <li>🔧 進階系統設定功能</li>
                    <li>📱 手機APP完整功能</li>
                    <li>💬 優先客戶技術支援</li>
                </ul>
            </div>

            <!-- 行動按鈕 -->
            <div style="text-align: center;">
                <a href="{{ route('subscription.history') }}" class="cta-button">
                    查看訂閱詳情
                </a>
            </div>

            <!-- 提醒事項 -->
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <h4 style="margin-top: 0; color: #856404;">💡 溫馨提醒</h4>
                <p style="margin-bottom: 0; color: #856404;">
                    您的訂閱將在 {{ $subscriptionEndDate }} 到期，我們將在到期前 7 天發送提醒郵件。
                    如需續約，請隨時前往訂閱管理頁面處理。
                </p>
            </div>
        </div>

        <!-- 頁尾區域 -->
        <div class="footer">
            <p>如有任何問題，歡迎聯繫我們的客服團隊</p>
            <div class="footer-links">
                <a href="{{ config('app.url') }}">官網首頁</a>
                <a href="mailto:{{ config('mail.from.address') }}">聯繫我們</a>
                <a href="#">使用說明</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px;">
                此郵件為系統自動發送，請勿直接回覆。<br>
                © {{ date('Y') }} 592meal. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>