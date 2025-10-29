<?php

namespace App\Console\Commands;

use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateStoreSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stores:update-slugs {--force : 強制重新生成所有slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '為所有現有店家生成store_slug_name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('開始更新店家 slug...');

        $force = $this->option('force');
        $updatedCount = 0;

        try {
            DB::beginTransaction();

            $stores = Store::all();
            $totalStores = $stores->count();

            $this->info("找到 {$totalStores} 家店家");

            $progressBar = $this->output->createProgressBar($totalStores);
            $progressBar->start();

            foreach ($stores as $store) {
                // 如果已經有 slug 且不是強制模式，則跳過
                if (!$force && !empty($store->store_slug_name)) {
                    $progressBar->advance();
                    continue;
                }

                $oldSlug = $store->store_slug_name;
                $newSlug = $store->getStoreSlugAttribute();

                if ($oldSlug !== $newSlug) {
                    $store->update(['store_slug_name' => $newSlug]);
                    $updatedCount++;

                    $this->line("");
                    $this->info("店家: {$store->name} (ID: {$store->id})");
                    $this->info("  舊 slug: " . ($oldSlug ?: '空'));
                    $this->info("  新 slug: {$newSlug}");
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line("");

            DB::commit();

            $this->info("✅ 成功更新 {$updatedCount} 家店家的 slug");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ 更新失敗: " . $e->getMessage());
            Log::error('Update store slugs failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
}