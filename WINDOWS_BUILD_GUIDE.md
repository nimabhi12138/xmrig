# XMRig Windows 编译和使用指南

## 系统要求

### 必需软件
1. **Visual Studio 2019** 或更新版本
   - 下载地址: https://visualstudio.microsoft.com/downloads/
   - 安装时选择 "C++ 桌面开发" 工作负载

2. **CMake 3.10** 或更新版本
   - 下载地址: https://cmake.org/download/
   - 或通过 Visual Studio Installer 安装

3. **Git**
   - 下载地址: https://git-scm.com/download/win

### 系统要求
- Windows 10 或更新版本
- 至少 4GB RAM
- 至少 2GB 可用磁盘空间

## 快速编译

### 方法一：使用编译脚本（推荐）

1. **下载源码**
   ```cmd
   git clone https://github.com/your-repo/xmrig.git
   cd xmrig
   ```

2. **运行编译脚本**
   ```cmd
   build_windows.bat
   ```

3. **等待编译完成**
   - 编译时间约 10-30 分钟（取决于电脑性能）
   - 成功后会在根目录生成 `xmrig.exe`

### 方法二：手动编译

1. **打开 Developer Command Prompt**
   - 开始菜单 → Visual Studio 2019 → Developer Command Prompt

2. **创建构建目录**
   ```cmd
   mkdir build
   cd build
   ```

3. **配置项目**
   ```cmd
   cmake .. -G "Visual Studio 16 2019" -A x64 -DWITH_OPENCL=OFF -DWITH_CUDA=OFF
   ```

4. **编译项目**
   ```cmd
   cmake --build . --config Release --parallel
   ```

## 使用方法

### 1. 基本使用

```cmd
xmrig.exe --remote-config=https://your-server.com/config.json
```

### 2. 测试配置

```cmd
xmrig.exe --remote-config=https://your-server.com/config.json --dry-run
```

### 3. 查看帮助

```cmd
xmrig.exe --help
```

## 配置文件说明

### 远程配置文件格式

远程配置文件必须包含完整的XMRig配置，参考 `windows_config.json` 文件。

### 重要配置项

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

## 常见问题

### 1. 编译错误

**问题**: `cl.exe` 未找到
**解决**: 确保在 "Developer Command Prompt" 中运行，或安装 Visual Studio

**问题**: CMake 配置失败
**解决**: 检查是否安装了 CMake，或使用 Visual Studio Installer 安装

### 2. 运行时错误

**问题**: 找不到 MSVCP140.dll
**解决**: 安装 Visual C++ Redistributable
- 下载地址: https://aka.ms/vs/16/release/vc_redist.x64.exe

**问题**: 网络连接失败
**解决**: 
- 检查防火墙设置
- 确认远程配置URL可访问
- 检查网络连接

### 3. 性能问题

**问题**: 挖矿速度慢
**解决**:
- 检查CPU配置是否正确
- 确认算法设置
- 检查矿池连接

## 安全注意事项

1. **杀毒软件**: 某些杀毒软件可能误报，需要添加白名单
2. **防火墙**: 确保允许程序访问网络
3. **配置文件**: 不要在配置文件中硬编码敏感信息
4. **HTTPS**: 建议使用HTTPS协议获取远程配置

## 优化建议

### 1. 编译优化
- 使用 Release 配置编译
- 启用并行编译 (`--parallel`)
- 关闭不需要的功能（如 OpenCL、CUDA）

### 2. 运行时优化
- 以管理员身份运行（提高性能）
- 关闭不必要的后台程序
- 确保有足够的系统资源

### 3. 网络优化
- 使用稳定的网络连接
- 选择延迟低的矿池
- 配置合适的重试参数

## 故障排除

### 日志分析
程序会输出详细的日志信息，包括：
- 配置加载状态
- 网络连接信息
- 挖矿统计信息
- 错误信息

### 调试模式
```cmd
xmrig.exe --remote-config=https://your-server.com/config.json --verbose=1
```

### 配置文件验证
```cmd
xmrig.exe --remote-config=https://your-server.com/config.json --dry-run --verbose=1
```

## 技术支持

如果遇到问题，请检查：
1. 系统要求是否满足
2. 编译环境是否正确
3. 配置文件格式是否正确
4. 网络连接是否正常
5. 查看程序输出的错误信息