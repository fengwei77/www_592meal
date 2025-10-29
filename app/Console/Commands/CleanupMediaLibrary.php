<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CleanupMediaLibrary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理媒體庫中的孤立檔案和損壞的媒體記錄';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('開始清理媒體庫...');

        // 1. 清理孤立媒體記錄（沒有對應模型的記錄）
        $this->cleanupOrphanedMedia();

        // 2. 清理不存在的檔案記錄
        $this->cleanupMissingFiles();

        // 3. 重新生成媒體轉換
        $this->regenerateMediaConversions();

        // 4. 清理暫存檔案
        $this->cleanupTempFiles();

        $this->info('媒體庫清理完成！');
    }

    private function cleanupOrphanedMedia()
    {
        $this->info('清理孤立媒體記錄...');

        $orphanedCount = 0;

        // 檢查 Store 的媒體
        $storeMedia = Media::where('model_type', Store::class)->get();
        foreach ($storeMedia as $media) {
            if (!Store::find($media->model_id)) {
                $media->delete();
                $orphanedCount++;
            }
        }

        // 檢查 MenuItem 的媒體
        $menuItemMedia = Media::where('model_type', MenuItem::class)->get();
        foreach ($menuItemMedia as $media) {
            if (!MenuItem::find($media->model_id)) {
                $media->delete();
                $orphanedCount++;
            }
        }

        $this->info("刪除了 {$orphanedCount} 個孤立媒體記錄");
    }

    private function cleanupMissingFiles()
    {
        $this->info('清理不存在的檔案記錄...');

        $missingCount = 0;
        $allMedia = Media::all();

        foreach ($allMedia as $media) {
            $fullPath = storage_path('app/public/' . $media->id . '/' . $media->file_name);
            if (!File::exists($fullPath)) {
                $this->warn("檔案不存在: {$media->file_name} (ID: {$media->id})");
                if ($this->option('force') || $this->confirm('刪除這個媒體記錄嗎？')) {
                    $media->delete();
                    $missingCount++;
                }
            }
        }

        $this->info("刪除了 {$missingCount} 個不存在檔案的媒體記錄");
    }

    private function regenerateMediaConversions()
    {
        $this->info('重新生成媒體轉換...');

        if ($this->option('force') || $this->confirm('是否重新生成所有媒體轉換？這可能需要一些時間。')) {
            $stores = Store::all();
            foreach ($stores as $store) {
                $this->line("處理店家: {$store->name}");
                $store->clearMediaCollection('store-logo');
                $store->clearMediaCollection('store-cover');
                $store->clearMediaCollection('store-photos');
            }

            $menuItems = MenuItem::all();
            foreach ($menuItems as $item) {
                $this->line("處理餐點: {$item->name}");
                $item->clearMediaCollection('menu-item-photos');
            }

            $this->info('媒體轉換清理完成');
        }
    }

    private function cleanupTempFiles()
    {
        $this->info('清理暫存檔案...');

        $tempPath = storage_path('app/public/tmp');
        if (File::exists($tempPath)) {
            $files = File::allFiles($tempPath);
            $deletedCount = 0;

            foreach ($files as $file) {
                // 刪除超過 24 小時的暫存檔案
                if ($file->getMTime() < time() - 86400) {
                    File::delete($file->getPathname());
                    $deletedCount++;
                }
            }

            $this->info("刪除了 {$deletedCount} 個暫存檔案");
        } else {
            $this->info('暫存目錄不存在，跳過');
        }
    }
}
