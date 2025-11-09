@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- 頁面標題 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-0">
                <i class="fas fa-history text-primary me-2"></i>
                訂閱歷史紀錄
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('subscription.index') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>
                訂閱服務
            </a>
        </div>
    </div>

    <!-- 訂閱統計 -->
    @if($subscriptionStats['total_orders'] > 0)
    <div class="card mb-4">
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

    <!-- 目前訂閱狀態 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>
                目前訂閱狀態
            </h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    @if($subscriptionStats['subscription_status'] === 'trial')
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-flask me-2"></i>
                            <strong>試用期中</strong>
                            <span class="ms-2">剩餘 {{ $subscriptionStats['remaining_days'] }} 天</span>
                            <span class="ms-2">(到期: {{ $subscriptionStats['expiry_date'] }})</span>
                        </div>
                    @elseif($subscriptionStats['subscription_status'] === 'active')
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>訂閱有效</strong>
                            <span class="ms-2">剩餘 {{ $subscriptionStats['remaining_days'] }} 天</span>
                            <span class="ms-2">(到期: {{ $subscriptionStats['expiry_date'] }})</span>
                        </div>
                    @else
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>訂閱已過期</strong>
                            <span class="ms-2 text-danger">請立即訂閱以繼續使用服務</span>
                        </div>
                    @endif
                </div>
                <div class="col-md-4 text-end">
                    @if($subscriptionStats['subscription_status'] === 'expired')
                        <a href="{{ route('subscription.index') }}" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-1"></i>
                            立即訂閱
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 訂單列表 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                訂單記錄
            </h5>
        </div>
        <div class="card-body">
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>訂單編號</th>
                                <th>月數</th>
                                <th>金額</th>
                                <th>狀態</th>
                                <th>付款時間</th>
                                <th>建立時間</th>
                                <th width="150">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $order->order_number }}</span>
                                </td>
                                <td>{{ $order->months }} 個月</td>
                                <td>NT$ {{ number_format($order->total_amount) }}</td>
                                <td>
                                    <span class="badge bg-{{ $order->getStatusColor() }}">
                                        {{ $order->getStatusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    @if($order->paid_at)
                                        {{ $order->paid_at->format('Y-m-d H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('subscription.show', $order) }}"
                                           class="btn btn-sm btn-outline-info"
                                           title="查看詳情">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($order->canRepay())
                                            <a href="{{ route('subscription.repay', $order) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="重新繳費">
                                                <i class="fas fa-redo"></i>
                                            </a>
                                        @endif
                                        @if($order->status === 'pending' && !$order->isExpired())
                                            <form method="POST"
                                                  action="{{ route('subscription.cancel', $order) }}"
                                                  onsubmit="return confirm('確定要取消這筆訂單嗎？');">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="取消訂單">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 分頁 -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        顯示 {{ $orders->firstItem() }} 到 {{ $orders->lastItem() }}
                        共 {{ $orders->total() }} 筆記錄
                    </div>
                    {{ $orders->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">目前沒有訂單記錄</h5>
                    <p class="text-muted mb-4">開始您的第一個訂閱吧！</p>
                    <a href="{{ route('subscription.index') }}" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-1"></i>
                        訂閱服務
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.btn-group .btn + .btn {
    margin-left: 2px;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>
@endpush