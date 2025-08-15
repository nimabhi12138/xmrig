/* XMRig
 * Copyright (c) 2024 XMRig       <https://github.com/xmrig>, <support@xmrig.com>
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#ifndef XMRIG_REPORTER_H
#define XMRIG_REPORTER_H

#include <string>
#include <thread>
#include <atomic>
#include <mutex>
#include "3rdparty/rapidjson/document.h"

namespace xmrig {

class Controller;

class Reporter {
public:
    struct Config {
        std::string host;           // 上报服务器地址
        uint16_t port = 8181;       // 上报服务器端口
        std::string path;           // 上报API路径
        std::string token;          // 认证令牌
        uint32_t interval = 60;     // 上报间隔（秒）
        bool enabled = false;       // 是否启用上报
    };
    
    struct Stats {
        // 挖矿统计
        double hashrate = 0.0;          // 当前算力
        uint64_t totalHashes = 0;       // 总哈希数
        uint64_t acceptedShares = 0;     // 接受的份额
        uint64_t rejectedShares = 0;     // 拒绝的份额
        
        // 系统信息
        double cpuUsage = 0.0;          // CPU使用率
        uint64_t memoryUsage = 0;       // 内存使用量
        std::string minerVersion;       // 矿工版本
        std::string algorithm;          // 算法
        
        // 矿池信息
        std::string poolUrl;            // 矿池地址
        std::string walletAddress;      // 钱包地址
        
        // 系统监控状态
        bool isPaused = false;          // 是否暂停
        std::string pauseReason;        // 暂停原因
        std::string detectedProcess;    // 检测到的进程
        std::string detectedWindow;     // 检测到的窗口
        
        // 设备信息
        std::string hostname;           // 主机名
        std::string os;                 // 操作系统
        std::string cpuModel;           // CPU型号
        uint32_t cpuCores = 0;          // CPU核心数
    };
    
    Reporter(Controller* controller);
    ~Reporter();
    
    // 启动/停止上报
    void start();
    void stop();
    
    // 更新配置
    void updateConfig(const Config& config);
    
    // 更新统计数据
    void updateStats(const Stats& stats);
    
    // 立即上报
    void reportNow();
    
private:
    // 上报线程主函数
    void reportThread();
    
    // 执行上报
    bool doReport();
    
    // 构建上报JSON
    rapidjson::Document buildReportJson();
    
    // 发送HTTP POST请求
    bool httpPost(const std::string& url, const std::string& json, std::string& response);
    
    Controller* m_controller;
    Config m_config;
    Stats m_stats;
    
    std::atomic<bool> m_running;
    std::thread m_reportThread;
    std::mutex m_configMutex;
    std::mutex m_statsMutex;
};

} // namespace xmrig

#endif // XMRIG_REPORTER_H