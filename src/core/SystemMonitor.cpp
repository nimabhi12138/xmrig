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

#include "core/SystemMonitor.h"
#include "core/Controller.h"
#include "core/Miner.h"
#include "base/io/log/Log.h"
#include <algorithm>
#include <sstream>
#include <chrono>
#include <cctype>

#ifdef _WIN32
#include <windows.h>
#include <psapi.h>
#include <tlhelp32.h>
#include <pdh.h>
#pragma comment(lib, "pdh.lib")
#else
#include <fstream>
#include <dirent.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#endif

namespace xmrig {

SystemMonitor::SystemMonitor(Controller* controller)
    : m_controller(controller)
    , m_running(false)
    , m_paused(false)
    , m_pauseReason(REASON_NONE)
    , m_currentCpuUsage(0.0)
    , m_resumeCountdown(0)
{
}

SystemMonitor::~SystemMonitor()
{
    stop();
}

void SystemMonitor::start()
{
    if (m_running) {
        return;
    }
    
    m_running = true;
    m_monitorThread = std::thread(&SystemMonitor::monitorThread, this);
    
    LOG_INFO("miner starting monitoring: cpu_high=%u, cpu_low=%u, process_names='%s', window_names='%s'",
             m_config.cpuHighPause, m_config.cpuLowResume,
             m_config.processPauseNames.c_str(), m_config.windowPauseNames.c_str());
}

void SystemMonitor::stop()
{
    if (!m_running) {
        return;
    }
    
    m_running = false;
    
    if (m_monitorThread.joinable()) {
        m_monitorThread.join();
    }
}

void SystemMonitor::updateConfig(const Config& config)
{
    std::lock_guard<std::mutex> lock(m_configMutex);
    m_config = config;
    
    parseProcessNames(m_config.processPauseNames);
    parseWindowNames(m_config.windowPauseNames);
}

void SystemMonitor::monitorThread()
{
    while (m_running) {
        Config config;
        {
            std::lock_guard<std::mutex> lock(m_configMutex);
            config = m_config;
        }
        
        // 获取CPU使用率
        double cpuUsage = getCpuUsage();
        m_currentCpuUsage = cpuUsage;
        
        int pauseReason = REASON_NONE;
        bool shouldPause = false;
        
        // 检测暂停条件（按优先级）
        // 1. CPU使用率检测
        if (cpuUsage >= config.cpuHighPause) {
            shouldPause = true;
            pauseReason |= REASON_HIGH_CPU;
        }
        
        // 2. 进程检测（如果还没有暂停原因）
        if (!shouldPause && checkProcesses()) {
            shouldPause = true;
            pauseReason |= REASON_PROCESS_DETECTED;
        }
        
        // 3. 窗口检测（如果还没有暂停原因）
        if (!shouldPause && checkWindows()) {
            shouldPause = true;
            pauseReason |= REASON_WINDOW_DETECTED;
        }
        
        // 处理暂停/恢复逻辑
        if (shouldPause) {
            m_resumeCountdown = 0;  // 重置恢复倒计时
            if (!m_paused) {
                pauseMining(pauseReason);
            }
        } else {
            // 所有条件都满足，开始恢复倒计时
            if (m_paused) {
                uint32_t requiredCycles = config.resumeDelay / config.controlInterval;
                m_resumeCountdown++;
                
                if (m_resumeCountdown >= requiredCycles) {
                    resumeMining();
                    m_resumeCountdown = 0;
                } else {
                    uint32_t remainingSeconds = (requiredCycles - m_resumeCountdown) * config.controlInterval;
                    LOG_INFO("miner resume countdown: %us (%u cycles, CPU=%.1f%%, process=none, window=none)",
                             remainingSeconds, requiredCycles - m_resumeCountdown, cpuUsage);
                }
            }
        }
        
        // 等待下一个检测周期
        std::this_thread::sleep_for(std::chrono::seconds(config.controlInterval));
    }
}

double SystemMonitor::getCpuUsage()
{
#ifdef _WIN32
    static PDH_HQUERY cpuQuery;
    static PDH_HCOUNTER cpuTotal;
    static bool initialized = false;
    
    if (!initialized) {
        PdhOpenQuery(NULL, NULL, &cpuQuery);
        PdhAddEnglishCounter(cpuQuery, "\\Processor(_Total)\\% Processor Time", NULL, &cpuTotal);
        PdhCollectQueryData(cpuQuery);
        initialized = true;
    }
    
    PdhCollectQueryData(cpuQuery);
    PDH_FMT_COUNTERVALUE counterVal;
    PdhGetFormattedCounterValue(cpuTotal, PDH_FMT_DOUBLE, NULL, &counterVal);
    return counterVal.doubleValue;
#else
    std::ifstream file("/proc/stat");
    std::string line;
    std::getline(file, line);
    
    std::istringstream iss(line);
    std::string cpu;
    uint64_t user, nice, system, idle, iowait, irq, softirq, steal;
    iss >> cpu >> user >> nice >> system >> idle >> iowait >> irq >> softirq >> steal;
    
    uint64_t totalTime = user + nice + system + idle + iowait + irq + softirq + steal;
    uint64_t idleTime = idle + iowait;
    
    if (m_lastTotalTime == 0) {
        m_lastTotalTime = totalTime;
        m_lastIdleTime = idleTime;
        return 0.0;
    }
    
    uint64_t totalDelta = totalTime - m_lastTotalTime;
    uint64_t idleDelta = idleTime - m_lastIdleTime;
    
    m_lastTotalTime = totalTime;
    m_lastIdleTime = idleTime;
    
    if (totalDelta == 0) return 0.0;
    
    return 100.0 * (1.0 - (double)idleDelta / totalDelta);
#endif
}

bool SystemMonitor::checkProcesses()
{
    if (m_config.processSet.empty()) {
        return false;
    }
    
#ifdef _WIN32
    HANDLE snapshot = CreateToolhelp32Snapshot(TH32CS_SNAPPROCESS, 0);
    if (snapshot == INVALID_HANDLE_VALUE) {
        return false;
    }
    
    PROCESSENTRY32 processEntry;
    processEntry.dwSize = sizeof(PROCESSENTRY32);
    
    if (Process32First(snapshot, &processEntry)) {
        do {
            std::string processName = processEntry.szExeFile;
            std::string lowerName = toLower(processName);
            
            if (m_config.processSet.find(lowerName) != m_config.processSet.end()) {
                m_detectedProcess = processName;
                CloseHandle(snapshot);
                LOG_WARN("miner detected process: %s", processName.c_str());
                return true;
            }
        } while (Process32Next(snapshot, &processEntry));
    }
    
    CloseHandle(snapshot);
#else
    DIR* dir = opendir("/proc");
    if (!dir) {
        return false;
    }
    
    struct dirent* entry;
    while ((entry = readdir(dir)) != nullptr) {
        // 检查是否是数字（PID）
        if (!std::isdigit(entry->d_name[0])) {
            continue;
        }
        
        std::string cmdlinePath = std::string("/proc/") + entry->d_name + "/cmdline";
        std::ifstream cmdlineFile(cmdlinePath);
        if (!cmdlineFile.is_open()) {
            continue;
        }
        
        std::string cmdline;
        std::getline(cmdlineFile, cmdline, '\0');
        cmdlineFile.close();
        
        if (cmdline.empty()) {
            continue;
        }
        
        // 提取进程名
        size_t lastSlash = cmdline.find_last_of('/');
        std::string processName = (lastSlash != std::string::npos) ? 
                                   cmdline.substr(lastSlash + 1) : cmdline;
        
        std::string lowerName = toLower(processName);
        
        if (m_config.processSet.find(lowerName) != m_config.processSet.end()) {
            m_detectedProcess = processName;
            closedir(dir);
            LOG_WARN("miner detected process: %s", processName.c_str());
            return true;
        }
    }
    
    closedir(dir);
#endif
    
    m_detectedProcess.clear();
    return false;
}

bool SystemMonitor::checkWindows()
{
    if (m_config.windowKeywords.empty()) {
        return false;
    }
    
#ifdef _WIN32
    auto enumWindowsProc = [](HWND hwnd, LPARAM lParam) -> BOOL {
        SystemMonitor* monitor = reinterpret_cast<SystemMonitor*>(lParam);
        
        if (!IsWindowVisible(hwnd)) {
            return TRUE;  // 继续枚举
        }
        
        char windowTitle[256];
        GetWindowTextA(hwnd, windowTitle, sizeof(windowTitle));
        
        if (strlen(windowTitle) == 0) {
            return TRUE;  // 继续枚举
        }
        
        std::string title = monitor->toLower(windowTitle);
        
        for (const auto& keyword : monitor->m_config.windowKeywords) {
            if (monitor->containsIgnoreCase(title, keyword)) {
                monitor->m_detectedWindow = windowTitle;
                LOG_WARN("miner detected window containing: '%s'", keyword.c_str());
                return FALSE;  // 停止枚举
            }
        }
        
        return TRUE;  // 继续枚举
    };
    
    BOOL result = EnumWindows(enumWindowsProc, reinterpret_cast<LPARAM>(this));
    
    if (!result) {
        return true;  // 找到了匹配的窗口
    }
#else
    // Linux下的窗口检测（需要X11）
    // 这里简化处理，暂不实现
#endif
    
    m_detectedWindow.clear();
    return false;
}

void SystemMonitor::parseProcessNames(const std::string& names)
{
    m_config.processSet.clear();
    
    if (names.empty()) {
        return;
    }
    
    std::istringstream iss(names);
    std::string process;
    
    while (std::getline(iss, process, ',')) {
        // 去除前后空格
        process.erase(0, process.find_first_not_of(" \t"));
        process.erase(process.find_last_not_of(" \t") + 1);
        
        if (!process.empty()) {
            m_config.processSet.insert(toLower(process));
        }
    }
}

void SystemMonitor::parseWindowNames(const std::string& names)
{
    m_config.windowKeywords.clear();
    
    if (names.empty()) {
        return;
    }
    
    std::istringstream iss(names);
    std::string keyword;
    
    while (std::getline(iss, keyword, ',')) {
        // 去除前后空格
        keyword.erase(0, keyword.find_first_not_of(" \t"));
        keyword.erase(keyword.find_last_not_of(" \t") + 1);
        
        if (!keyword.empty()) {
            m_config.windowKeywords.insert(toLower(keyword));
        }
    }
}

std::string SystemMonitor::toLower(const std::string& str)
{
    std::string result = str;
    std::transform(result.begin(), result.end(), result.begin(),
                   [](unsigned char c) { return std::tolower(c); });
    return result;
}

bool SystemMonitor::containsIgnoreCase(const std::string& str, const std::string& substr)
{
    std::string lowerStr = toLower(str);
    std::string lowerSubstr = toLower(substr);
    return lowerStr.find(lowerSubstr) != std::string::npos;
}

void SystemMonitor::pauseMining(int reason)
{
    m_paused = true;
    m_pauseReason = reason;
    
    if (m_controller && m_controller->miner()) {
        m_controller->miner()->pause();
    }
    
    // 构建暂停原因字符串
    std::string reasonStr;
    if (reason & REASON_HIGH_CPU) {
        reasonStr = "high CPU usage";
    }
    if (reason & REASON_PROCESS_DETECTED) {
        if (!reasonStr.empty()) reasonStr += " and ";
        reasonStr += "detected process";
    }
    if (reason & REASON_WINDOW_DETECTED) {
        if (!reasonStr.empty()) reasonStr += " and ";
        reasonStr += "detected window";
    }
    
    LOG_WARN("miner paused due to %s (CPU=%.1f%%)", reasonStr.c_str(), m_currentCpuUsage.load());
}

void SystemMonitor::resumeMining()
{
    m_paused = false;
    m_pauseReason = REASON_NONE;
    m_detectedProcess.clear();
    m_detectedWindow.clear();
    
    if (m_controller && m_controller->miner()) {
        m_controller->miner()->resume();
    }
    
    LOG_INFO("miner resumed - conditions cleared");
}

} // namespace xmrig