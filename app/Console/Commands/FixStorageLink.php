<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixStorageLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:fix-link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修復 Docker 環境下的 storage 連結問題';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('正在修復 Storage 連結...');

        $publicPath = public_path();
        $storageLink = $publicPath . '/storage';
        $targetPath = storage_path('app/public');

        // 移除現有的連結或目錄
        if (file_exists($storageLink)) {
            if (is_link($storageLink)) {
                unlink($storageLink);
                $this->info('已移除現有的符號連結');
            } else {
                $this->warn('Storage 目錄存在但不是連結，正在備份...');
                rename($storageLink, $storageLink . '.backup.' . time());
            }
        }

        // 建立新的符號連結
        if (symlink($targetPath, $storageLink)) {
            $this->info('✅ Storage 連結建立成功！');
            $this->info('連結路徑: ' . $storageLink . ' -> ' . $targetPath);
        } else {
            $this->error('❌ Storage 連結建立失敗！');
            return 1;
        }

        // 檢查連結是否正確
        if (is_link($storageLink) && readlink($storageLink) === $targetPath) {
            $this->info('✅ 連結驗證成功！');
        } else {
            $this->error('❌ 連結驗證失敗！');
            return 1;
        }

        // 設定正確的權限
        $this->call('storage:link');

        $this->info('🎉 Storage 連結修復完成！');
        return 0;
    }
}
