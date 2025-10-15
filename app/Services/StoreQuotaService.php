<?php

namespace App\Services;

use App\Models\User;
use App\Models\StoreQuotaLog;
use Illuminate\Support\Facades\Request;

class StoreQuotaService
{
    /**
     * Initialize a new user's store quota based on the system default.
     */
    public function initializeUserQuota(User $user): void
    {
        // Get the default quota from the config file, defaulting to 1 if not set.
        $defaultQuota = config('bmad.subscription.default_max_stores', 1);

        $user->max_stores = $defaultQuota;
        $user->save();

        // Log the initialization action.
        StoreQuotaLog::create([
            'user_id' => $user->id,
            'old_max_stores' => 0,
            'new_max_stores' => $defaultQuota,
            'adjustment' => $defaultQuota,
            'reason' => '系統預設配額', // System default quota
            'action_type' => 'system_reset',
            'performed_by' => null, // System action
            'performed_by_name' => 'System',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
