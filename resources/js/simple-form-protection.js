/**
 * 簡化版防重複提交工具
 * 專門針對訂單表單優化
 */
class SimpleFormProtection {
    constructor() {
        this.isSubmitting = false;
        this.init();
    }

    init() {
        // 專門保護訂單表單
        const orderForm = document.querySelector('form[action*="checkout"]');
        if (orderForm) {
            this.protectOrderForm(orderForm);
        }

        // 保護所有現有的購物車按鈕
        this.protectAllCartButtons();

        // 監聽 DOM 變化，處理動態創建的按鈕
        this.observeCartButtons();
    }

    // 保護所有購物車按鈕
    protectAllCartButtons() {
        const cartButtons = document.querySelectorAll('button[onclick*="addToCart"]');
        cartButtons.forEach(button => {
            if (!button.hasAttribute('data-protected')) {
                // 檢查按鈕是否已經被自己的 addToCart 函數保護
                if (!button.disabled) {
                    this.protectCartButton(button);
                    button.setAttribute('data-protected', 'true');
                }
            }
        });
    }

    // 監聽 DOM 變化
    observeCartButtons() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // 檢查新添加的節點中是否有購物車按鈕
                            const cartButtons = node.querySelectorAll ?
                                node.querySelectorAll('button[onclick*="addToCart"]:not([data-protected])') : [];
                            cartButtons.forEach(button => {
                                this.protectCartButton(button);
                                button.setAttribute('data-protected', 'true');
                            });
                        }
                    });
                }
            });
        });

        // 只觀察特定的容器，避免過度觀察
        const cartContainers = document.querySelectorAll('.cart-sidebar, #cart-sidebar, .cart-container');
        const observeTargets = cartContainers.length > 0 ? cartContainers : [document.body];

        observeTargets.forEach(target => {
            observer.observe(target, {
                childList: true,
                subtree: true
            });
        });
    }

    protectOrderForm(form) {
        form.addEventListener('submit', (e) => {
            if (this.isSubmitting) {
                e.preventDefault();
                console.log('Order form submission blocked - already submitting');
                return false;
            }

            this.isSubmitting = true;
            const submitButton = form.querySelector('button[type="submit"]');

            if (submitButton) {
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>處理中...';

                // 10秒後恢復（防止永久鎖定）
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                    this.isSubmitting = false;
                    console.log('Order form lock released');
                }, 10000);
            }

            console.log('Order form submitted successfully');
        });
    }

    protectCartButton(button) {
        // 簡化的保護，只防止重複點擊，不干擾原有的 addToCart 函數
        button.addEventListener('click', (e) => {
            // 檢查按鈕是否已經被禁用（說明正在處理中）
            if (button.disabled) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Cart button click blocked - button is disabled');
                return false;
            }

            // 檢查全域提交狀態
            if (this.isSubmitting) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Cart button click blocked - global submitting state');
                return false;
            }

            // 設置全域提交狀態
            this.isSubmitting = true;

            // 1秒後自動重置全域狀態（防止永久鎖定）
            setTimeout(() => {
                this.isSubmitting = false;
                console.log('Global cart lock released');
            }, 1000);
        }, true); // 使用捕獲階段確保在其他處理器之前執行
    }

    // 手動重置方法
    reset() {
        this.isSubmitting = false;
        console.log('Form protection reset');
    }
}

// 創建全域實例
window.simpleFormProtection = new SimpleFormProtection();

// 頁面載入完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('Simple form protection initialized');
});