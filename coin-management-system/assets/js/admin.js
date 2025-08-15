// 管理后台JavaScript

class AdminDashboard {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.loadDashboardData();
        this.initCharts();
        this.bindEvents();
        this.startAutoRefresh();
    }

    // 绑定事件
    bindEvents() {
        // 刷新按钮
        const refreshBtn = document.querySelector('.btn-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshData());
        }

        // 图表时间选择
        const chartPeriod = document.getElementById('chartPeriod');
        if (chartPeriod) {
            chartPeriod.addEventListener('change', () => this.updateCharts());
        }

        // 侧边栏菜单
        this.initSidebarNavigation();
    }

    // 侧边栏导航
    initSidebarNavigation() {
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                menuItems.forEach(mi => mi.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    // 加载仪表盘数据
    async loadDashboardData() {
        this.showLoading();
        
        try {
            const [stats, activities] = await Promise.all([
                this.fetchStats(),
                this.fetchRecentActivities()
            ]);

            this.updateStats(stats);
            this.renderActivities(activities);
        } catch (error) {
            console.error('加载数据失败:', error);
            this.showError('数据加载失败');
        } finally {
            this.hideLoading();
        }
    }

    // 获取统计数据
    async fetchStats() {
        // 模拟API调用
        return new Promise(resolve => {
            setTimeout(() => {
                resolve({
                    totalCoins: Math.floor(Math.random() * 50) + 10,
                    totalUsers: Math.floor(Math.random() * 1000) + 100,
                    totalConfigs: Math.floor(Math.random() * 500) + 50,
                    todayApiCalls: Math.floor(Math.random() * 10000) + 1000
                });
            }, 500);
        });
    }

    // 获取最近活动
    async fetchRecentActivities() {
        // 模拟API调用
        return new Promise(resolve => {
            setTimeout(() => {
                const activities = [
                    {
                        type: 'user_register',
                        title: '新用户注册',
                        description: '用户 "crypto_trader" 注册了账户',
                        time: '2分钟前',
                        icon: 'fas fa-user-plus'
                    },
                    {
                        type: 'config_update',
                        title: '配置更新',
                        description: '用户更新了 Bitcoin 配置',
                        time: '5分钟前',
                        icon: 'fas fa-cog'
                    },
                    {
                        type: 'api_call',
                        title: 'API调用',
                        description: '外部程序请求了配置数据',
                        time: '8分钟前',
                        icon: 'fas fa-exchange-alt'
                    },
                    {
                        type: 'coin_add',
                        title: '新币种添加',
                        description: '管理员添加了 Ethereum 币种',
                        time: '15分钟前',
                        icon: 'fas fa-plus-circle'
                    }
                ];
                resolve(activities);
            }, 300);
        });
    }

    // 更新统计数据
    updateStats(stats) {
        const elements = {
            totalCoins: document.getElementById('totalCoins'),
            totalUsers: document.getElementById('totalUsers'),
            totalConfigs: document.getElementById('totalConfigs'),
            todayApiCalls: document.getElementById('todayApiCalls')
        };

        Object.keys(stats).forEach(key => {
            if (elements[key]) {
                this.animateNumber(elements[key], stats[key]);
            }
        });
    }

    // 数字动画
    animateNumber(element, targetValue) {
        const duration = 1000;
        const startValue = 0;
        const startTime = performance.now();

        const updateNumber = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // 使用缓动函数
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.floor(startValue + (targetValue - startValue) * easeOutQuart);
            
            element.textContent = this.formatNumber(currentValue);
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        };

        requestAnimationFrame(updateNumber);
    }

    // 格式化数字
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    // 渲染活动列表
    renderActivities(activities) {
        const container = document.getElementById('recentActivity');
        if (!container) return;

        const html = activities.map(activity => `
            <div class="activity-item fade-in">
                <div class="activity-icon">
                    <i class="${activity.icon}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">${activity.title}</div>
                    <div class="activity-description">${activity.description}</div>
                    <div class="activity-time">${activity.time}</div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    // 初始化图表
    initCharts() {
        this.initUserChart();
        this.initApiChart();
    }

    // 用户注册趋势图
    initUserChart() {
        const ctx = document.getElementById('userChart');
        if (!ctx) return;

        // 生成模拟数据
        const data = this.generateUserChartData();

        this.charts.userChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: '新注册用户',
                    data: data.values,
                    borderColor: 'rgb(0, 212, 255)',
                    backgroundColor: 'rgba(0, 212, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(0, 212, 255)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#94a3b8'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#64748b'
                        },
                        grid: {
                            color: '#334155'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#64748b'
                        },
                        grid: {
                            color: '#334155'
                        }
                    }
                }
            }
        });
    }

    // API调用统计图
    initApiChart() {
        const ctx = document.getElementById('apiChart');
        if (!ctx) return;

        const data = this.generateApiChartData();

        this.charts.apiChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['成功', '失败', '超时'],
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        'rgb(16, 185, 129)',
                        'rgb(239, 68, 68)',
                        'rgb(245, 158, 11)'
                    ],
                    borderColor: '#252b4a',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            padding: 20
                        }
                    }
                }
            }
        });
    }

    // 生成用户图表数据
    generateUserChartData() {
        const days = 7;
        const labels = [];
        const values = [];

        for (let i = days - 1; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('zh-CN', { month: 'short', day: 'numeric' }));
            values.push(Math.floor(Math.random() * 20) + 5);
        }

        return { labels, values };
    }

    // 生成API图表数据
    generateApiChartData() {
        return {
            values: [
                Math.floor(Math.random() * 800) + 200, // 成功
                Math.floor(Math.random() * 50) + 10,   // 失败
                Math.floor(Math.random() * 30) + 5     // 超时
            ]
        };
    }

    // 更新图表
    updateCharts() {
        const period = document.getElementById('chartPeriod')?.value || '7';
        
        // 更新用户图表
        if (this.charts.userChart) {
            const data = this.generateUserChartData();
            this.charts.userChart.data.labels = data.labels;
            this.charts.userChart.data.datasets[0].data = data.values;
            this.charts.userChart.update('active');
        }

        // 更新API图表
        if (this.charts.apiChart) {
            const data = this.generateApiChartData();
            this.charts.apiChart.data.datasets[0].data = data.values;
            this.charts.apiChart.update('active');
        }
    }

    // 刷新数据
    async refreshData() {
        const refreshBtn = document.querySelector('.btn-refresh');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> 刷新中...';
            refreshBtn.disabled = true;
        }

        try {
            await this.loadDashboardData();
            this.updateCharts();
            this.showNotification('数据刷新成功', 'success');
        } catch (error) {
            this.showNotification('数据刷新失败', 'error');
        } finally {
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 刷新数据';
                refreshBtn.disabled = false;
            }
        }
    }

    // 自动刷新
    startAutoRefresh() {
        setInterval(() => {
            this.loadDashboardData();
        }, 60000); // 每分钟刷新一次
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

    // 显示错误信息
    showError(message) {
        this.showNotification(message, 'error');
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
}

// 工具函数
window.refreshData = function() {
    if (window.adminDashboard) {
        window.adminDashboard.refreshData();
    }
};

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 添加页面加载动画
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s ease-out';
        document.body.style.opacity = '1';
    }, 100);

    // 初始化仪表盘
    window.adminDashboard = new AdminDashboard();

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

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + R 刷新数据
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        if (window.adminDashboard) {
            window.adminDashboard.refreshData();
        }
    }
});

// 响应式侧边栏
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }
}

// 添加响应式处理
window.addEventListener('resize', function() {
    if (window.innerWidth <= 1024) {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && !sidebar.classList.contains('mobile-hidden')) {
            sidebar.classList.add('mobile-hidden');
        }
    }
});