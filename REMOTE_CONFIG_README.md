# XMRig 远程配置功能

## 概述

XMRig 现在支持从网页拉取配置，包括算法、用户钱包、开发钱包、开发抽水以及矿池配置。所有内置配置都已移除，程序在运行时从指定的URL获取完整配置。

## 使用方法

### 1. 命令行参数

使用 `--remote-config` 参数指定远程配置URL：

```bash
./xmrig --remote-config=https://your-server.com/config.json
```

### 2. 远程配置文件格式

远程配置文件必须是一个有效的JSON格式，包含以下主要配置项：

#### 算法配置
```json
{
    "algo": "rx/0",  // 挖矿算法
    "coin": "XMR"    // 币种
}
```

#### 用户钱包配置
```json
{
    "pools": [
        {
            "algo": "rx/0",
            "coin": "XMR",
            "url": "pool.example.com:3333",
            "user": "YOUR_USER_WALLET_ADDRESS",  // 用户钱包地址
            "pass": "x",
            "enabled": true
        }
    ]
}
```

#### 开发钱包配置
```json
{
    "pools": [
        {
            "algo": "rx/0",
            "coin": "XMR",
            "url": "pool.example.com:3333",
            "user": "DEVELOPER_WALLET_ADDRESS",  // 开发钱包地址
            "pass": "x",
            "enabled": true
        }
    ]
}
```

#### 抽水配置
```json
{
    "donate-level": 0,        // 关闭捐赠
    "donate-over-proxy": 0    // 关闭代理捐赠
}
```

### 3. 完整配置示例

参考 `remote_config_example.json` 文件查看完整的配置示例。

## 功能特性

### 自动重试
- 网络连接失败时自动重试
- 默认重试3次，间隔5秒
- 可通过代码修改重试参数

### 错误处理
- 详细的错误日志
- JSON格式验证
- HTTP状态码检查

### 实时配置更新
- 支持运行时重新加载配置
- 配置变更时自动应用

## 安全注意事项

1. **HTTPS推荐**: 建议使用HTTPS协议获取配置
2. **配置验证**: 确保远程配置来源可信
3. **钱包安全**: 不要在配置中硬编码敏感信息

## 开发说明

### 添加新的配置项

1. 在 `Config.h` 中添加新的配置方法
2. 在 `Config.cpp` 中实现配置读取逻辑
3. 在远程配置JSON中添加对应字段

### 自定义重试策略

修改 `RemoteConfig.cpp` 中的重试参数：

```cpp
m_maxRetries = 5;        // 最大重试次数
m_retryInterval = 10000; // 重试间隔(毫秒)
```

## 故障排除

### 常见问题

1. **配置加载失败**
   - 检查网络连接
   - 验证URL是否正确
   - 确认JSON格式有效

2. **配置应用失败**
   - 检查配置字段是否正确
   - 查看日志输出
   - 验证钱包地址格式

3. **性能问题**
   - 调整重试间隔
   - 优化网络请求
   - 检查服务器响应时间

### 日志信息

程序会输出详细的日志信息：

```
[INFO] Fetching configuration from: https://your-server.com/config.json
[INFO] Configuration fetched successfully
[INFO] Applying remote configuration
[INFO] Remote configuration applied successfully
```

## 版本兼容性

- 支持所有现有的XMRig配置选项
- 向后兼容本地配置文件
- 新增远程配置功能不影响现有功能