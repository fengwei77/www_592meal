@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- 頁面標題 -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-2">
                <i class="fas fa-file-invoice text-primary me-2"></i>
                訂單詳情
            </h2>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 基本訂單資訊 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        基本資訊
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td style="width: 25%" class="fw-bold">訂單編號</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $order->order_number }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">訂閱月數</td>
                                    <td>{{ $order->months }} 個月</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">單價</td>
                                    <td>NT$ {{ number_format($order->unit_price) }}/月</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">總金額</td>
                                    <td class="text-danger fw-bold fs-5">NT$ {{ number_format($order->total_amount) }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">訂閱狀態</td>
                                    <td>
                                        <span class="badge bg-{{ $order->getStatusColor() }}">
                                            {{ $order->getStatusLabel() }}
                                        </span>
                                    </td>
                                </tr>
                                @if($order->notes)
                                <tr>
                                    <td class="fw-bold">備註</td>
                                    <td>{{ $order->notes }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 時間資訊 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        時間資訊
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td style="width: 25%" class="fw-bold">建立時間</td>
                                    <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @if($order->expire_date)
                                <tr>
                                    <td class="fw-bold">付款期限</td>
                                    <td>
                                        {{ $order->expire_date->format('Y-m-d H:i') }}
                                        <span class="text-muted ms-2">({{ $order->getExpireTimeRemaining() }})</span>
                                    </td>
                                </tr>
                                @endif
                                @if($order->paid_at)
                                <tr>
                                    <td class="fw-bold">付款時間</td>
                                    <td>{{ $order->paid_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @endif
                                @if($order->payment_type)
                                <tr>
                                    <td class="fw-bold">付款方式</td>
                                    <td>{{ $order->getPaymentTypeLabel() }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 付款日誌 -->
            @if($paymentLogs->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        付款日誌
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($paymentLogs as $log)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-{{ $log->getRtnCodeColor() }}"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                {{ $log->getRtnCodeLabel() }}
                                                @if($log->simulate_paid)
                                                    <span class="badge bg-info ms-2">模擬</span>
                                                @endif
                                            </h6>
                                            <p class="text-muted mb-1">{{ $log->rtn_msg }}</p>
                                            <small class="text-muted">
                                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                                @if($log->merchant_id)
                                                | 商家編號: {{ $log->merchant_id }}
                                                @endif
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            @if($log->isPaymentSuccess())
                                                <span class="badge bg-success">付款成功</span>
                                            @elseif($log->isNumberGeneratedSuccess())
                                                <span class="badge bg-info">取號成功</span>
                                            @else
                                                <span class="badge bg-danger">失敗</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 付款資訊 (ATM/CVS/BARCODE) -->
                                    @if($paymentInfo = $log->getPaymentInfo())
                                        <div class="alert alert-info mt-3 mb-0">
                                            <h6 class="alert-heading">付款資訊</h6>
                                            <div class="row">
                                                @switch($paymentInfo['type'])
                                                    @case('ATM')
                                                        <div class="col-md-6">
                                                            <strong>銀行代碼:</strong> {{ $paymentInfo['bank_code'] }}<br>
                                                            <strong>虛擬帳號:</strong> {{ $paymentInfo['virtual_account'] }}<br>
                                                            <strong>繳費期限:</strong> {{ $paymentInfo['expire_date'] }}
                                                        </div>
                                                        @break
                                                    @case('CVS')
                                                        <div class="col-md-6">
                                                            <strong>繳費代碼:</strong> {{ $paymentInfo['payment_no'] }}<br>
                                                            <strong>繳費期限:</strong> {{ $paymentInfo['expire_date'] }}
                                                        </div>
                                                        @break
                                                    @case('BARCODE')
                                                        <div class="col-md-12">
                                                            <strong>條碼:</strong><br>
                                                            <span class="font-monospace">
                                                                {{ $paymentInfo['barcode1'] }}<br>
                                                                {{ $paymentInfo['barcode2'] }}<br>
                                                                {{ $paymentInfo['barcode3'] }}
                                                            </span>
                                                            <br>
                                                            <strong>繳費期限:</strong> {{ $paymentInfo['expire_date'] }}
                                                        </div>
                                                        @break
                                                @endswitch
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 快速操作 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        快速操作
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($order->status === 'pending' && !$order->isExpired())
                            <a href="{{ route('subscription.confirm', $order) }}" class="btn btn-success">
                                <i class="fas fa-credit-card me-1"></i>
                                立即付款
                            </a>
                        @endif

                        @if($order->canRepay())
                            <a href="{{ route('subscription.repay', $order) }}" class="btn btn-primary">
                                <i class="fas fa-redo me-1"></i>
                                重新繳費
                            </a>
                        @endif

                        @if($order->status === 'pending' && !$order->isExpired())
                            <form method="POST"
                                  action="{{ route('subscription.cancel', $order) }}"
                                  onsubmit="return confirm('確定要取消這筆訂單嗎？');">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-times me-1"></i>
                                    取消訂單
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('subscription.history') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            返回列表
                        </a>
                    </div>
                </div>
            </div>

            <!-- 狀態說明 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        狀態說明
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <span class="badge bg-warning">待繳費</span>
                        </h6>
                        <p class="text-muted small mb-0">訂單已建立，請在期限內完成付款。</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-success">
                            <span class="badge bg-success">已付款</span>
                        </h6>
                        <p class="text-muted small mb-0">付款完成，訂閱已開通。</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-danger">
                            <span class="badge bg-danger">已過期</span>
                        </h6>
                        <p class="text-muted small mb-0">付款期限已過，需要重新建立訂單。</p>
                    </div>
                    <div>
                        <h6 class="text-secondary">
                            <span class="badge bg-secondary">已取消</span>
                        </h6>
                        <p class="text-muted small mb-0">訂單已被取消。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid white;
}

.timeline-content {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-marker.bg-success {
    background-color: #198754;
}

.timeline-marker.bg-info {
    background-color: #0dcaf0;
}

.timeline-marker.bg-danger {
    background-color: #dc3545;
}

.timeline-marker.bg-warning {
    background-color: #ffc107;
}
</style>
@endpush