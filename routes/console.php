<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * 排程任務：自動清理失敗工作
 *
 * 每天凌晨 2:00 自動清除超過 48 小時的失敗工作
 * 這可以防止 failed_jobs 資料表無限增長
 */
Schedule::command('queue:prune-failed --hours=48')
    ->daily()
    ->at('02:00')
    ->timezone('Asia/Taipei')
    ->runInBackground()
    ->onSuccess(function () {
        \Log::info('Failed jobs pruned successfully');
    })
    ->onFailure(function () {
        \Log::error('Failed to prune failed jobs');
    });

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
