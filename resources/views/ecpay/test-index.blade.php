@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card"></i> ECPay 綠界金流測試
                    </h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- 測試資訊 -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> 測試環境資訊</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>商家ID:</strong><br>
                                <code>{{ config('ecpay.merchant_id') }}</code>
                            </div>
                            <div class="col-md-6">
                                <strong>測試模式:</strong><br>
                                <span class="badge {{ config('ecpay.test_mode') ? 'bg-warning' : 'bg-success' }}">
                                    {{ config('ecpay.test_mode') ? '測試環境' : '正式環境' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 測試表單 -->
                    <form method="POST" action="{{ route('ecpay.test.generate') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">付款金額 (元)</label>
                                <input type="number"
                                       class="form-control"
                                       id="amount"
                                       name="amount"
                                       value="100"
                                       min="1"
                                       max="20000"
                                       required>
                                <small class="form-text text-muted">金額範圍：1-20,000 元</small>
                            </div>
                            <div class="col-md-6">
                                <label for="item_name" class="form-label">商品名稱</label>
                                <input type="text"
                                       class="form-control"
                                       id="item_name"
                                       name="item_name"
                                       value="ECPay測試商品"
                                       maxlength="200"
                                       required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label for="description" class="form-label">交易描述</label>
                                <input type="text"
                                       class="form-control"
                                       id="description"
                                       name="description"
                                       value="592Meal ECPay 金流測試"
                                       maxlength="200"
                                       placeholder="請輸入交易描述（可選）">
                                <small class="form-text text-muted">最多 200 個字元</small>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card"></i> 產生測試付款
                            </button>
                        </div>
                    </form>

                    <!-- 測試說明 -->
                    <div class="alert alert-secondary mt-4">
                        <h5><i class="fas fa-lightbulb"></i> 測試說明</h5>
                        <ul>
                            <li>此頁面用於測試 ECPay 金流整合是否正常運作</li>
                            <li>測試環境使用綠界提供的測試帳號，不會產生實際扣款</li>
                            <li>測試信用卡號碼：<code>4311-9522-2222-2222</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection