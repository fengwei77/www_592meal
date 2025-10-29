<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>驗證碼測試</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .captcha-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .captcha-image {
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .captcha-image:hover {
            opacity: 0.8;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .refresh-btn {
            background-color: #6c757d;
            padding: 8px 16px;
            font-size: 14px;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>驗證碼測試</h2>

        <form id="captchaForm">
            <div class="form-group">
                <label for="captcha">請輸入驗證碼：</label>
                <div class="captcha-container">
                    <input type="text" id="captcha" name="captcha" required maxlength="6" style="flex: 1;">
                    <img id="captchaImage" src="/api/captcha" alt="驗證碼" class="captcha-image">
                    <button type="button" onclick="refreshCaptcha()" class="refresh-btn">重新整理</button>
                </div>
            </div>

            <button type="submit">驗證</button>
        </form>

        <div id="result" class="result"></div>
    </div>

    <script>
        function refreshCaptcha() {
            const img = document.getElementById('captchaImage');
            // 添加時間戳參數來防止快取
            img.src = '/api/captcha?' + new Date().getTime();
            document.getElementById('captcha').value = '';
            document.getElementById('captcha').focus();
        }

        document.getElementById('captchaImage').addEventListener('click', refreshCaptcha);

        document.getElementById('captchaForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const captcha = document.getElementById('captcha').value;
            const resultDiv = document.getElementById('result');

            try {
                const response = await fetch('/api/captcha/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({ captcha: captcha })
                });

                const data = await response.json();

                resultDiv.style.display = 'block';

                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = data.message;
                    // 驗證成功後重新整理驗證碼
                    setTimeout(refreshCaptcha, 2000);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = data.message;
                    // 驗證失敗後重新整理驗證碼
                    refreshCaptcha();
                }
            } catch (error) {
                resultDiv.style.display = 'block';
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '發生錯誤：' + error.message;
            }
        });

        // 自動聚焦到驗證碼輸入框
        document.getElementById('captcha').focus();
    </script>
</body>
</html>