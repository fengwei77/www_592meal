<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactSubmissionNotification;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * 顯示聯絡表單頁面
     */
    public function index()
    {
        return view('frontend.contact');
    }

    /**
     * 處理聯絡表單提交
     */
    public function submit(Request $request)
    {
        // 驗證表單數據
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ], [
            'name.required' => '姓名欄位為必填',
            'name.max' => '姓名不能超過 100 個字元',
            'email.required' => '電子郵件欄位為必填',
            'email.email' => '請輸入有效的電子郵件地址',
            'email.max' => '電子郵件不能超過 255 個字元',
            'phone.max' => '電話號碼不能超過 20 個字元',
            'subject.required' => '主題欄位為必填',
            'subject.max' => '主題不能超過 255 個字元',
            'message.required' => '訊息內容欄位為必填',
            'message.max' => '訊息內容不能超過 2000 個字元',
        ]);

        if ($validator->fails()) {
            return redirect()->route('frontend.contact')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // 創建聯絡記錄
            $contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => Contact::STATUS_PENDING,
                'send_notification' => true,
            ]);

            // 發送通知郵件給管理員
            try {
                Mail::to(config('mail.admin_email', 'admin@592meal.online'))
                    ->send(new ContactSubmissionNotification($contact));
            } catch (\Exception $e) {
                // 郵件發送失敗不影響表單提交
                \Log::error('聯絡表單郵件發送失敗: ' . $e->getMessage());
            }

            return redirect()->route('frontend.contact')
                ->with('success', '您的訊息已成功送出！我們會盡快回覆您。');

        } catch (\Exception $e) {
            \Log::error('聯絡表單提交失敗: ' . $e->getMessage());

            return redirect()->route('frontend.contact')
                ->with('error', '抱歉，系統發生錯誤，請稍後再試。')
                ->withInput();
        }
    }
}