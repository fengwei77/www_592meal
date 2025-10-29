<?php

use Illuminate\Support\Facades\Session;
use Illuminate\Support\HtmlString;

if (!function_exists('captcha_img')) {
    /**
     * 生成驗證碼圖片的 HTML 標籤
     *
     * @return HtmlString
     */
    function captcha_img(): HtmlString
    {
        $url = url('/api/captcha?' . time());
        $html = '<img src="' . $url . '" alt="驗證碼" style="border: 1px solid #ddd; border-radius: 4px; cursor: pointer; width: 120px; height: 40px;" onclick="this.src=\'' . url('/api/captcha') . '?\' + Math.random();" title="點擊重新整理驗證碼">';

        return new HtmlString($html);
    }
}

if (!function_exists('captcha_check')) {
    /**
     * 驗證輸入的驗證碼是否正確
     *
     * @param string $value
     * @return bool
     */
    function captcha_check(string $value): bool
    {
        $correctCode = session('captcha_code');

        // 清除 session 中的驗證碼，防止重複使用
        Session::forget('captcha_code');

        // 不區分大小寫比較
        return strtoupper($value) === strtoupper($correctCode);
    }
}

if (!function_exists('captcha_rules')) {
    /**
     * 返回驗證碼驗證規則
     *
     * @return array
     */
    function captcha_rules(): array
    {
        return [
            'required',
            'string',
            'size:6',
            function ($attribute, $value, $fail) {
                if (!captcha_check($value)) {
                    $fail('驗證碼錯誤，請重新輸入。');
                }
            },
        ];
    }
}