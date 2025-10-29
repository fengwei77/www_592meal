<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FixStorageLink
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 只在開發環境或管理後台執行
        if (app()->environment('local') || str_contains($request->getHost(), 'cms.')) {
            $this->ensureStorageLink();
        }

        return $next($request);
    }

    /**
     * 確保 storage 連結正確
     */
    protected function ensureStorageLink()
    {
        $storageLink = public_path('storage');
        $targetPath = storage_path('app/public');

        // 檢查連結是否存在且正確
        if (!is_link($storageLink) || readlink($storageLink) !== $targetPath) {
            try {
                // 移除錯誤的連結或目錄
                if (file_exists($storageLink)) {
                    if (is_link($storageLink)) {
                        unlink($storageLink);
                    } else {
                        // 備份現有內容
                        rename($storageLink, $storageLink . '.backup.' . time());
                    }
                }

                // 建立正確的連結
                symlink($targetPath, $storageLink);

                Log::info('Storage 連結已自動修復');
            } catch (\Exception $e) {
                Log::error('自動修復 Storage 連結失敗: ' . $e->getMessage());
            }
        }
    }
}