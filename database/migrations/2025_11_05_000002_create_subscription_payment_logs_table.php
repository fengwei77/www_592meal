<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('subscription_orders')->onDelete('cascade')->comment('訂單ID');
            $table->string('order_number', 20)->comment('訂單編號');

            // 綠界回傳基本資料
            $table->string('merchant_id', 10)->comment('特店編號');
            $table->string('ecpay_trade_no', 50)->nullable()->comment('綠界交易編號');
            $table->integer('rtn_code')->comment('交易狀態');
            $table->string('rtn_msg', 200)->nullable()->comment('交易訊息');
            $table->decimal('trade_amt', 10, 0)->comment('交易金額');
            $table->datetime('payment_date')->nullable()->comment('付款時間');
            $table->string('payment_type', 50)->nullable()->comment('付款方式');
            $table->decimal('payment_type_charge_fee', 10, 2)->nullable()->comment('手續費');

            // ATM/CVS/BARCODE 專用欄位
            $table->string('bank_code', 10)->nullable()->comment('銀行代碼 (ATM)');
            $table->string('virtual_account', 16)->nullable()->comment('虛擬帳號 (ATM)');
            $table->string('payment_no', 14)->nullable()->comment('繳費代碼 (CVS)');
            $table->string('barcode1', 20)->nullable()->comment('條碼第一段 (BARCODE)');
            $table->string('barcode2', 20)->nullable()->comment('條碼第二段 (BARCODE)');
            $table->string('barcode3', 20)->nullable()->comment('條碼第三段 (BARCODE)');
            $table->datetime('expire_date')->nullable()->comment('繳費期限');

            // 系統處理
            $table->boolean('processed')->default(false)->comment('是否已處理');
            $table->datetime('processed_at')->nullable()->comment('處理時間');
            $table->string('check_mac_value', 255)->nullable()->comment('檢查碼');
            $table->json('raw_data')->nullable()->comment('完整回傳資料');

            // 模擬付款識別
            $table->boolean('simulate_paid')->default(false)->comment('是否為模擬付款');

            $table->timestamps();

            // 索引
            $table->index('order_id');
            $table->index('order_number');
            $table->index('processed');
            $table->index('ecpay_trade_no');
            $table->index('rtn_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payment_logs');
    }
};