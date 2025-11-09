<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試用期即將結束 - 592meal</title>
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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
        .rocket-icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .trial-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
            text-align: center;
        }
        .days-remaining {
            font-size: 48px;
            font-weight: 700;
            margin: 20px 0;
        }
        .pricing-cards {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        .pricing-card {
            flex: 1;
            min-width: 120px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .pricing-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        .pricing-card.featured {
            border-color: #28a745;
            background: linear-gradient(135deg, #f0fff4, #e8f5e8);
        }
        .price {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin: 10px 0;
        }
        .pricing-card.featured .price {
            color: #28a745;
        }
        .months {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .description {
            font-size: 14px;
            color: #495057;
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
            margin: 20px 10px;
            transition: transform 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .cta-button.secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }
        .benefits-list {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .benefits-list h3 {
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
            color: #667eea;
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
        .badge-popular {
            background-color: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .pricing-cards {
                flex-direction: column;
            }
            .days-remaining {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 標題區域 -->
        <div class="header">
            <h1>🚀 試用期即將結束</h1>
            <p>立即升級體驗完整功能</p>
        </div>

        <!-- 內容區域 -->
        <div class="content">
            <div class="rocket-icon">🚀</div>

            <h2 style="text-align: center; color: #667eea; margin-bottom: 30px;">
                親愛的 {{ $userName }}，您的試用期即將結束！
            </h2>

            <!-- 試用資訊 -->
            <div class="trial-info">
                <h3 style="margin-top: 0;">您的試用期狀態</h3>
                <div class="days-remaining">
                    剩餘 {{ $remainingDays }} 天
                </div>
                <p style="margin: 0; font-size: 18px;">
                    試用期結束：{{ $trialEndDate }}
                </p>
            </div>

            <!-- 試用期回顧 -->
            <div style="margin: 30px 0;">
                <h3 style="color: #28a745; text-align: center;">🎉 試用期期間您已體驗</h3>
                <div class="benefits-list">
                    <ul style="line-height: 2; margin-bottom: 0;">
                        <li>✨ 餐廳資料管理系統</li>
                        <li>📊 基本營運分析報表</li>
                        <li>👥 客戶資料整理功能</li>
                        <li>🔧 系統基本設定選項</li>
                        <li>📱 手機APP核心功能</li>
                    </ul>
                </div>
            </div>

            <!-- 訂閱方案 -->
            <div style="margin: 30px 0;">
                <h3 style="text-align: center; color: #495057;">💎 選擇您的訂閱方案</h3>
                <div class="pricing-cards">
                    @foreach($subscriptionPlans as $months => $plan)
                    <div class="pricing-card {{ $months == 6 ? 'featured' : '' }}">
                        <div class="months">{{ $plan['description'] }}</div>
                        <div class="price">NT${{ number_format($plan['price']) }}</div>
                        <div class="description">
                            @if($months == 12)
                                最優惠
                            @elseif($months == 6)
                                <span class="badge-popular">熱門</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- 完整功能列表 -->
            <div style="margin: 30px 0;">
                <h3 style="color: #667eea; text-align: center;">🌟 訂閱後可享完整功能</h3>
                <div class="benefits-list">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <h5 style="color: #667eea; margin-bottom: 10px;">📊 數據分析</h5>
                            <ul style="padding-left: 20px; margin: 0;">
                                <li>進階營運報表</li>
                                <li>趨勢分析圖表</li>
                                <li>客戶行為分析</li>
                            </ul>
                        </div>
                        <div>
                            <h5 style="color: #667eea; margin-bottom: 10px;">🎯 行銷工具</h5>
                            <ul style="padding-left: 20px; margin: 0;">
                                <li>客戶分群管理</li>
                                <li>精準行銷推播</li>
                                <li>促銷活動設定</li>
                            </ul>
                        </div>
                        <div>
                            <h5 style="color: #667eea; margin-bottom: 10px;">🔧 系統功能</h5>
                            <ul style="padding-left: 20px; margin: 0;">
                                <li>進階系統設定</li>
                                <li>API 介面支援</li>
                                <li>資料匯出功能</li>
                            </ul>
                        </div>
                        <div>
                            <h5 style="color: #667eea; margin-bottom: 10px;">🛡️ 技術支援</h5>
                            <ul style="padding-left: 20px; margin: 0;">
                                <li>優先客戶支援</li>
                                <li>線上客服服務</li>
                                <li>定期系統更新</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 行動按鈕 -->
            <div class="button-container">
                <a href="{{ $subscriptionUrl }}" class="cta-button">
                    🚀 立即訂閱
                </a>
                <a href="{{ config('app.url') }}/features" class="cta-button secondary">
                    📋 了解更多
                </a>
            </div>

            <!-- 限時優惠 -->
            <div style="background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h4 style="margin-top: 0; text-align: center;">🔥 試用戶專屬優惠</h4>
                <div style="text-align: center;">
                    <p style="margin: 10px 0; font-size: 18px;">
                        <strong>限時優惠：</strong>在試用期結束前訂閱，<br>
                        即可獲得 <strong>額外 7 天免費使用</strong>！
                    </p>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">
                        優惠碼將在結帳時自動套用
                    </p>
                </div>
            </div>

            <!-- 常見問題 -->
            <div style="margin: 30px 0;">
                <h4 style="color: #6c757d; text-align: center;">❓ 常見問題</h4>
                <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px;">
                    <div style="margin-bottom: 15px;">
                        <strong>Q: 可以隨時取消訂閱嗎？</strong><br>
                        A: 可以，您隨時可以在訂閱管理頁面取消訂閱，已付費用會按比例退費。
                    </div>
                    <div style="margin-bottom: 15px;">
                        <strong>Q: 支援哪些付款方式？</strong><br>
                        A: 支援信用卡、銀行轉帳、超商代碼等多種付款方式。
                    </div>
                    <div>
                        <strong>Q: 資料會在試用期結束後刪除嗎？</strong><br>
                        A: 不會，您的資料會安全保存 90 天，期間隨時可以訂閱恢復完整功能。
                    </div>
                </div>
            </div>
        </div>

        <!-- 頁尾區域 -->
        <div class="footer">
            <p>感謝您試用 592meal 美食訂閱服務</p>
            <div class="footer-links">
                <a href="{{ config('app.url') }}">官網首頁</a>
                <a href="{{ $subscriptionUrl }}">立即訂閱</a>
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