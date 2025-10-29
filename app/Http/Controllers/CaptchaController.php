<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    /**
     * 生成簡單的驗證碼圖片（不依賴 FreeType）
     */
    public function generate()
    {
        try {
            // 生成 6 位數字驗證碼
            $code = $this->generateRandomCode();

            // 將驗證碼存儲到 session
            Session::flash('captcha_code', $code);

            // 創建圖像
            $width = 120;
            $height = 40;
            $image = imagecreatetruecolor($width, $height);

            if (!$image) {
                throw new Exception("無法創建圖像");
            }

            // 設置顏色
            $bgColor = imagecolorallocate($image, 240, 240, 240);
            $textColor = imagecolorallocate($image, 50, 50, 50);
            $lineColor = imagecolorallocate($image, 200, 200, 200);

            // 填充背景
            imagefill($image, 0, 0, $bgColor);

            // 添加干擾線
            for ($i = 0; $i < 5; $i++) {
                imageline(
                    $image,
                    rand(0, $width),
                    rand(0, $height),
                    rand(0, $width),
                    rand(0, $height),
                    $lineColor
                );
            }

            // 使用內建字體寫入驗證碼（不需要 TTF）
            $fontSize = 5;
            $textWidth = strlen($code) * imagefontwidth($fontSize);
            $textHeight = imagefontheight($fontSize);
            $x = ($width - $textWidth) / 2;
            $y = ($height - $textHeight) / 2;

            imagestring($image, $fontSize, $x, $y, $code, $textColor);

            // 設置 HTTP 頭
            header('Content-Type: image/png');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // 輸出圖像
            imagepng($image);
            imagedestroy($image);

            exit;

        } catch (Exception $e) {
            // 錯誤處理 - 輸出錯誤信息圖片
            $errorImage = imagecreatetruecolor(300, 60);
            $bgColor = imagecolorallocate($errorImage, 255, 255, 255);
            $textColor = imagecolorallocate($errorImage, 255, 0, 0);
            imagefill($errorImage, 0, 0, $bgColor);

            $errorMsg = substr($e->getMessage(), 0, 30);
            imagestring($errorImage, 3, 10, 15, 'Error: ' . $errorMsg, $textColor);
            imagestring($errorImage, 2, 10, 35, 'File: ' . basename($e->getFile()), $textColor);

            header('Content-Type: image/png');
            imagepng($errorImage);
            imagedestroy($errorImage);
            exit;
        }
    }

    /**
     * 驗證用戶輸入的驗證碼
     */
    public function verify(Request $request)
    {
        $userInput = $request->input('captcha');
        $correctCode = session('captcha_code');

        // 清除 session 中的驗證碼
        Session::forget('captcha_code');

        return response()->json([
            'success' => $userInput === $correctCode,
            'message' => $userInput === $correctCode ? '驗證碼正確' : '驗證碼錯誤，請重新輸入'
        ]);
    }

    /**
     * 生成隨機驗證碼
     */
    private function generateRandomCode()
    {
        // 只使用數字和容易區分的字母
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }
}