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
        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique()->comment('訂單編號');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('老闆用戶ID');
            $table->tinyInteger('months')->unsigned()->comment('訂閱月數');
            $table->decimal('unit_price', 10, 0)->default(50)->comment('月費單價');
            $table->decimal('total_amount', 10, 0)->comment('總金額');

            // 訂單狀態
            $table->enum('status', ['pending', 'paid', 'expired', 'cancelled'])
                  ->default('pending')
                  ->comment('訂單狀態');

            // 綠界金流相關
            $table->string('ecpay_trade_no', 50)->nullable()->comment('綠界交易編號');
            $table->string('payment_type', 50)->nullable()->comment('付款方式');
            $table->datetime('payment_date')->nullable()->comment('付款時間');

            // 時間控制
            $table->datetime('expire_date')->comment('訂單過期時間');
            $table->datetime('paid_at')->nullable()->comment('付款完成時間');

            // 訂閱處理
            $table->datetime('subscription_start_date')->nullable()->comment('訂閱開始日期');
            $table->datetime('subscription_end_date')->nullable()->comment('訂閱結束日期');

            // 備註
            $table->text('notes')->nullable()->comment('備註');

            $table->timestamps();

            // 索引
            $table->index('user_id');
            $table->index('status');
            $table->index('order_number');
            $table->index('ecpay_trade_no');
            $table->index('expire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};