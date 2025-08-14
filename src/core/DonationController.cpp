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

#include "core/DonationController.h"
#include "core/Controller.h"
#include "base/io/log/Log.h"
#include "base/net/stratum/Job.h"
#include <chrono>

namespace xmrig {

DonationController::DonationController(Controller* controller)
    : m_controller(controller)
    , m_isDonating(false)
    , m_donatedShares(0)
    , m_userShares(0)
    , m_donationTime(0)
    , m_userTime(0)
{
    m_startTime = currentTimeMs();
    m_lastSwitchTime = m_startTime;
}

DonationController::~DonationController()
{
}

void DonationController::updateConfig(const Config& config)
{
    std::lock_guard<std::mutex> lock(m_configMutex);
    
    bool levelChanged = (m_config.donateLevel != config.donateLevel);
    m_config = config;
    
    // 限制捐赠比例在0-100之间
    if (m_config.donateLevel > 100) {
        m_config.donateLevel = 100;
    }
    
    if (levelChanged) {
        LOG_INFO("Donation level changed to %u%%", m_config.donateLevel);
        
        // 重置计时器
        m_startTime = currentTimeMs();
        m_lastSwitchTime = m_startTime;
        m_donationTime = 0;
        m_userTime = 0;
        
        // 立即更新状态
        updateDonationState();
    }
}

bool DonationController::shouldDonate() const
{
    return m_isDonating;
}

std::string DonationController::getCurrentWallet() const
{
    std::lock_guard<std::mutex> lock(const_cast<std::mutex&>(m_configMutex));
    
    if (m_isDonating && !m_config.donateAddress.empty()) {
        return m_config.donateAddress;
    }
    
    // 返回空字符串表示使用用户钱包
    return "";
}

void DonationController::onJob(const Job& job)
{
    updateDonationState();
}

double DonationController::getActualDonatePercent() const
{
    uint64_t total = m_donatedShares + m_userShares;
    if (total == 0) {
        return 0.0;
    }
    
    return (100.0 * m_donatedShares) / total;
}

void DonationController::onShareSubmitted(bool isDonation)
{
    if (isDonation) {
        m_donatedShares++;
    } else {
        m_userShares++;
    }
}

void DonationController::updateDonationState()
{
    std::lock_guard<std::mutex> lock(m_configMutex);
    
    if (m_config.donateLevel == 0) {
        // 无捐赠
        if (m_isDonating) {
            m_isDonating = false;
            LOG_INFO("Donation disabled");
        }
        return;
    }
    
    if (m_config.donateLevel == 100) {
        // 100%捐赠
        if (!m_isDonating) {
            m_isDonating = true;
            LOG_INFO("100%% donation enabled");
        }
        return;
    }
    
    uint64_t now = currentTimeMs();
    uint64_t elapsed = now - m_lastSwitchTime;
    
    // 计算一个周期内的捐赠时间和用户时间
    uint64_t donateTimePerCycle = (CYCLE_TIME * m_config.donateLevel) / 100;
    uint64_t userTimePerCycle = CYCLE_TIME - donateTimePerCycle;
    
    if (m_isDonating) {
        // 当前在捐赠模式
        m_donationTime += elapsed;
        
        // 检查是否应该切换到用户模式
        if (m_donationTime >= donateTimePerCycle) {
            m_isDonating = false;
            m_lastSwitchTime = now;
            m_donationTime = 0;
            LOG_INFO("Switching to user mining (donated %.1f%% this cycle)", 
                     getActualDonatePercent());
        }
    } else {
        // 当前在用户模式
        m_userTime += elapsed;
        
        // 检查是否应该切换到捐赠模式
        if (m_userTime >= userTimePerCycle) {
            m_isDonating = true;
            m_lastSwitchTime = now;
            m_userTime = 0;
            LOG_INFO("Switching to donation mining (%u%% configured)", 
                     m_config.donateLevel);
        }
    }
}

uint64_t DonationController::currentTimeMs() const
{
    using namespace std::chrono;
    return duration_cast<milliseconds>(steady_clock::now().time_since_epoch()).count();
}

} // namespace xmrig