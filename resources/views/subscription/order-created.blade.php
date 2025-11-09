@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- 頁面標題 -->
    <div class="row mb-4">
        <div class="col-md-12 text-center">
            <div class="success-icon mb-3">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
            </div>
            <h2 class="mb-3">
                訂單建立成功！
            </h2>
            <p class="text-muted">
                您的訂單紀錄已成功建立，訂單編號：{{ $order->order_number }}
            </p>
        </div>
    </div>

    <!-- 訂單詳情 -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice me-2"></i>
                        訂單詳情
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>訂單編號：</strong><br>
                                <span class="text-primary">{{ $order->order_number }}</span>
                            </p>
                            <p class="mb-2">
                                <strong>訂閱方案：</strong><br>
                                {{ $order->months }} 個月
                            </p>
                            <p class="mb-2">
                                <strong>訂單狀態：</strong><br>
                                <span class="badge bg-warning text-dark">{{ $order->getStatusLabel() }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>訂單金額：</strong><br>
                                <span class="text-danger fs-4">NT$ {{ number_format($order->total_amount) }}</span>
                            </p>
                            <p class="mb-2">
                                <strong>建立時間：</strong><br>
                                {{ $order->created_at->format('Y-m-d H:i:s') }}
                            </p>
                            <p class="mb-2">
                                <strong>付款期限：</strong><br>
                                <span class="text-info">{{ $order->expire_date->format('Y-m-d H:i:s') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 確認框 -->
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-credit-card me-2"></i>
                        立即前往付款？
                    </h5>
                    <p class="card-text text-muted mb-4">
                        您可以立即前往付款頁面完成訂閱，或稍後在「訂閱歷史」中找到此訂單進行付款。
                    </p>

                    <!-- 確認按鈕 -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button type="button"
                                id="confirmPaymentBtn"
                                class="btn btn-success btn-lg me-md-2"
                                data-payment-url="{{ route('subscription.confirm', $order) }}">
                            <i class="fas fa-shopping-cart me-2"></i>
                            立即付款
                        </button>
                        <a href="{{ route('subscription.history') }}"
                           class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-clock me-2"></i>
                            稍後付款
                        </a>
                    </div>

                    <!-- 警告訊息 -->
                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small>
                            請注意：訂單將於 {{ $order->expire_date->format('Y-m-d H:i') }} 過期，過期後需重新建立訂單。
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 其他選項 -->
    <div class="row">
        <div class="col-md-12 text-center">
            <a href="{{ route('subscription.index') }}" class="btn btn-outline-primary me-2">
                <i class="fas fa-plus-circle me-1"></i>
                建立新訂單
            </a>
            <a href="{{ route('subscription.history') }}" class="btn btn-outline-info">
                <i class="fas fa-history me-1"></i>
                查看訂閱歷史
            </a>
        </div>
    </div>
</div>

<style>
.success-icon {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.btn {
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

/* 確認視窗樣式 */
.modal {
    display: none;
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.modal-footer {
    border-top: 1px solid #e5e7eb;
    padding-top: 15px;
    margin-top: 20px;
    text-align: right;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
}

.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    const modal = document.getElementById('confirmModal');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelPayment');
    const proceedBtn = document.getElementById('proceedPayment');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            showModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            hideModal();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            hideModal();
        });
    }

    if (proceedBtn) {
        proceedBtn.addEventListener('click', function() {
            const paymentUrl = confirmBtn.getAttribute('data-payment-url');
            window.location.href = paymentUrl;
        });
    }

    // 點擊模態框外部關閉
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            hideModal();
        }
    });

    function showModal() {
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    function hideModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
});
</script>

<!-- 確認視窗 -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <span id="closeModal" class="close">&times;</span>

        <div class="modal-header">
            <h4 class="modal-title">
                <i class="fas fa-credit-card me-2"></i>
                前往付款確認
            </h4>
        </div>

        <div class="modal-body">
            <p><strong>您即將前往綠界金流付款頁面</strong></p>
            <p class="text-muted mb-0">一旦離開此頁面，付款過程將由綠界金流處理。</p>
            <ul class="mt-3 mb-0">
                <li>請準備您的信用卡或其他付款方式</li>
                <li>付款完成後將自動返回此系統</li>
                <li>如遇問題，請聯繫客服支援</li>
            </ul>
        </div>

        <div class="modal-footer">
            <button type="button" id="cancelPayment" class="btn btn-secondary me-2">
                <i class="fas fa-times me-1"></i>
                取消
            </button>
            <button type="button" id="proceedPayment" class="btn btn-success">
                <i class="fas fa-check me-1"></i>
                確認前往
            </button>
        </div>
    </div>
</div>
@endsection