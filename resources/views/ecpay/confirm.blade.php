<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>前往付款 - ECPay 測試</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 600px;
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
        .test-banner {
            background: #ffc107;
            border: 1px solid #ffb300;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            margin-bottom: 30px;
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
        .submit-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }
        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
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
        .debug-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="logo">
            <h1>💳 ECPay 測試</h1>
            <p>592Meal 金流測試環境</p>
        </div>

        <div class="test-banner">
            ⚠️ 這是測試環境，不會產生實際扣款
        </div>

        @if(isset($params))
        <div class="debug-info">
            <strong>調試資訊：</strong><br>
            交易編號: {{ $merchantTradeNo }}<br>
            付款URL: {{ config('ecpay.test_mode') ? '測試環境' : '正式環境' }}<br>
            回傳URL: {{ route('ecpay.test.return') }}
        </div>
        @endif

        <div class="loading-spinner"></div>
        <h3 style="text-align: center; margin: 20px 0; color: #333;">正在前往綠界金流測試環境</h3>

        <div class="order-details">
            <div class="order-detail-row">
                <span class="order-label">交易編號</span>
                <span class="order-value">{{ $merchantTradeNo }}</span>
            </div>
            @if(isset($params['ItemName']))
            <div class="order-detail-row">
                <span class="order-label">商品名稱</span>
                <span class="order-value">{{ $params['ItemName'] }}</span>
            </div>
            @endif
            @if(isset($params['TotalAmount']))
            <div class="order-detail-row">
                <span class="order-label">付款金額</span>
                <span class="order-value amount">NT$ {{ number_format($params['TotalAmount']) }}</span>
            </div>
            @endif
        </div>

        {!! $paymentForm !!}

        <button type="button" class="submit-button" onclick="document.getElementById('ecpayPaymentForm').submit()">
            立即前往測試付款
        </button>

        <div class="warning-message">
            ⚠️ 這是測試交易，請使用測試信用卡號碼進行付款測試
        </div>

        <div class="ecpay-logo">
            安全付款由綠界科技提供
        </div>
    </div>

    <script>
        // 自動提交表單（延遲2秒讓用戶看到頁面）
        setTimeout(function() {
            console.log('Auto-submitting ECPay test form...');
            document.getElementById('ecpayPaymentForm').submit();
        }, 2000);
    </script>
</body>
</html>