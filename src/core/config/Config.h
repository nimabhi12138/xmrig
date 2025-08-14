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

#ifndef XMRIG_CONFIG_H
#define XMRIG_CONFIG_H


#include <cstdint>
#include <string>


#include "3rdparty/rapidjson/fwd.h"
#include "backend/cpu/CpuConfig.h"
#include "base/kernel/config/BaseConfig.h"
#include "base/tools/Object.h"
#include "core/config/WebConfigFetcher.h"
#include "core/SystemMonitor.h"
#include "core/Reporter.h"
#include "core/DonationController.h"


namespace xmrig {


class ConfigPrivate;
class CudaConfig;
class IThread;
class OclConfig;
class RxConfig;


class Config : public BaseConfig
{
public:
    XMRIG_DISABLE_COPY_MOVE(Config);

    static const char *kPauseOnBattery;
    static const char *kPauseOnActive;
    static const char *kWebConfigUrl;
    
    // System monitor config keys
    static const char *kCpuHighPause;
    static const char *kCpuLowResume;
    static const char *kCpuControlInterval;
    static const char *kCpuResumeDelay;
    static const char *kProcessPauseNames;
    static const char *kWindowPauseNames;
    
    // Reporter config keys
    static const char *kReportHost;
    static const char *kReportPort;
    static const char *kReportPath;
    static const char *kReportToken;
    
    // Donation config keys
    static const char *kDonateAddress;
    static const char *kDonateUseUserPool;

#   ifdef XMRIG_FEATURE_OPENCL
    static const char *kOcl;
#   endif

#   ifdef XMRIG_FEATURE_CUDA
    static const char *kCuda;
#   endif

#   if defined(XMRIG_FEATURE_NVML) || defined (XMRIG_FEATURE_ADL)
    static const char *kHealthPrintTime;
#   endif

#   ifdef XMRIG_FEATURE_DMI
    static const char *kDMI;
#   endif

    Config();
    ~Config() override;

    inline bool isPauseOnActive() const { return idleTime() > 0; }

    bool isPauseOnBattery() const;
    const CpuConfig &cpu() const;
    uint32_t idleTime() const;

#   ifdef XMRIG_FEATURE_OPENCL
    const OclConfig &cl() const;
#   endif

#   ifdef XMRIG_FEATURE_CUDA
    const CudaConfig &cuda() const;
#   endif

#   ifdef XMRIG_ALGO_RANDOMX
    const RxConfig &rx() const;
#   endif

#   if defined(XMRIG_FEATURE_NVML) || defined (XMRIG_FEATURE_ADL)
    uint32_t healthPrintTime() const;
#   else
    uint32_t healthPrintTime() const        { return 0; }
#   endif

#   ifdef XMRIG_FEATURE_DMI
    bool isDMI() const;
#   else
    static constexpr inline bool isDMI()    { return false; }
#   endif

    bool isShouldSave() const;
    bool read(const IJsonReader &reader, const char *fileName) override;
    void getJSON(rapidjson::Document &doc) const override;
    
    // Web configuration support
    void setWebConfigUrl(const std::string& url) { m_webConfigUrl = url; }
    void loadWebConfig();
    void applyWebConfig(const WebConfigFetcher::WebConfig& config);
    
    // Get configurations for new modules
    SystemMonitor::Config getSystemMonitorConfig() const;
    Reporter::Config getReporterConfig() const;
    DonationController::Config getDonationConfig() const;

private:
    ConfigPrivate *d_ptr;
    std::string m_webConfigUrl;
    WebConfigFetcher* m_webConfigFetcher = nullptr;
};


} /* namespace xmrig */


#endif /* XMRIG_CONFIG_H */
