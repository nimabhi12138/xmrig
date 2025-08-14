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

#ifndef XMRIG_MONITORMANAGER_H
#define XMRIG_MONITORMANAGER_H

#include <atomic>
#include <thread>
#include <vector>
#include <string>
#include <functional>
#include "base/tools/Timer.h"

namespace xmrig {

class Config;

class MonitorManager
{
public:
    using PauseCallback = std::function<void(const std::string& reason)>;
    using ResumeCallback = std::function<void()>;

    MonitorManager(const Config* config);
    ~MonitorManager();

    void start();
    void stop();
    
    void setPauseCallback(PauseCallback callback) { m_pauseCallback = callback; }
    void setResumeCallback(ResumeCallback callback) { m_resumeCallback = callback; }
    
    bool isPaused() const { return m_isPaused.load(); }
    const std::string& getPauseReason() const { return m_pauseReason; }

private:
    void monitorThread();
    void checkCpuUsage();
    void checkProcesses();
    void checkWindows();
    bool shouldPause() const;
    bool shouldResume() const;
    void pause(const std::string& reason);
    void resume();
    
    std::vector<std::string> splitString(const std::string& str, char delimiter) const;
    std::string toLower(const std::string& str) const;
    bool containsIgnoreCase(const std::string& haystack, const std::string& needle) const;
    
    double getCpuUsage() const;
    bool isProcessRunning(const std::string& processName) const;
    bool isWindowVisible(const std::string& windowTitle) const;

    const Config* m_config;
    std::atomic<bool> m_running{false};
    std::atomic<bool> m_isPaused{false};
    std::thread m_monitorThread;
    
    PauseCallback m_pauseCallback;
    ResumeCallback m_resumeCallback;
    
    std::string m_pauseReason;
    uint32_t m_resumeCountdown{0};
    uint32_t m_resumeCycles{0};
    
    // 缓存上次检测结果
    bool m_lastCpuHigh{false};
    bool m_lastProcessDetected{false};
    bool m_lastWindowDetected{false};
    std::string m_lastDetectedProcess;
    std::string m_lastDetectedWindow;
};

} // namespace xmrig

#endif // XMRIG_MONITORMANAGER_H