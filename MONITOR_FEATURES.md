# XMRig 监控功能说明

## 概述

XMRig 现在支持智能监控功能，可以自动检测系统状态并相应地暂停或恢复挖矿。监控功能包括：

1. **CPU使用率监控** - 当CPU使用率过高时暂停挖矿
2. **进程检测** - 检测特定进程运行时暂停挖矿
3. **窗口检测** - 检测特定窗口标题时暂停挖矿
4. **智能恢复** - 所有条件满足后自动恢复挖矿

## 功能特性

### 🔍 统一监控线程
- 所有检测功能使用同一个线程
- 可配置检测间隔（默认3秒）
- 性能优化，检测到一个条件立即停止后续检测

### ⚡ CPU监控
- **高占用暂停**: CPU使用率 >= 95% 时暂停挖矿
- **低占用恢复**: CPU使用率 <= 30% 时可以恢复挖矿
- **实时检测**: 使用PDH（Windows）或/proc/stat（Linux）获取准确CPU使用率

### 🔍 进程检测
- **精确匹配**: 进程名必须完全一致
- **不区分大小写**: taskmgr.exe = Taskmgr.exe = TASKMGR.EXE
- **动态配置**: 支持通过远程JSON更新进程列表
- **高性能**: 使用系统API快速检测

### 🪟 窗口检测
- **模糊匹配**: 包含关键词即匹配
- **不区分大小写**: Administrator = administrator = ADMINISTRATOR
- **智能优化**: 只在进程未检测到时才检测窗口
- **只检测可见窗口**: 提高性能

### 🔄 智能恢复
- **延迟恢复**: 所有条件满足后等待30秒再恢复
- **倒计时显示**: 显示恢复倒计时
- **条件重置**: 恢复过程中任何暂停条件重新出现，倒计时立即重置

## 配置参数

### CPU控制设置
```json
{
  "cpu-high-pause": 95,                // CPU高占用暂停阈值（%）
  "cpu-low-resume": 30,                // CPU低占用恢复阈值（%）
  "cpu-control-interval": 3,           // 检测间隔（秒）
  "cpu-resume-delay": 30               // 恢复延迟（秒）
}
```

### 进程检测设置
```json
{
  "process-pause-names": "taskmgr.exe,processhacker.exe,procexp.exe"
}
```

### 窗口检测设置
```json
{
  "window-pause-names": "administrator,任务管理器,task manager"
}
```

## 工作逻辑

### 暂停条件（满足任一即暂停）
1. **CPU使用率** >= cpu-high-pause (95%)
2. **检测到进程** - process-pause-names 中的任何进程
3. **检测到窗口** - window-pause-names 中任何关键词的窗口标题

### 恢复条件（必须全部满足）
1. **CPU使用率** <= cpu-low-resume (30%)
2. **没有检测到**任何配置的进程
3. **没有检测到**任何配置的窗口
4. **等待延迟** cpu-resume-delay (30秒)

### 检测优化策略
- **统一间隔**: 每 cpu-control-interval (3秒) 执行一次检测循环
- **顺序检测**: CPU → 进程 → 窗口
- **早期退出**: 一旦发现任何暂停条件立即停止后续检测
- **智能缓存**: 缓存上次检测结果，避免重复检测

## 日志输出示例

### 启动监控
```
[INFO] miner starting monitoring: cpu_high=95, cpu_low=30, process_names='taskmgr.exe,processhacker.exe', window_names='administrator,task manager'
```

### 检测到进程
```
[INFO] miner detected process: taskmgr.exe
[INFO] miner paused due to detected process: taskmgr.exe (CPU=45%)
```

### 检测到窗口
```
[INFO] miner detected window containing: 'administrator'
[INFO] miner paused due to detected window: administrator (CPU=45%)
```

### 检测到高CPU
```
[INFO] miner detected high CPU usage: 99.5%
[INFO] miner paused due to high CPU usage (CPU=99.5%)
```

### 恢复倒计时
```
[INFO] miner resume countdown: 30s (10 cycles, CPU=25%, process=none, window=none)
[INFO] miner resumed - conditions cleared
```

## 性能优化

### 检测优化
- **统一线程**: 所有检测使用同一个线程，减少系统开销
- **智能缓存**: 缓存检测结果，避免重复计算
- **早期退出**: 检测到条件立即停止，不浪费CPU
- **窗口优化**: 只在进程未检测到时才检测窗口

### 系统友好
- **低优先级**: 监控线程使用低优先级
- **可配置间隔**: 可根据需要调整检测频率
- **资源节约**: 最小化对系统性能的影响

## 安全特性

### 进程检测
- **系统级检测**: 使用系统API，无法被普通程序绕过
- **实时更新**: 支持动态更新进程列表
- **精确匹配**: 避免误报

### 窗口检测
- **模糊匹配**: 支持关键词匹配，更灵活
- **大小写不敏感**: 提高检测准确性
- **可见性检查**: 只检测可见窗口

## 使用建议

### 配置建议
1. **CPU阈值**: 根据系统性能调整，建议85-95%
2. **检测间隔**: 建议3-5秒，平衡性能和响应速度
3. **恢复延迟**: 建议30-60秒，避免频繁启停
4. **进程列表**: 根据实际需要添加或删除进程

### 性能建议
1. **合理配置**: 不要设置过低的CPU阈值
2. **适度检测**: 不要设置过短的检测间隔
3. **必要进程**: 只添加真正需要检测的进程
4. **关键词优化**: 使用准确的关键词，避免误报

## 故障排除

### 常见问题
1. **误报**: 检查进程名和窗口关键词是否准确
2. **性能影响**: 调整检测间隔和CPU阈值
3. **不恢复**: 检查恢复条件是否全部满足
4. **日志异常**: 查看详细日志输出

### 调试方法
1. **启用详细日志**: 查看检测过程
2. **测试配置**: 使用dry-run模式测试
3. **逐步调试**: 逐个测试各个检测功能
4. **系统监控**: 使用系统工具验证检测结果

## 技术实现

### 平台支持
- **Windows**: 使用PDH API获取CPU使用率，Toolhelp32 API检测进程，EnumWindows检测窗口
- **Linux**: 使用/proc/stat获取CPU使用率，/proc目录检测进程，xprop检测窗口

### 线程安全
- **原子操作**: 使用std::atomic确保线程安全
- **回调机制**: 使用std::function实现异步通知
- **资源管理**: 自动管理线程生命周期

### 扩展性
- **模块化设计**: 各检测功能独立，易于扩展
- **配置驱动**: 通过JSON配置控制功能
- **插件化**: 支持添加新的检测类型