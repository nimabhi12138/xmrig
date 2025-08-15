# XMRig 完整功能版本

这是一个功能增强版的 XMRig 矿工，包含了以下高级功能：

## 🎯 核心功能

### 1. 智能系统监控
- **CPU 使用率监控**：当 CPU 使用率超过设定阈值时自动暂停挖矿
- **进程检测**：检测到特定进程（如任务管理器）时自动暂停
- **窗口标题检测**：检测到包含特定关键词的窗口时自动暂停
- **智能恢复**：所有条件满足后延迟恢复，避免频繁切换

### 2. 远程上报功能
- **实时状态上报**：定期向服务器上报挖矿状态和系统信息
- **完整统计数据**：包括算力、份额、系统资源使用情况等
- **设备信息**：上报主机名、操作系统、CPU 型号等信息
- **暂停状态追踪**：记录暂停原因和检测到的进程/窗口

### 3. 动态捐赠控制
- **可配置捐赠比例**：支持 0-100% 的捐赠比例设置
- **时间片轮转**：按配置的比例在用户挖矿和捐赠之间切换
- **使用用户矿池**：可选择使用用户的矿池进行捐赠
- **实时统计**：追踪实际捐赠比例和份额分配

### 4. Web 配置支持
- **远程配置加载**：从 HTTP/HTTPS URL 加载配置
- **动态更新**：无需重启即可更新配置
- **完全控制**：算法、矿池、钱包等所有参数都可远程配置

## 📋 配置参数详解

### 系统监控配置
```json
{
  "cpu-high-pause": 95,        // CPU 使用率 >= 95% 时暂停
  "cpu-low-resume": 30,         // CPU 使用率 <= 30% 时可恢复
  "cpu-control-interval": 3,    // 检测间隔（秒）
  "cpu-resume-delay": 30,       // 恢复延迟（秒）
  "process-pause-names": "...", // 暂停进程列表（逗号分隔）
  "window-pause-names": "..."   // 暂停窗口关键词（逗号分隔）
}
```

### 上报配置
```json
{
  "report-host": "your-server.com",  // 上报服务器地址
  "report-port": 8181,               // 上报端口
  "report-path": "/api/report",      // API 路径
  "report-token": "auth-token"       // 认证令牌
}
```

### 捐赠配置
```json
{
  "donate-level": 90,                // 捐赠比例（%）
  "donate-address": "wallet",        // 捐赠钱包地址
  "donate-use-user-pool": true       // 使用用户矿池
}
```

## 🚀 编译指南

### 依赖项
```bash
# Ubuntu/Debian
sudo apt-get install -y \
    build-essential cmake libuv1-dev libssl-dev libhwloc-dev \
    libcurl4-openssl-dev

# Windows (需要 Visual Studio 2019+)
# 安装 vcpkg 并安装依赖：
vcpkg install curl openssl libuv hwloc
```

### 编译步骤
```bash
git clone https://github.com/your-repo/xmrig-enhanced.git
cd xmrig-enhanced
mkdir build && cd build
cmake ..
make -j$(nproc)
```

### Windows 编译
```batch
mkdir build
cd build
cmake .. -G "Visual Studio 16 2019" -A x64
cmake --build . --config Release
```

## 📊 上报数据格式

服务器将收到以下格式的 JSON 数据：

```json
{
  "timestamp": 1234567890,
  "version": "6.x.x",
  "token": "auth-token",
  "device": {
    "hostname": "worker-01",
    "os": "Windows 10",
    "cpu_model": "Intel Core i7-9700K",
    "cpu_cores": 8
  },
  "mining": {
    "hashrate": 5000.0,
    "total_hashes": 1000000,
    "accepted_shares": 100,
    "rejected_shares": 2,
    "algorithm": "rx/0"
  },
  "pool": {
    "url": "pool.example.com:3333",
    "wallet": "wallet_address"
  },
  "system": {
    "cpu_usage": 45.5,
    "memory_usage": 2048000000,
    "is_paused": false,
    "pause_reason": "",
    "detected_process": "",
    "detected_window": ""
  }
}
```

## 🔧 运行示例

### 基本运行
```bash
./xmrig -c config.json
```

### 使用 Web 配置
```bash
./xmrig --web-config-url https://your-server.com/config.json
```

### 后台运行（Linux）
```bash
nohup ./xmrig -c config.json > /dev/null 2>&1 &
```

### Windows 服务模式
```batch
sc create XMRigService binPath= "C:\xmrig\xmrig.exe -c C:\xmrig\config.json"
sc start XMRigService
```

## 🛡️ 安全建议

1. **使用 HTTPS**：配置 URL 和上报服务器应使用 HTTPS
2. **认证令牌**：设置强认证令牌保护上报接口
3. **防火墙规则**：限制上报服务器的访问 IP
4. **日志审计**：定期检查日志文件中的异常活动
5. **配置加密**：敏感配置信息应加密存储

## 📝 日志输出示例

```
[2024-01-01 12:00:00] miner starting monitoring: cpu_high=95, cpu_low=30
[2024-01-01 12:00:03] CPU usage: 25.3%
[2024-01-01 12:00:06] miner detected process: taskmgr.exe
[2024-01-01 12:00:06] miner paused due to detected process (CPU=25.3%)
[2024-01-01 12:01:00] miner resume countdown: 27s (9 cycles)
[2024-01-01 12:01:30] miner resumed - conditions cleared
[2024-01-01 12:02:00] Reporter: sent status to server
[2024-01-01 12:03:00] Switching to donation mining (90% configured)
[2024-01-01 12:04:30] Switching to user mining (donated 90.1% this cycle)
```

## 🔄 工作流程

1. **启动阶段**
   - 加载本地配置
   - 获取 Web 配置（如果配置了 URL）
   - 初始化各个模块

2. **运行阶段**
   - 系统监控持续检测 CPU、进程、窗口
   - 根据条件自动暂停/恢复挖矿
   - 按配置的比例进行捐赠切换
   - 定期上报状态到服务器

3. **停止阶段**
   - 保存当前状态
   - 清理资源
   - 发送最后的状态报告

## ⚠️ 注意事项

1. **性能影响**：监控功能会占用少量系统资源
2. **网络要求**：上报功能需要稳定的网络连接
3. **权限要求**：某些功能可能需要管理员权限
4. **兼容性**：支持 Windows 7+、Linux、macOS

## 📞 技术支持

如有问题，请查看日志文件或联系技术支持。

## 📄 许可证

本软件基于 GNU GPL v3.0 许可证发布。