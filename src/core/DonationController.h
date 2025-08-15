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

#ifndef XMRIG_DONATIONCONTROLLER_H
#define XMRIG_DONATIONCONTROLLER_H

#include <string>
#include <atomic>
#include <mutex>
#include <chrono>
#include "base/net/stratum/Pool.h"

namespace xmrig {

class Controller;
class IStrategy;
class Job;

class DonationController {
public:
    struct Config {
        uint32_t donateLevel = 0;           // 捐赠比例（0-100）
        std::string donateAddress;          // 捐赠钱包地址
        bool useUserPool = true;            // 使用用户矿池进行捐赠
    };
    
    DonationController(Controller* controller);
    ~DonationController();
    
    // 更新配置
    void updateConfig(const Config& config);
    
    // 检查当前是否应该捐赠
    bool shouldDonate() const;
    
    // 获取当前使用的钱包地址
    std::string getCurrentWallet() const;
    
    // 处理新任务
    void onJob(const Job& job);
    
    // 获取统计信息
    uint64_t getDonatedShares() const { return m_donatedShares; }
    uint64_t getUserShares() const { return m_userShares; }
    double getActualDonatePercent() const;
    
    // 记录份额提交
    void onShareSubmitted(bool isDonation);
    
private:
    // 计算是否切换到捐赠模式
    void updateDonationState();
    
    // 获取当前时间（毫秒）
    uint64_t currentTimeMs() const;
    
    Controller* m_controller;
    Config m_config;
    
    std::atomic<bool> m_isDonating;
    std::atomic<uint64_t> m_donatedShares;
    std::atomic<uint64_t> m_userShares;
    
    std::mutex m_configMutex;
    
    // 时间控制
    uint64_t m_startTime;
    uint64_t m_lastSwitchTime;
    uint64_t m_donationTime;
    uint64_t m_userTime;
    
    // 捐赠周期控制（毫秒）
    static constexpr uint64_t CYCLE_TIME = 100 * 60 * 1000;  // 100分钟一个周期
};

} // namespace xmrig

#endif // XMRIG_DONATIONCONTROLLER_H