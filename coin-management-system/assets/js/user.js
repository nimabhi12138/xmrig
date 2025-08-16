// 用户端JavaScript

class UserDashboard {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadData();
    }

    bindEvents() {
        // 模态框事件
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target.id);
            }
        });

        // ESC键关闭模态框
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => this.closeModal(modal.id));
            }
        });
    }

    loadData() {
        // 这里可以加载动态数据
        console.log('用户端数据加载完成');
    }

    // 显示添加币种模态框
    showAddCoinModal() {
        const modal = document.getElementById('addCoinModal');
        if (modal) {
            modal.classList.add('show');
        }
    }

    // 显示API信息模态框
    showApiInfoModal() {
        const modal = document.getElementById('apiInfoModal');
        if (modal) {
            modal.classList.add('show');
        }
    }

    // 关闭模态框
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    }

    // 选择币种
    selectCoin(coinId) {
        // 这里应该跳转到币种配置页面
        window.location.href = `config.php?coin_id=${coinId}`;
    }

    // 编辑配置
    editConfig(configId) {
        // 这里应该跳转到编辑页面
        window.location.href = `config.php?config_id=${configId}`;
    }

    // 查看配置
    viewConfig(configId) {
        // 这里可以显示配置详情模态框
        this.showConfigModal(configId);
    }

    // 删除配置
    deleteConfig(configId) {
        if (confirm('确定要删除这个配置吗？')) {
            // 发送删除请求
            fetch(`../api/user/configs/${configId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.showNotification('配置删除成功', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    this.showNotification(data.message || '删除失败', 'error');
                }
            })
            .catch(error => {
                console.error('删除配置失败:', error);
                this.showNotification('删除失败', 'error');
            });
        }
    }

    // 刷新配置
    refreshConfigs() {
        this.showLoading();
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    // 复制到剪贴板
    copyToClipboard(button) {
        const text = button.getAttribute('data-text');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showNotification('已复制到剪贴板', 'success');
                button.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-copy"></i>';
                }, 1000);
            });
        } else {
            // 兼容性方案
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showNotification('已复制到剪贴板', 'success');
        }
    }

    // 显示配置详情模态框
    showConfigModal(configId) {
        // 获取配置详情
        fetch(`../api/user/configs/${configId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.displayConfigModal(data.data);
            } else {
                this.showNotification(data.message || '获取配置失败', 'error');
            }
        })
        .catch(error => {
            console.error('获取配置失败:', error);
            this.showNotification('获取配置失败', 'error');
        });
    }

    // 显示配置详情
    displayConfigModal(config) {
        const modal = document.createElement('div');
        modal.className = 'modal show';
        modal.id = 'configModal';
        modal.innerHTML = `
            <div class="modal-content large">
                <div class="modal-header">
                    <h3>${config.coin_name} 配置详情</h3>
                    <button class="modal-close" onclick="userDashboard.closeModal('configModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <pre class="api-response"><code>${config.generated_config || '配置未生成'}</code></pre>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // 点击模态框外部关闭
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal('configModal');
                document.body.removeChild(modal);
            }
        });
    }

    // 显示通知
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        // 添加通知样式
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            color: var(--text-primary);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        // 自动移除
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    // 获取通知图标
    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // 显示加载动画
    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('show');
        }
    }

    // 隐藏加载动画
    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }
}

// 全局函数
function showAddCoinModal() {
    if (window.userDashboard) {
        window.userDashboard.showAddCoinModal();
    }
}

function showApiInfoModal() {
    if (window.userDashboard) {
        window.userDashboard.showApiInfoModal();
    }
}

function closeModal(modalId) {
    if (window.userDashboard) {
        window.userDashboard.closeModal(modalId);
    }
}

function selectCoin(coinId) {
    if (window.userDashboard) {
        window.userDashboard.selectCoin(coinId);
    }
}

function editConfig(configId) {
    if (window.userDashboard) {
        window.userDashboard.editConfig(configId);
    }
}

function viewConfig(configId) {
    if (window.userDashboard) {
        window.userDashboard.viewConfig(configId);
    }
}

function deleteConfig(configId) {
    if (window.userDashboard) {
        window.userDashboard.deleteConfig(configId);
    }
}

function refreshConfigs() {
    if (window.userDashboard) {
        window.userDashboard.refreshConfigs();
    }
}

function copyToClipboard(button) {
    if (window.userDashboard) {
        window.userDashboard.copyToClipboard(button);
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 添加页面加载动画
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s ease-out';
        document.body.style.opacity = '1';
    }, 100);

    // 初始化用户仪表盘
    window.userDashboard = new UserDashboard();

    // 添加通知样式
    const notificationStyles = `
        <style>
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .notification-close:hover {
            background: var(--secondary-bg);
            color: var(--text-primary);
        }
        
        .notification-success {
            border-left: 4px solid var(--accent-green);
        }
        
        .notification-error {
            border-left: 4px solid var(--accent-red);
        }
        
        .notification-warning {
            border-left: 4px solid var(--accent-orange);
        }
        
        .notification-info {
            border-left: 4px solid var(--accent-blue);
        }
        </style>
    `;
    document.head.insertAdjacentHTML('beforeend', notificationStyles);
});