<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="space-y-4">
        @php
            $user = auth()->user();
            $google2fa = new \PragmaRX\Google2FA\Google2FA();

            // 如果還沒有 secret，生成一個
            if (!$user->two_factor_secret) {
                $secret = $google2fa->generateSecretKey();
                $user->two_factor_secret = encrypt($secret);
                $user->save();
            } else {
                $secret = decrypt($user->two_factor_secret);
            }

            // 生成 QR Code URL
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            );
        @endphp

        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-2">設定步驟：</h3>
            <ol class="list-decimal list-inside space-y-2 text-sm">
                <li>下載 Google Authenticator 應用程式</li>
                <li>開啟應用程式並掃描下方的 QR Code</li>
                <li>輸入應用程式顯示的 6 位數驗證碼</li>
                <li>點擊「確認」完成設定</li>
            </ol>
        </div>

        <div class="flex justify-center">
            <div class="bg-white p-4 rounded-lg">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}"
                     alt="2FA QR Code"
                     class="w-48 h-48">
            </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
            <p class="text-sm font-semibold mb-1">手動輸入密鑰：</p>
            <code class="text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ $secret }}</code>
            <p class="text-xs mt-2 text-gray-600 dark:text-gray-400">如果無法掃描 QR Code，請使用此密鑰手動設定</p>
        </div>
    </div>
</x-dynamic-component>
