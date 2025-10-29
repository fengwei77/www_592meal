/**
 * 防重複提交工具
 * 用於防止表單和按鈕的重複點擊
 */
class FormSubmitProtection {
    constructor() {
        this.submittingForms = new Set();
        this.clickingButtons = new Set();
        this.init();
    }

    /**
     * 初始化防護機制
     */
    init() {
        // 保護所有表單
        this.protectAllForms();

        // 保護所有按鈕
        this.protectAllButtons();

        // 監聽 DOM 變化
        this.observeDOMChanges();
    }

    /**
     * 保護所有表單
     */
    protectAllForms() {
        const forms = document.querySelectorAll('form[data-protect="true"]');
        forms.forEach(form => this.protectForm(form));
    }

    /**
     * 保護單個表單
     */
    protectForm(form) {
        if (this.submittingForms.has(form)) return;

        form.addEventListener('submit', (e) => {
            const formId = this.getFormId(form);

            if (this.submittingForms.has(formId)) {
                e.preventDefault();
                console.log('Form submission blocked - already submitting:', formId);
                return false;
            }

            this.submittingForms.add(formId);
            this.setFormLoading(form, true);

            // 3秒後自動解除鎖定（防止長時間等待）
            setTimeout(() => {
                this.submittingForms.delete(formId);
                this.setFormLoading(form, false);
                console.log('Form lock released automatically:', formId);
            }, 3000);
        });

        // 監聽頁面即將卸載事件，清除鎖定
        window.addEventListener('beforeunload', () => {
            const formId = this.getFormId(form);
            this.submittingForms.delete(formId);
            this.setFormLoading(form, false);
        });
    }

    /**
     * 保護所有按鈕
     */
    protectAllButtons() {
        // 保護有 data-protect 屬性的按鈕
        const protectedButtons = document.querySelectorAll('button[data-protect="true"]');
        protectedButtons.forEach(button => this.protectButton(button));

        // 保護有 onclick 的按鈕（購物車等）
        const onClickButtons = document.querySelectorAll('button[onclick]');
        onClickButtons.forEach(button => this.protectButton(button));
    }

    /**
     * 保護單個按鈕
     */
    protectButton(button) {
        const buttonId = this.getButtonId(button);

        if (this.clickingButtons.has(buttonId)) return;

        // 替換原有的 onclick
        const originalOnClick = button.onclick;
        button.onclick = null;

        button.addEventListener('click', (e) => {
            if (this.clickingButtons.has(buttonId)) {
                e.preventDefault();
                console.log('Button click blocked - already clicking:', buttonId);
                return false;
            }

            this.clickingButtons.add(buttonId);
            this.setButtonLoading(button, true);

            // 執行原有的 onclick
            if (originalOnClick) {
                try {
                    const result = originalOnClick.call(button, e);

                    // 如果原有的 onclick 返回 false，立即解除鎖定
                    if (result === false) {
                        this.clickingButtons.delete(buttonId);
                        this.setButtonLoading(button, false);
                        return false;
                    }
                } catch (error) {
                    console.error('Error in original onclick:', error);
                    this.clickingButtons.delete(buttonId);
                    this.setButtonLoading(button, false);
                    return false;
                }
            }

            // 2秒後自動解除鎖定
            setTimeout(() => {
                this.clickingButtons.delete(buttonId);
                this.setButtonLoading(button, false);
            }, 2000);
        });
    }

    /**
     * 設置表單載入狀態
     */
    setFormLoading(form, isLoading) {
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitButton) return;

        if (isLoading) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');

            // 保存原始內容
            submitButton.dataset.originalText = submitButton.innerHTML;

            // 設置載入文字
            if (submitButton.tagName.toLowerCase() === 'input') {
                submitButton.value = '處理中...';
            } else {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>處理中...';
            }
        } else {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-75', 'cursor-not-allowed');

            // 恢復原始內容
            if (submitButton.dataset.originalText) {
                if (submitButton.tagName.toLowerCase() === 'input') {
                    submitButton.value = submitButton.dataset.originalText;
                } else {
                    submitButton.innerHTML = submitButton.dataset.originalText;
                }
                delete submitButton.dataset.originalText;
            }
        }
    }

    /**
     * 設置按鈕載入狀態
     */
    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('opacity-75', 'cursor-not-allowed');

            // 保存原始內容
            button.dataset.originalText = button.innerHTML;

            // 設置載入文字
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>處理中...';
        } else {
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-not-allowed');

            // 恢復原始內容
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
                delete button.dataset.originalText;
            }
        }
    }

    /**
     * 獲取表單ID
     */
    getFormId(form) {
        if (form.id) return form.id;
        if (form.dataset.formId) return form.dataset.formId;

        // 生成唯一ID
        const formId = 'form_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        form.dataset.formId = formId;
        return formId;
    }

    /**
     * 獲取按鈕ID
     */
    getButtonId(button) {
        if (button.id) return button.id;
        if (button.dataset.buttonId) return button.dataset.buttonId;

        // 生成唯一ID
        const buttonId = 'button_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        button.dataset.buttonId = buttonId;
        return buttonId;
    }

    /**
     * 監聽 DOM 變化
     */
    observeDOMChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // 檢查新增的表單
                            const forms = node.querySelectorAll ?
                                node.querySelectorAll('form[data-protect="true"]') : [];
                            forms.forEach(form => this.protectForm(form));

                            // 檢查新增的按鈕
                            const buttons = node.querySelectorAll ?
                                node.querySelectorAll('button[data-protect="true"], button[onclick]') : [];
                            buttons.forEach(button => this.protectButton(button));
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * 手動解除表單鎖定
     */
    unlockForm(form) {
        const formId = this.getFormId(form);
        this.submittingForms.delete(formId);
        this.setFormLoading(form, false);
    }

    /**
     * 手動解除按鈕鎖定
     */
    unlockButton(button) {
        const buttonId = this.getButtonId(button);
        this.clickingButtons.delete(buttonId);
        this.setButtonLoading(button, false);
    }

    /**
     * 重置所有鎖定
     */
    resetAll() {
        console.log('Resetting all form locks...');
        this.submittingForms.clear();
        this.clickingButtons.clear();

        // 恢復所有表單狀態
        document.querySelectorAll('form').forEach(form => {
            this.setFormLoading(form, false);
            delete form.dataset.submitting;
        });

        // 恢復所有按鈕狀態
        document.querySelectorAll('button').forEach(button => {
            this.setButtonLoading(button, false);
        });

        console.log('All form locks have been reset');
    }

    // 檢查是否有被鎖定的表單
    checkLockedForms() {
        const lockedForms = [];
        this.submittingForms.forEach((value, formId) => {
            lockedForms.push(formId);
        });

        if (lockedForms.length > 0) {
            console.warn('Currently locked forms:', lockedForms);
            return lockedForms;
        }

        return [];
    }
}

// 全域實例
window.formSubmitProtection = new FormSubmitProtection();

// 頁面載入完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    window.formSubmitProtection.init();
});

// 防止 F5 重新提交
window.addEventListener('beforeunload', function(e) {
    if (window.formSubmitProtection.submittingForms.size > 0) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

// 顯示確認訊息的函數
window.showConfirmMessage = function(message, callback) {
    if (confirm(message)) {
        if (callback) callback();
        return true;
    }
    return false;
};

// 導出給其他腳本使用
window.FormSubmitProtection = FormSubmitProtection;