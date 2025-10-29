<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 店員認證控制器
 * 提供簡易的密碼驗證功能，讓店員可以訪問訂單管理介面
 */
class StaffAuthController extends Controller
{
    /**
     * 顯示店員登入頁面
     */
    public function showLoginForm(Request $request, $storeSlug): View
    {
        $store = Store::where('store_slug_name', $storeSlug)
                      ->where('is_active', true)
                      ->firstOrFail();

        return view('store.staff.login', compact('store'));
    }

    /**
     * 處理店員登入
     */
    public function login(Request $request, $storeSlug)
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => '請輸入密碼',
        ]);

        $store = Store::where('store_slug_name', $storeSlug)
                      ->where('is_active', true)
                      ->firstOrFail();

        // 檢查店家是否設定了店員密碼
        if (empty($store->staff_password)) {
            return back()->withErrors([
                'password' => '此店家尚未設定店員密碼，請聯繫店家管理員'
            ])->withInput();
        }

        // 驗證密碼（明碼比對，因為是簡易系統）
        if ($request->input('password') === $store->staff_password) {
            // 驗證成功，設定 session
            session([
                'staff_authenticated' => true,
                'staff_store_id' => $store->id,
                'staff_store_slug' => $store->store_slug_name,
                'staff_login_time' => now()->toDateTimeString(),
            ]);

            return redirect()->route('admin.store.orders.index', $store->store_slug_name)
                ->with('success', '登入成功！歡迎使用訂單管理系統');
        }

        // 密碼錯誤
        return back()->withErrors([
            'password' => '密碼錯誤，請重新輸入'
        ])->withInput();
    }

    /**
     * 登出
     */
    public function logout(Request $request, $storeSlug)
    {
        session()->forget([
            'staff_authenticated',
            'staff_store_id',
            'staff_store_slug',
            'staff_login_time',
        ]);

        return redirect()->route('admin.store.staff.login', $storeSlug)
            ->with('success', '已成功登出');
    }
}
