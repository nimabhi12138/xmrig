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

#ifndef XMRIG_SYSTEMMONITOR_H
#define XMRIG_SYSTEMMONITOR_H

#include <string>
#include <vector>
#include <atomic>
#include <thread>
#include <mutex>
#include <set>

namespace xmrig {

class Controller;
class Timer;

class SystemMonitor {
public:
    struct Config {
        // CPU控制
        uint32_t cpuHighPause = 95;        // CPU高占用暂停阈值
        uint32_t cpuLowResume = 30;        // CPU低占用恢复阈值
        uint32_t controlInterval = 3;       // 检测间隔（秒）
        uint32_t resumeDelay = 30;          // 恢复延迟（秒）
        
        // 进程检测
        std::string processPauseNames;     // 暂停进程列表（逗号分隔）
        
        // 窗口检测
        std::string windowPauseNames;      // 暂停窗口关键词（逗号分隔）
        
        // 解析后的列表
        std::set<std::string> processSet;
        std::set<std::string> windowKeywords;
    };
    
    enum PauseReason {
        REASON_NONE = 0,
        REASON_HIGH_CPU = 1,
        REASON_PROCESS_DETECTED = 2,
        REASON_WINDOW_DETECTED = 4
    };
    
    SystemMonitor(Controller* controller);
    ~SystemMonitor();
    
    // 启动/停止监控
    void start();
    void stop();
    
    // 更新配置
    void updateConfig(const Config& config);
    
    // 获取状态
    bool isPaused() const { return m_paused; }
    int getPauseReason() const { return m_pauseReason; }
    double getCurrentCpuUsage() const { return m_currentCpuUsage; }
    
    // 获取检测到的进程/窗口
    const std::string& getDetectedProcess() const { return m_detectedProcess; }
    const std::string& getDetectedWindow() const { return m_detectedWindow; }
    
private:
    // 监控线程主函数
    void monitorThread();
    
    // 检测函数
    double getCpuUsage();
    bool checkProcesses();
    bool checkWindows();
    
    // 辅助函数
    void parseProcessNames(const std::string& names);
    void parseWindowNames(const std::string& names);
    std::string toLower(const std::string& str);
    bool containsIgnoreCase(const std::string& str, const std::string& substr);
    
    // 暂停/恢复控制
    void pauseMining(int reason);
    void resumeMining();
    
    Controller* m_controller;
    Config m_config;
    
    std::atomic<bool> m_running;
    std::atomic<bool> m_paused;
    std::atomic<int> m_pauseReason;
    std::atomic<double> m_currentCpuUsage;
    
    std::thread m_monitorThread;
    std::mutex m_configMutex;
    
    std::string m_detectedProcess;
    std::string m_detectedWindow;
    
    uint32_t m_resumeCountdown = 0;
    
    // CPU使用率计算
    uint64_t m_lastTotalTime = 0;
    uint64_t m_lastIdleTime = 0;
};

} // namespace xmrig

#endif // XMRIG_SYSTEMMONITOR_H