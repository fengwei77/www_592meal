@extends('frontend.layouts.app')

@section('title', '防重複提交測試')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">防重複提交功能測試</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- 測試表單 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">測試表單</h2>

            <form id="test-form" data-protect="true" class="space-y-4">
                @csrf
                <input type="hidden" name="timestamp" value="{{ time() }}">

                <div>
                    <label for="test_input" class="block text-sm font-medium text-gray-700 mb-2">
                        測試輸入
                    </label>
                    <input type="text"
                           id="test_input"
                           name="test_input"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                           placeholder="輸入任何文字進行測試">
                </div>

                <div>
                    <label for="test_select" class="block text-sm font-medium text-gray-700 mb-2">
                        測試選擇
                    </label>
                    <select id="test_select"
                            name="test_select"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="option1">選項 1</option>
                        <option value="option2">選項 2</option>
                        <option value="option3">選項 3</option>
                    </select>
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>提交測試
                </button>
            </form>
        </div>

        <!-- 測試按鈕 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">測試按鈕</h2>

            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium mb-2">購物車測試按鈕</h3>
                    <button onclick="testAddToCart()"
                            data-protect="true"
                            class="w-full bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors mb-2">
                        <i class="fas fa-cart-plus mr-2"></i>測試加入購物車
                    </button>

                    <button onclick="testRemoveFromCart()"
                            data-protect="true"
                            class="w-full bg-red-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>測試移除購物車
                    </button>
                </div>

                <div>
                    <h3 class="text-lg font-medium mb-2">一般測試按鈕</h3>
                    <button onclick="testAlert()"
                            data-protect="true"
                            class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-purple-700 transition-colors">
                        <i class="fas fa-bell mr-2"></i>測試警報按鈕
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 測試說明 -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h2 class="text-xl font-semibold text-blue-800 mb-4">測試說明</h2>
        <ul class="list-disc list-inside space-y-2 text-blue-700">
            <li><strong>表單測試：</strong>快速雙擊「提交測試」按鈕，應該只會提交一次</li>
            <li><strong>按鈕測試：</strong>快速點擊任何測試按鈕，按鈕會被鎖定並顯示載入狀態</li>
            <li><strong>後端防護：</strong>即使繞過前端防護，後端中間件也會阻止重複提交</li>
            <li><strong>自動恢復：</strong>按鈕會在 3-5 秒後自動恢復可用狀態</li>
            <li><strong>日誌記錄：</strong>重複提交嘗試會被記錄在系統日誌中</li>
        </ul>
    </div>

    <!-- 測試結果顯示區 -->
    <div id="test-results" class="mt-8 hidden">
        <h2 class="text-xl font-semibold mb-4">測試結果</h2>
        <div id="results-content" class="bg-gray-50 rounded-lg p-4"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 測試函數
function testAddToCart() {
    console.log('測試加入購物車功能');
    showResult('success', '成功加入購物車（模擬）');
    return true;
}

function testRemoveFromCart() {
    console.log('測試移除購物車功能');
    if (confirm('確定要移除這個商品嗎？')) {
        showResult('success', '成功移除購物車商品（模擬）');
    }
    return true;
}

function testAlert() {
    console.log('測試警報功能');
    alert('這是一個測試警報！');
    showResult('info', '警報已顯示');
    return true;
}

function showResult(type, message) {
    const resultsDiv = document.getElementById('test-results');
    const contentDiv = document.getElementById('results-content');

    resultsDiv.classList.remove('hidden');

    const timestamp = new Date().toLocaleTimeString();
    const bgColor = type === 'success' ? 'bg-green-100 text-green-800' :
                    type === 'error' ? 'bg-red-100 text-red-800' :
                    'bg-blue-100 text-blue-800';

    const resultHtml = `
        <div class="${bgColor} rounded p-3 mb-2">
            <span class="font-medium">[${timestamp}]</span> ${message}
        </div>
    `;

    contentDiv.insertAdjacentHTML('afterbegin', resultHtml);

    // 只保留最近 5 條結果
    const results = contentDiv.children;
    if (results.length > 5) {
        contentDiv.removeChild(results[results.length - 1]);
    }
}

// 頁面載入完成後的初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('防重複提交測試頁面已載入');
    console.log('FormSubmitProtection 實例:', window.formSubmitProtection);

    // 設置測試表單的提交處理
    const testForm = document.getElementById('test-form');
    if (testForm) {
        testForm.addEventListener('submit', function(e) {
            e.preventDefault(); // 阻止默認提交，使用 AJAX

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            // 顯示處理中狀態
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>處理中...';

            fetch('/test/duplicate-submit', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('success', `表單提交成功！時間戳: ${data.timestamp}`);
                } else {
                    showResult('error', data.message || '提交失敗');
                }
            })
            .catch(error => {
                console.error('提交錯誤:', error);
                showResult('error', '網路錯誤或請求失敗');
            })
            .finally(() => {
                // 恢復按鈕狀態
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }
});
</script>
@endsection