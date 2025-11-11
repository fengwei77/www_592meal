<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPaymentLog;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class AdminSubscriptionController extends Controller
{
    private SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->middleware(['auth', 'role:super_admin']);
    }

    /**
     * 老闆訂閱管理首頁
     */
    public function index(Request $request)
    {
        $query = User::whereHas('roles', function ($q) {
            $q->where('name', 'store_owner');
        });

        // 搜尋功能
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where(function ($q) use ($status) {
                switch ($status) {
                    case 'trial':
                        $q->where('is_trial_used', true)
                          ->where('trial_ends_at', '>', now());
                        break;
                    case 'active':
                        $q->where('subscription_ends_at', '>', now());
                        break;
                    case 'expired':
                        $q->where(function ($subQ) {
                            $subQ->where('subscription_ends_at', '<', now())
                               ->orWhereNull('subscription_ends_at');
                        });
                        break;
                }
            });
        }

        $users = $query->latest()->paginate(20);

        return view('admin.subscriptions.index', compact('users'));
    }

    /**
     * 顯示老闆的訂閱詳細資訊
     */
    public function show(User $user)
    {
        // 檢查是否為店家
        if (!$user->hasRole('store_owner')) {
            abort(404);
        }

        $subscriptionStats = $this->subscriptionService->getUserSubscriptionStats($user);
        $orders = $user->subscriptionOrders()
            ->with('paymentLogs')
            ->latest()
            ->paginate(15);

        return view('admin.subscriptions.show', compact('user', 'subscriptionStats', 'orders'));
    }

    /**
     * 手動延長訂閱
     */
    public function extend(Request $request, User $user)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:24',
            'reason' => 'required|string|max:255',
        ], [
            'months.required' => '請輸入延長月數',
            'months.min' => '至少延長1個月',
            'months.max' => '最多延長24個月',
            'reason.required' => '請輸入原因',
        ]);

        if (!$user->hasRole('store_owner')) {
            return back()->with('error', '此用戶不是店家');
        }

        try {
            DB::beginTransaction();

            // 延長訂閱
            $success = $this->subscriptionService->extendUserSubscription(
                $user,
                $request->input('months')
            );

            if (!$success) {
                throw new \Exception('延長訂閱失敗');
            }

            // 記錄管理員操作
            $adminUser = Auth::user();
            \Log::info('Admin extended user subscription', [
                'admin_id' => $adminUser->id,
                'admin_name' => $adminUser->name,
                'target_user_id' => $user->id,
                'target_user_name' => $user->name,
                'months' => $request->input('months'),
                'reason' => $request->input('reason'),
                'new_expiry_date' => $user->subscription_ends_at,
            ]);

            DB::commit();

            return back()
                ->with('success', "成功為 {$user->name} 延長 {$request->input('months')} 個月訂閱");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Admin failed to extend subscription', [
                'admin_id' => Auth::id(),
                'target_user_id' => $user->id,
                'months' => $request->input('months'),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', '延長訂閱失敗：' . $e->getMessage());
        }
    }

    /**
     * 手動調整到期日
     */
    public function adjustExpiry(Request $request, User $user)
    {
        $request->validate([
            'expiry_date' => 'required|date|after:today',
            'reason' => 'required|string|max:255',
        ], [
            'expiry_date.required' => '請選擇新的到期日',
            'expiry_date.date' => '請輸入有效的日期格式',
            'expiry_date.after' => '到期日必須是未來日期',
            'reason.required' => '請輸入調整原因',
        ]);

        if (!$user->hasRole('store_owner')) {
            return back()->with('error', '此用戶不是店家');
        }

        try {
            $newDate = Carbon::parse($request->input('expiry_date'));
            $oldDate = $user->subscription_ends_at;
            $reason = $request->input('reason');

            $success = $this->subscriptionService->adjustExpiryDate($user, $newDate, $reason);

            if (!$success) {
                throw new \Exception('調整到期日失敗');
            }

            // 記錄管理員操作
            $adminUser = Auth::user();
            \Log::info('Admin adjusted user subscription expiry date', [
                'admin_id' => $adminUser->id,
                'admin_name' => $adminUser->name,
                'target_user_id' => $user->id,
                'target_user_name' => $user->name,
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'reason' => $reason,
            ]);

            return back()
                ->with('success', "成功調整 {$user->name} 的到期日為 {$newDate->format('Y-m-d')}");
        } catch (\Exception $e) {
            \Log::error('Admin failed to adjust expiry date', [
                'admin_id' => Auth::id(),
                'target_user_id' => $user->id,
                'new_date' => $request->input('expiry_date'),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', '調整到期日失敗：' . $e->getMessage());
        }
    }

    /**
     * 初始化試用期
     */
    public function initializeTrial(Request $request, User $user)
    {
        if (!$user->hasRole('store_owner')) {
            return back()->with('error', '此用戶不是店家');
        }

        if ($user->is_trial_used) {
            return back()->with('error', '此用戶已經使用過試用期');
        }

        try {
            $success = $this->subscriptionService->initializeTrial($user);

            if (!$success) {
                throw new \Exception('初始化試用期失敗');
            }

            // 記錄管理員操作
            $adminUser = Auth::user();
            \Log::info('Admin initialized user trial', [
                'admin_id' => $adminUser->id,
                'admin_name' => $adminUser->name,
                'target_user_id' => $user->id,
                'target_user_name' => $user->name,
                'trial_ends_at' => $user->trial_ends_at,
            ]);

            return back()
                ->with('success', "成功為 {$user->name} 開通試用期");
        } catch (\Exception $e) {
            \Log::error('Admin failed to initialize trial', [
                'admin_id' => Auth::id(),
                'target_user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', '初始化試用期失敗：' . $e->getMessage());
        }
    }

    /**
     * 顯示訂單詳細資訊
     */
    public function showOrder(SubscriptionOrder $order)
    {
        $order->load(['user', 'paymentLogs']);

        return view('admin.subscriptions.show-order', compact('order'));
    }

    /**
     * 顯示付款日誌
     */
    public function showPaymentLogs(Request $request)
    {
        $query = SubscriptionPaymentLog::with(['order.user'])
            ->latest();

        // 篩選條件
        if ($request->filled('order_number')) {
            $query->where('order_number', $request->input('order_number'));
        }

        if ($request->filled('user_name')) {
            $query->whereHas('order.user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('user_name') . '%');
            });
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->input('payment_type'));
        }

        if ($request->filled('rtn_code')) {
            $query->where('rtn_code', $request->input('rtn_code'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $logs = $query->paginate(20);

        return view('admin.subscriptions.payment-logs', compact('logs'));
    }

    /**
     * 系統訂閱統計
     */
    public function statistics()
    {
        // 訂閱統計
        $stats = [
            'total_users' => User::whereHas('roles', function ($q) {
                $q->where('name', 'store_owner');
            })->count(),

            'trial_users' => User::whereHas('roles', function ($q) {
                $q->where('name', 'store_owner');
            })->where('is_trial_used', true)
              ->where('trial_ends_at', '>', now())
              ->count(),

            'active_users' => User::whereHas('roles', function ($q) {
                $q->where('name', 'store_owner');
            })->where('subscription_ends_at', '>', now())
              ->count(),

            'expired_users' => User::whereHas('roles', function ($q) {
                $q->where('name', 'store_owner');
            })->where(function ($q) {
                $q->where('subscription_ends_at', '<', now())
                  ->orWhereNull('subscription_ends_at');
            })->count(),
        ];

        // 訂單統計
        $orderStats = [
            'total_orders' => SubscriptionOrder::count(),
            'pending_orders' => SubscriptionOrder::where('status', 'pending')->count(),
            'paid_orders' => SubscriptionOrder::where('status', 'paid')->count(),
            'expired_orders' => SubscriptionOrder::where('status', 'expired')->count(),
            'cancelled_orders' => SubscriptionOrder::where('status', 'cancelled')->count(),
            'total_revenue' => SubscriptionOrder::where('status', 'paid')->sum('total_amount'),
        ];

        // 最近7天的訂單趨勢
        $recentOrders = SubscriptionOrder::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 付款方式統計
        $paymentTypeStats = SubscriptionPaymentLog::whereNotNull('payment_type')
            ->where('rtn_code', 1)
            ->select('payment_type', \DB::raw('COUNT(*) as count'))
            ->groupBy('payment_type')
            ->get();

        return view('admin.subscriptions.statistics', compact('stats', 'orderStats', 'recentOrders', 'paymentTypeStats'));
    }

    /**
     * 批量操作
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:extend_trial,send_reminder',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'months' => 'required_if:action,extend_trial|integer|min:1|max:24',
            'reason' => 'required|string|max:255',
        ]);

        $action = $request->input('action');
        $userIds = $request->input('user_ids');
        $users = User::whereIn('id', $userIds)->get();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                switch ($action) {
                    case 'extend_trial':
                        if (!$user->hasRole('store_owner')) {
                            $errors[] = "用戶 {$user->name} 不是店家";
                            $errorCount++;
                            continue 2;
                        }

                        if (!$this->subscriptionService->extendUserSubscription($user, $request->input('months'))) {
                            $errors[] = "用戶 {$user->name} 延長訂閱失敗";
                            $errorCount++;
                            continue 2;
                        }
                        $successCount++;
                        break;

                    case 'send_reminder':
                        // 這裡可以實作發送提醒郵件的功能
                        $successCount++;
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "用戶 {$user->name} 處理失敗：" . $e->getMessage();
                $errorCount++;
            }
        }

        // 記錄批量操作
        \Log::info('Admin bulk subscription action', [
            'admin_id' => Auth::id(),
            'action' => $action,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'user_ids' => $userIds,
        ]);

        $message = "批量操作完成：成功 {$successCount} 個，失敗 {$errorCount} 個";
        if (!empty($errors)) {
            $message .= "。錯誤詳情：" . implode('; ', $errors);
        }

        return back()->with($errorCount > 0 ? 'warning' : 'success', $message);
    }
}