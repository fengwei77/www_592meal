<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂閱即將到期 - 592meal</title>
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
            background: linear-gradient(135deg, #ffc107, #fd7e14);
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
        .warning-icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 30px;
            color: #ffc107;
        }
        .expiry-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            text-align: center;
        }
        .days-remaining {
            font-size: 48px;
            font-weight: 700;
            color: #e67e22;
            margin: 20px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 20px 10px;
            transition: transform 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .cta-button.primary {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .cta-button.secondary {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        .feature-list {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .feature-list h3 {
            margin-top: 0;
            color: #495057;
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
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .days-remaining {
                font-size: 36px;
            }
            .cta-button {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 標題區域 -->
        <div class="header">
            <h1>⚠️ 訂閱即將到期提醒</h1>
            <p>請及時續約以確保服務不中斷</p>
        </div>

        <!-- 內容區域 -->
        <div class="content">
            <div class="warning-icon">⏰</div>

            <h2 style="text-align: center; color: #e67e22; margin-bottom: 30px;">
                親愛的 {{ $userName }}，您的訂閱即將到期
            </h2>

            <!-- 到期資訊 -->
            <div class="expiry-info">
                <h3 style="margin-top: 0; color: #856404;">您的訂閱狀態</h3>
                <div class="days-remaining">
                    剩餘 {{ $remainingDays }} 天
                </div>
                <p style="margin: 0; font-size: 18px; color: #856404;">
                    訂閱到期日：{{ $expiryDate }}
                </p>
            </div>

            <!-- 影響說明 -->
            <div style="margin: 30px 0;">
                <h3 style="color: #dc3545; text-align: center;">🚨 到期後將無法使用以下功能</h3>
                <div class="feature-list">
                    <ul style="line-height: 2; margin-bottom: 0;">
                        <li style="color: #dc3545;">❌ 新增餐廳資料管理</li>
                        <li style="color: #dc3545;">❌ 營運分析報表查看</li>
                        <li style="color: #dc3545;">❌ 客戶群組行銷功能</li>
                        <li style="color: #dc3545;">❌ 進階系統設定</li>
                        <li style="color: #dc3545;">❌ 手機APP進階功能</li>
                    </ul>
                </div>
            </div>

            <!-- 保留功能 -->
            <div style="margin: 30px 0;">
                <h3 style="color: #28a745; text-align: center;">✅ 到期後仍可使用</h3>
                <div class="feature-list">
                    <ul style="line-height: 2; margin-bottom: 0;">
                        <li style="color: #28a745;">✅ 查看現有餐廳資料</li>
                        <li style="color: #28a745;">✅ 基本營運資訊查看</li>
                        <li style="color: #28a745;">✅ 客戶基本資料管理</li>
                    </ul>
                </div>
            </div>

            <!-- 行動按鈕 -->
            <div class="button-container">
                <a href="{{ $renewalUrl }}" class="cta-button primary">
                    🚀 立即續約
                </a>
                <a href="{{ $subscriptionUrl }}" class="cta-button secondary">
                    📋 查看詳情
                </a>
            </div>

            <!-- 續約優惠 -->
            <div style="background-color: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <h4 style="margin-top: 0; color: #0c5460;">💎 續約優惠方案</h4>
                <ul style="margin-bottom: 0; color: #0c5460;">
                    <li>💰 長期訂閱享更多優惠</li>
                    <li>🎁 續約可獲得額外 7 天免費使用</li>
                    <li>⭐ 支援多種付款方式</li>
                </ul>
            </div>

            <!-- 聯絡資訊 -->
            <div style="text-align: center; margin: 30px 0;">
                <h4 style="color: #6c757d;">需要協助嗎？</h4>
                <p style="color: #6c757d;">
                    我們的客服團隊隨時為您服務<br>
                    <strong> Email：</strong> {{ config('mail.from.address') }}<br>
                    <strong> 服務時間：</strong> 週一至週五 9:00-18:00
                </p>
            </div>
        </div>

        <!-- 頁尾區域 -->
        <div class="footer">
            <p>感謝您使用 592meal 美食訂閱服務</p>
            <div class="footer-links">
                <a href="{{ config('app.url') }}">官網首頁</a>
                <a href="{{ route('subscription.history') }}">訂閱管理</a>
                <a href="mailto:{{ config('mail.from.address') }}">聯繫我們</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px;">
                此郵件為系統自動發送，請勿直接回覆。<br>
                © {{ date('Y') }} 592meal. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>