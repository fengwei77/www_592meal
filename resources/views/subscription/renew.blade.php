@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- 頁面標題 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-0">
                <i class="fas fa-sync-alt text-success me-2"></i>
                592Meal 續約服務
            </h2>
            <p class="text-muted mb-0">延長您的訂閱方案，繼續享受專業餐廳管理工具</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('subscription.history') }}" class="btn btn-outline-primary">
                <i class="fas fa-history me-1"></i>
                訂閱歷史
            </a>
        </div>
    </div>

    <!-- 目前訂閱狀態 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>
                目前訂閱狀態
            </h5>
        </div>
        <div class="card-body">
            @if($user->isInTrialPeriod())
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fas fa-flask fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">試用期中</h6>
                        <div class="mb-1">
                            試用期至: {{ $user->trial_ends_at->format('Y年m月d日') }}
                        </div>
                        <div class="progress" style="height: 6px;">
                            <?php
                            $totalDays = $user->trial_ends_at->diffInDays($user->trial_ends_at->subDays(30));
                            $remainingDays = $user->getSubscriptionRemainingDays();
                            $percentage = ($remainingDays / $totalDays) * 100;
                            ?>
                            <div class="progress-bar" style="width: {{ $percentage }}%"></div>
                        </div>
                        <small class="text-muted">剩餘 {{ $remainingDays }} 天</small>
                    </div>
                </div>
            @elseif($user->hasActiveSubscription())
                <div class="alert alert-success d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">訂閱有效</h6>
                        <div class="mb-1">
                            到期日: {{ $user->subscription_ends_at->format('Y年m月d日') }}
                        </div>
                        <div class="mb-1">
                            已訂閱: {{ $subscriptionStats['total_months'] }} 個月
                        </div>
                        @if($user->isSubscriptionExpiringSoon())
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                即將於 {{ $user->getSubscriptionRemainingDays() }} 天後到期
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="fas fa-times-circle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">訂閱已過期</h6>
                        <p class="mb-0">請立即訂閱以繼續使用服務</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- 待付款訂單提醒 -->
    @if($pendingOrder)
    <div class="alert alert-warning mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">
                    <i class="fas fa-clock me-2"></i>
                    您有未付款的訂單
                </h6>
                <div class="mb-1">
                    訂單編號: {{ $pendingOrder->order_number }}
                </div>
                <div class="mb-1">
                    金額: NT$ {{ number_format($pendingOrder->total_amount) }}
                </div>
                <div class="text-muted">
                    繳費期限: {{ $pendingOrder->expire_date->format('Y-m-d H:i') }}
                    ({{ $pendingOrder->getExpireTimeRemaining() }})
                </div>
            </div>
            <div>
                <a href="{{ route('subscription.confirm', $pendingOrder) }}" class="btn btn-warning">
                    <i class="fas fa-credit-card me-1"></i>
                    立即付款
                </a>
                <a href="{{ route('subscription.index') }}" class="btn btn-outline-secondary ms-2">
                    查看詳情
                </a>
            </div>
        </div>
    </div>
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>溫馨提醒：</strong>您可以最多保留3筆待付款訂單。如果已經有3筆待付款訂單，系統會提醒您先完成部分訂單的付款或等待舊訂單過期。
    </div>
    @endif

    <!-- 訂閱方案選擇 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-shopping-cart me-2"></i>
                選擇訂閱方案
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('subscription.createOrder') }}" id="subscriptionForm">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="months" class="form-label fw-bold">
                                選擇訂閱月數
                            </label>
                            <select name="months" id="months" class="form-select form-select-lg" required>
                                @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ old('months') == $i ? 'selected' : '' }}>
                                    {{ $i }} 個月 - NT$ {{ $i * 50 }}
                                </option>
                                @endfor
                            </select>
                            <div class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                月費: NT$ 50，一次訂閱最多12個月
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-bold">總金額</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">NT$</span>
                                <input type="text" class="form-control" id="totalAmount" readonly value="50">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <h6 class="alert-heading">
                        <i class="fas fa-shield-alt me-2"></i>
                        付款說明
                    </h6>
                    <ul class="mb-0">
                        <li>支援信用卡、超商代碼、ATM轉帳等多種付款方式</li>
                        <li>訂單建立後有效期限為3天，請及時完成付款</li>
                        <li>付款完成後將立即開通訂閱服務</li>
                        <li>如有任何問題，請聯繫客服</li>
                    </ul>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-shopping-cart me-2"></i>
                        確認訂單
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 訂閱統計 -->
    @if($subscriptionStats['total_orders'] > 0)
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                訂閱統計
            </h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="border-end">
                        <h4 class="text-primary mb-1">{{ $subscriptionStats['total_orders'] }}</h4>
                        <small class="text-muted">總訂單數</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border-end">
                        <h4 class="text-success mb-1">{{ $subscriptionStats['paid_orders'] }}</h4>
                        <small class="text-muted">已付款訂單</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border-end">
                        <h4 class="text-info mb-1">{{ $subscriptionStats['total_months'] }}</h4>
                        <small class="text-muted">總訂閱月數</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <h4 class="text-warning mb-1">NT$ {{ number_format($subscriptionStats['total_amount']) }}</h4>
                    <small class="text-muted">總消費金額</small>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('months').addEventListener('change', function() {
    const months = parseInt(this.value);
    const totalAmount = months * 50;
    document.getElementById('totalAmount').value = totalAmount;
});
</script>
@endpush