<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>前往付款 - 592Meal訂閱服務</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #FFE5B4 0%, #FFDAB9 50%, #FFE4B5 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 60px;
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo h1 {
            color: #333;
            margin: 10px 0;
            font-size: 28px;
        }
        .logo p {
            color: #666;
            margin: 0;
        }
        .order-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        .order-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: center;
        }
        .order-detail-row:last-child {
            margin-bottom: 0;
        }
        .order-label {
            font-weight: 600;
            color: #555;
        }
        .order-value {
            font-weight: 700;
            color: #333;
            font-size: 18px;
        }
        .amount {
            color: #e74c3c;
            font-size: 24px;
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            margin-top: 20px;
        }
        .ecpay-logo {
            font-size: 12px;
            color: #999;
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="logo">
            <h1>💳 592Meal</h1>
            <p>線上訂閱服務</p>
        </div>

        <div class="loading-spinner"></div>
        <h3 style="text-align: center; margin: 20px 0; color: #333;">正在前往綠界金流...</h3>
        <p style="text-align: center; margin: 10px 0; color: #666; font-size: 14px;">將於5秒後自動跳轉至付款頁面</p>

        <div class="order-details">
            <div class="order-detail-row">
                <span class="order-label">訂單編號</span>
                <span class="order-value">{{ $order->order_number }}</span>
            </div>
            <div class="order-detail-row">
                <span class="order-label">訂閱方案</span>
                <span class="order-value">{{ $order->months }} 個月</span>
            </div>
            <div class="order-detail-row">
                <span class="order-label">付款金額</span>
                <span class="order-value amount">NT$ {{ number_format($order->total_amount) }}</span>
            </div>
        </div>

        {!! $paymentForm !!}

        <div class="warning-message">
            ⚠️ 請在付款期限內完成付款，付款成功後立即開通服務
        </div>

        <div class="ecpay-logo">
            安全付款由綠界科技提供
        </div>
    </div>

    <script>
        // 自動提交表單（延遲5秒讓用戶看到頁面）
        setTimeout(function() {
            document.getElementById('ecpayPaymentForm').submit();
        }, 5000);
    </script>
</body>
</html>