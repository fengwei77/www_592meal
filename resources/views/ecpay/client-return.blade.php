<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>測試付款完成 - ECPay 測試</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .result-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .result-header {
            background: #28a745;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .result-body {
            padding: 40px;
        }
        .success-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="result-header">
            <div class="success-icon">✓</div>
            <h2>測試付款已完成</h2>
            <p class="mb-0">您已返回 592Meal 測試環境</p>
        </div>

        <div class="result-body text-center">
            <h4>感謝您測試 ECPay 金流整合</h4>
            <p class="text-muted mb-4">這是一個測試交易，不會產生實際扣款</p>

            <div class="d-grid gap-3 d-md-block">
                <a href="{{ route('ecpay.test.index') }}" class="btn btn-primary me-md-2">
                    <i class="bi bi-arrow-left"></i> 返回測試頁面
                </a>
                <a href="{{ url('/manage-subscription') }}" class="btn btn-secondary">
                    <i class="bi bi-house"></i> 回到後台
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>