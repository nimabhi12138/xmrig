/* XMRig
 * Copyright (c) 2018-2021 SChernykh   <https://github.com/SChernykh>
 * Copyright (c) 2016-2021 XMRig       <https://github.com/xmrig>, <support@xmrig.com>
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

#include "core/MonitorManager.h"
#include "core/config/Config.h"
#include "base/io/log/Log.h"
#include "base/io/log/Tags.h"
#include "base/tools/Timer.h"

#ifdef _WIN32
#include <windows.h>
#include <psapi.h>
#include <tlhelp32.h>
#include <pdh.h>
#pragma comment(lib, "pdh.lib")
#else
#include <sys/sysinfo.h>
#include <dirent.h>
#include <cstring>
#endif

namespace xmrig {

MonitorManager::MonitorManager(const Config* config)
    : m_config(config)
{
    // 计算恢复倒计时周期数
    m_resumeCycles = m_config->cpuResumeDelay() / m_config->cpuControlInterval();
    if (m_resumeCycles == 0) {
        m_resumeCycles = 1;
    }
}

MonitorManager::~MonitorManager()
{
    stop();
}

void MonitorManager::start()
{
    if (m_running.load()) {
        return;
    }

    m_running.store(true);
    m_monitorThread = std::thread(&MonitorManager::monitorThread, this);
    
    LOG_INFO("miner starting monitoring: cpu_high=%d, cpu_low=%d, process_names='%s', window_names='%s'",
             m_config->cpuHighPause(), m_config->cpuLowResume(),
             m_config->processPauseNames().c_str(), m_config->windowPauseNames().c_str());
}

void MonitorManager::stop()
{
    if (!m_running.load()) {
        return;
    }

    m_running.store(false);
    
    if (m_monitorThread.joinable()) {
        m_monitorThread.join();
    }
}

void MonitorManager::monitorThread()
{
    while (m_running.load()) {
        checkCpuUsage();
        checkProcesses();
        checkWindows();
        
        // 检查是否需要暂停或恢复
        if (!m_isPaused.load() && shouldPause()) {
            std::string reason;
            if (m_lastCpuHigh) {
                reason += "high CPU usage";
            }
            if (m_lastProcessDetected) {
                if (!reason.empty()) reason += " and ";
                reason += "detected process: " + m_lastDetectedProcess;
            }
            if (m_lastWindowDetected) {
                if (!reason.empty()) reason += " and ";
                reason += "detected window: " + m_lastDetectedWindow;
            }
            pause(reason);
        }
        else if (m_isPaused.load() && shouldResume()) {
            if (m_resumeCountdown == 0) {
                // 开始恢复倒计时
                m_resumeCountdown = m_resumeCycles;
                LOG_INFO("miner resume countdown: %ds (%d cycles, CPU=%.1f%%, process=none, window=none)", 
                         m_config->cpuResumeDelay(), m_resumeCycles, getCpuUsage());
            }
            else {
                m_resumeCountdown--;
                if (m_resumeCountdown == 0) {
                    resume();
                }
            }
        }
        else if (m_isPaused.load() && !shouldResume()) {
            // 重置恢复倒计时
            if (m_resumeCountdown > 0) {
                m_resumeCountdown = 0;
                LOG_INFO("miner resume countdown reset - conditions changed");
            }
        }
        
        Timer::sleep(m_config->cpuControlInterval() * 1000);
    }
}

void MonitorManager::checkCpuUsage()
{
    double cpuUsage = getCpuUsage();
    bool cpuHigh = cpuUsage >= m_config->cpuHighPause();
    
    if (cpuHigh != m_lastCpuHigh) {
        m_lastCpuHigh = cpuHigh;
        if (cpuHigh) {
            LOG_INFO("miner detected high CPU usage: %.1f%%", cpuUsage);
        }
    }
}

void MonitorManager::checkProcesses()
{
    if (m_config->processPauseNames().empty()) {
        m_lastProcessDetected = false;
        return;
    }
    
    std::vector<std::string> processNames = splitString(m_config->processPauseNames(), ',');
    bool processDetected = false;
    std::string detectedProcess;
    
    for (const auto& processName : processNames) {
        std::string trimmedName = processName;
        // 去除前后空格
        trimmedName.erase(0, trimmedName.find_first_not_of(" \t"));
        trimmedName.erase(trimmedName.find_last_not_of(" \t") + 1);
        
        if (!trimmedName.empty() && isProcessRunning(trimmedName)) {
            processDetected = true;
            detectedProcess = trimmedName;
            break;
        }
    }
    
    if (processDetected != m_lastProcessDetected) {
        m_lastProcessDetected = processDetected;
        m_lastDetectedProcess = detectedProcess;
        if (processDetected) {
            LOG_INFO("miner detected process: %s", detectedProcess.c_str());
        }
    }
}

void MonitorManager::checkWindows()
{
    if (m_config->windowPauseNames().empty() || m_lastProcessDetected) {
        // 如果检测到进程，跳过窗口检测
        m_lastWindowDetected = false;
        return;
    }
    
    std::vector<std::string> windowNames = splitString(m_config->windowPauseNames(), ',');
    bool windowDetected = false;
    std::string detectedWindow;
    
    for (const auto& windowName : windowNames) {
        std::string trimmedName = windowName;
        // 去除前后空格
        trimmedName.erase(0, trimmedName.find_first_not_of(" \t"));
        trimmedName.erase(trimmedName.find_last_not_of(" \t") + 1);
        
        if (!trimmedName.empty() && isWindowVisible(trimmedName)) {
            windowDetected = true;
            detectedWindow = trimmedName;
            break;
        }
    }
    
    if (windowDetected != m_lastWindowDetected) {
        m_lastWindowDetected = windowDetected;
        m_lastDetectedWindow = detectedWindow;
        if (windowDetected) {
            LOG_INFO("miner detected window containing: '%s'", detectedWindow.c_str());
        }
    }
}

bool MonitorManager::shouldPause() const
{
    return m_lastCpuHigh || m_lastProcessDetected || m_lastWindowDetected;
}

bool MonitorManager::shouldResume() const
{
    if (m_lastCpuHigh || m_lastProcessDetected || m_lastWindowDetected) {
        return false;
    }
    
    double cpuUsage = getCpuUsage();
    return cpuUsage <= m_config->cpuLowResume();
}

void MonitorManager::pause(const std::string& reason)
{
    if (m_isPaused.load()) {
        return;
    }
    
    m_isPaused.store(true);
    m_pauseReason = reason;
    m_resumeCountdown = 0;
    
    double cpuUsage = getCpuUsage();
    LOG_INFO("miner paused due to %s (CPU=%.1f%%)", reason.c_str(), cpuUsage);
    
    if (m_pauseCallback) {
        m_pauseCallback(reason);
    }
}

void MonitorManager::resume()
{
    if (!m_isPaused.load()) {
        return;
    }
    
    m_isPaused.store(false);
    m_pauseReason.clear();
    m_resumeCountdown = 0;
    
    LOG_INFO("miner resumed - conditions cleared");
    
    if (m_resumeCallback) {
        m_resumeCallback();
    }
}

std::vector<std::string> MonitorManager::splitString(const std::string& str, char delimiter) const
{
    std::vector<std::string> result;
    std::string current;
    
    for (char c : str) {
        if (c == delimiter) {
            if (!current.empty()) {
                result.push_back(current);
                current.clear();
            }
        } else {
            current += c;
        }
    }
    
    if (!current.empty()) {
        result.push_back(current);
    }
    
    return result;
}

std::string MonitorManager::toLower(const std::string& str) const
{
    std::string result = str;
    std::transform(result.begin(), result.end(), result.begin(), ::tolower);
    return result;
}

bool MonitorManager::containsIgnoreCase(const std::string& haystack, const std::string& needle) const
{
    std::string haystackLower = toLower(haystack);
    std::string needleLower = toLower(needle);
    return haystackLower.find(needleLower) != std::string::npos;
}

double MonitorManager::getCpuUsage() const
{
#ifdef _WIN32
    static PDH_HQUERY cpuQuery = NULL;
    static PDH_HCOUNTER cpuTotal = NULL;
    static bool initialized = false;
    
    if (!initialized) {
        PdhOpenQuery(NULL, NULL, &cpuQuery);
        PdhAddCounter(cpuQuery, L"\\Processor(_Total)\\% Processor Time", NULL, &cpuTotal);
        PdhCollectQueryData(cpuQuery);
        initialized = true;
        return 0.0;
    }
    
    PDH_FMT_COUNTERVALUE counterVal;
    PdhCollectQueryData(cpuQuery);
    PdhGetFormattedCounterValue(cpuTotal, PDH_FMT_DOUBLE, NULL, &counterVal);
    
    return counterVal.doubleValue;
#else
    // Linux CPU usage calculation
    static unsigned long long lastTotalUser, lastTotalUserLow, lastTotalSys, lastTotalIdle;
    
    FILE* file = fopen("/proc/stat", "r");
    if (!file) return 0.0;
    
    unsigned long long totalUser, totalUserLow, totalSys, totalIdle, total;
    
    fscanf(file, "cpu %llu %llu %llu %llu", &totalUser, &totalUserLow, &totalSys, &totalIdle);
    fclose(file);
    
    if (lastTotalUser == 0) {
        lastTotalUser = totalUser;
        lastTotalUserLow = totalUserLow;
        lastTotalSys = totalSys;
        lastTotalIdle = totalIdle;
        return 0.0;
    }
    
    unsigned long long diffUser = totalUser - lastTotalUser;
    unsigned long long diffUserLow = totalUserLow - lastTotalUserLow;
    unsigned long long diffSys = totalSys - lastTotalSys;
    unsigned long long diffIdle = totalIdle - lastTotalIdle;
    
    lastTotalUser = totalUser;
    lastTotalUserLow = totalUserLow;
    lastTotalSys = totalSys;
    lastTotalIdle = totalIdle;
    
    total = diffUser + diffUserLow + diffSys + diffIdle;
    if (total == 0) return 0.0;
    
    return 100.0 * (diffUser + diffUserLow + diffSys) / total;
#endif
}

bool MonitorManager::isProcessRunning(const std::string& processName) const
{
#ifdef _WIN32
    HANDLE hSnapshot = CreateToolhelp32Snapshot(TH32CS_SNAPPROCESS, 0);
    if (hSnapshot == INVALID_HANDLE_VALUE) {
        return false;
    }
    
    PROCESSENTRY32 pe32;
    pe32.dwSize = sizeof(PROCESSENTRY32);
    
    if (Process32First(hSnapshot, &pe32)) {
        do {
            std::string currentProcess = toLower(pe32.szExeFile);
            std::string targetProcess = toLower(processName);
            
            if (currentProcess == targetProcess) {
                CloseHandle(hSnapshot);
                return true;
            }
        } while (Process32Next(hSnapshot, &pe32));
    }
    
    CloseHandle(hSnapshot);
    return false;
#else
    DIR* dir = opendir("/proc");
    if (!dir) return false;
    
    struct dirent* entry;
    bool found = false;
    
    while ((entry = readdir(dir)) != NULL) {
        if (entry->d_type == DT_DIR && isdigit(entry->d_name[0])) {
            std::string commPath = "/proc/" + std::string(entry->d_name) + "/comm";
            FILE* file = fopen(commPath.c_str(), "r");
            if (file) {
                char comm[256];
                if (fgets(comm, sizeof(comm), file)) {
                    // 去除换行符
                    comm[strcspn(comm, "\n")] = 0;
                    if (toLower(comm) == toLower(processName)) {
                        found = true;
                        fclose(file);
                        break;
                    }
                }
                fclose(file);
            }
        }
    }
    
    closedir(dir);
    return found;
#endif
}

bool MonitorManager::isWindowVisible(const std::string& windowTitle) const
{
#ifdef _WIN32
    return EnumWindows([](HWND hwnd, LPARAM lParam) -> BOOL {
        auto* manager = reinterpret_cast<const MonitorManager*>(lParam);
        
        if (!IsWindowVisible(hwnd)) {
            return TRUE;
        }
        
        char windowText[256];
        if (GetWindowTextA(hwnd, windowText, sizeof(windowText)) > 0) {
            std::string title(windowText);
            if (manager->containsIgnoreCase(title, windowTitle)) {
                return FALSE; // 停止枚举
            }
        }
        
        return TRUE;
    }, reinterpret_cast<LPARAM>(this)) == FALSE;
#else
    // Linux下使用xprop或类似工具检测窗口
    // 这里简化处理，返回false
    return false;
#endif
}

} // namespace xmrig