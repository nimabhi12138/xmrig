# XMRig Windows 版本 - 远程配置挖矿程序

## 🚀 快速开始

### 1. 编译程序
```cmd
# 方法一：使用批处理脚本（推荐）
build_windows.bat

# 方法二：使用PowerShell脚本
powershell -ExecutionPolicy Bypass -File build_windows.ps1
```

### 2. 运行程序
```cmd
# 方法一：使用启动脚本
start_mining.bat https://your-server.com/config.json

# 方法二：直接运行
xmrig.exe --remote-config=https://your-server.com/config.json
```

## 📋 系统要求

- **操作系统**: Windows 10 或更新版本
- **内存**: 至少 4GB RAM
- **磁盘空间**: 至少 2GB 可用空间
- **网络**: 稳定的互联网连接

### 必需软件
1. **Visual Studio 2019** (C++ 桌面开发)
2. **CMake 3.10+**
3. **Git**

## 🔧 编译说明

### 编译优化设置
- ✅ 启用所有挖矿算法 (RandomX, Argon2, KawPow, GhostRider)
- ✅ 启用HTTP/TLS支持 (远程配置必需)
- ✅ 启用CPU优化 (SSE4.1, AVX2, VAES)
- ❌ 禁用GPU支持 (OpenCL, CUDA) - 减少依赖
- ❌ 禁用调试功能 - 提高性能

### 编译时间
- **首次编译**: 10-30分钟
- **后续编译**: 5-15分钟

## 📁 文件说明

### 编译脚本
- `build_windows.bat` - 批处理编译脚本
- `build_windows.ps1` - PowerShell编译脚本

### 启动脚本
- `start_mining.bat` - 快速启动脚本

### 配置文件
- `windows_config.json` - Windows专用配置示例
- `remote_config_example.json` - 完整配置示例

### 文档
- `WINDOWS_BUILD_GUIDE.md` - 详细编译指南
- `REMOTE_CONFIG_README.md` - 远程配置说明

## 🎯 使用方法

### 基本使用
```cmd
xmrig.exe --remote-config=https://your-server.com/config.json
```

### 测试配置
```cmd
xmrig.exe --remote-config=https://your-server.com/config.json --dry-run
```

### 查看帮助
```cmd
xmrig.exe --help
```

### 调试模式
```cmd
xmrig.exe --remote-config=https://your-server.com/config.json --verbose=1
```

## ⚙️ 配置说明

### 远程配置文件格式
```json
{
    "pools": [
        {
            "algo": "rx/0",                    // 挖矿算法
            "coin": "XMR",                     // 币种
            "url": "pool.example.com:3333",    // 矿池地址
            "user": "YOUR_WALLET_ADDRESS",     // 用户钱包地址
            "pass": "x",                       // 密码
            "enabled": true                    // 启用状态
        }
    ],
    "donate-level": 0,                        // 关闭捐赠
    "donate-over-proxy": 0                    // 关闭代理捐赠
}
```

### 重要配置项
- **algo**: 挖矿算法 (rx/0, argon2/chukwav2, kawpow等)
- **coin**: 币种 (XMR, RVN, KAS等)
- **url**: 矿池地址和端口
- **user**: 钱包地址
- **donate-level**: 捐赠比例 (0表示关闭)

## 🔒 安全注意事项

### 1. 杀毒软件
- 某些杀毒软件可能误报
- 建议添加程序到白名单
- 或临时关闭实时保护

### 2. 防火墙
- 确保允许程序访问网络
- 检查Windows防火墙设置
- 可能需要添加例外规则

### 3. 配置文件安全
- 使用HTTPS协议获取配置
- 不要在配置中硬编码敏感信息
- 定期更新配置URL

## 🚨 常见问题

### 编译问题

**Q: 找不到 cl.exe**
```
A: 确保安装了Visual Studio 2019，并在"Developer Command Prompt"中运行
```

**Q: CMake配置失败**
```
A: 检查是否安装了CMake 3.10+，或通过Visual Studio Installer安装
```

**Q: 编译时间过长**
```
A: 这是正常现象，首次编译需要下载依赖库
```

### 运行问题

**Q: 找不到 MSVCP140.dll**
```
A: 安装Visual C++ Redistributable
下载: https://aka.ms/vs/16/release/vc_redist.x64.exe
```

**Q: 网络连接失败**
```
A: 检查网络连接、防火墙设置、配置URL是否正确
```

**Q: 挖矿速度慢**
```
A: 检查CPU配置、算法设置、矿池连接质量
```

### 性能问题

**Q: 如何提高挖矿效率？**
```
A: 
1. 以管理员身份运行
2. 关闭不必要的后台程序
3. 确保有足够的系统资源
4. 选择延迟低的矿池
```

## 📊 性能优化

### 系统优化
1. **以管理员身份运行** - 提高CPU优先级
2. **关闭后台程序** - 释放CPU资源
3. **调整电源计划** - 选择"高性能"模式
4. **更新驱动程序** - 确保系统最新

### 网络优化
1. **选择就近矿池** - 减少网络延迟
2. **使用有线连接** - 提高网络稳定性
3. **配置DNS** - 使用可靠的DNS服务器

### 程序优化
1. **使用Release版本** - 性能最佳
2. **调整线程数** - 根据CPU核心数优化
3. **启用大页面内存** - 提高内存访问效率

## 🔍 故障排除

### 日志分析
程序会输出详细的日志信息：
```
[INFO] Fetching configuration from: https://your-server.com/config.json
[INFO] Configuration fetched successfully
[INFO] Applying remote configuration
[INFO] Remote configuration applied successfully
```

### 调试步骤
1. **检查网络连接**
2. **验证配置URL**
3. **查看错误日志**
4. **测试配置文件格式**
5. **检查系统资源**

### 获取帮助
1. 查看程序帮助: `xmrig.exe --help`
2. 检查日志输出
3. 参考配置示例文件
4. 查看详细文档

## 📈 监控和维护

### 性能监控
- 监控CPU使用率
- 检查内存使用情况
- 观察网络连接状态
- 跟踪挖矿收益

### 定期维护
- 更新程序版本
- 检查配置文件
- 清理日志文件
- 备份重要数据

## 🎉 成功运行

当您看到类似以下输出时，说明程序运行成功：

```
[INFO] XMRig 6.18.0 started
[INFO] Loading remote configuration from: https://your-server.com/config.json
[INFO] Configuration fetched successfully
[INFO] Remote configuration applied successfully
[INFO] Starting miner...
[INFO] Connected to pool.example.com:3333
[INFO] Mining started
```

恭喜！您的XMRig远程配置挖矿程序已经成功运行！🎊