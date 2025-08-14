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

#include <algorithm>
#include <cinttypes>
#include <cstring>
#include <uv.h>


#include "core/config/Config.h"
#include "core/config/RemoteConfig.h"
#include "3rdparty/rapidjson/document.h"
#include "backend/cpu/Cpu.h"
#include "base/io/json/Json.h"
#include "base/io/log/Log.h"
#include "base/kernel/interfaces/IJsonReader.h"
#include "base/net/dns/Dns.h"
#include "crypto/common/Assembly.h"


#ifdef XMRIG_ALGO_RANDOMX
#   include "crypto/rx/RxConfig.h"
#endif


#ifdef XMRIG_FEATURE_OPENCL
#   include "backend/opencl/OclConfig.h"
#endif


#ifdef XMRIG_FEATURE_CUDA
#   include "backend/cuda/CudaConfig.h"
#endif


namespace xmrig {


constexpr static uint32_t kIdleTime     = 60U;


const char *Config::kPauseOnBattery     = "pause-on-battery";
const char *Config::kPauseOnActive      = "pause-on-active";

// 新增监控配置常量
const char *Config::kCpuHighPause        = "cpu-high-pause";
const char *Config::kCpuLowResume        = "cpu-low-resume";
const char *Config::kCpuControlInterval  = "cpu-control-interval";
const char *Config::kCpuResumeDelay      = "cpu-resume-delay";
const char *Config::kProcessPauseNames   = "process-pause-names";
const char *Config::kWindowPauseNames    = "window-pause-names";
const char *Config::kReportHost          = "report-host";
const char *Config::kReportPort          = "report-port";
const char *Config::kReportPath          = "report-path";
const char *Config::kReportToken         = "report-token";
const char *Config::kDonateAddress       = "donate-address";
const char *Config::kDonateUseUserPool   = "donate-use-user-pool";


#ifdef XMRIG_FEATURE_OPENCL
const char *Config::kOcl                = "opencl";
#endif

#ifdef XMRIG_FEATURE_CUDA
const char *Config::kCuda               = "cuda";
#endif

#if defined(XMRIG_FEATURE_NVML) || defined (XMRIG_FEATURE_ADL)
const char *Config::kHealthPrintTime    = "health-print-time";
#endif

#ifdef XMRIG_FEATURE_DMI
const char *Config::kDMI                = "dmi";
#endif


class ConfigPrivate
{
public:
    bool pauseOnBattery = false;
    CpuConfig cpu;
    uint32_t idleTime   = 0;

    // 新增监控配置成员变量
    uint32_t cpuHighPause = 95;
    uint32_t cpuLowResume = 30;
    uint32_t cpuControlInterval = 3;
    uint32_t cpuResumeDelay = 30;
    String processPauseNames;
    String windowPauseNames;
    String reportHost;
    uint32_t reportPort = 8181;
    String reportPath;
    String reportToken;
    String donateAddress;
    bool donateUseUserPool = true;

#   ifdef XMRIG_ALGO_RANDOMX
    RxConfig rx;
#   endif

#   ifdef XMRIG_FEATURE_OPENCL
    OclConfig cl;
#   endif

#   ifdef XMRIG_FEATURE_CUDA
    CudaConfig cuda;
#   endif

#   if defined(XMRIG_FEATURE_NVML) || defined (XMRIG_FEATURE_ADL)
    uint32_t healthPrintTime = 60U;
#   endif

#   ifdef XMRIG_FEATURE_DMI
    bool dmi = true;
#   endif

    void setIdleTime(const rapidjson::Value &value)
    {
        if (value.IsBool()) {
            idleTime = value.GetBool() ? kIdleTime : 0U;
        }
        else if (value.IsUint()) {
            idleTime = value.GetUint();
        }
    }
};

} // namespace xmrig


xmrig::Config::Config() :
    BaseConfig(),
    m_remoteConfig(std::make_unique<RemoteConfig>())
{
    d_ptr = new ConfigPrivate();
}


xmrig::Config::~Config()
{
    delete d_ptr;
}


bool xmrig::Config::isPauseOnBattery() const
{
    return d_ptr->pauseOnBattery;
}


const xmrig::CpuConfig &xmrig::Config::cpu() const
{
    return d_ptr->cpu;
}


uint32_t xmrig::Config::idleTime() const
{
    return d_ptr->idleTime * 1000U;
}


#ifdef XMRIG_FEATURE_OPENCL
const xmrig::OclConfig &xmrig::Config::cl() const
{
    return d_ptr->cl;
}
#endif


#ifdef XMRIG_FEATURE_CUDA
const xmrig::CudaConfig &xmrig::Config::cuda() const
{
    return d_ptr->cuda;
}
#endif


#ifdef XMRIG_ALGO_RANDOMX
const xmrig::RxConfig &xmrig::Config::rx() const
{
    return d_ptr->rx;
}
#endif


#if defined(XMRIG_FEATURE_NVML) || defined (XMRIG_FEATURE_ADL)
uint32_t xmrig::Config::healthPrintTime() const
{
    return d_ptr->healthPrintTime;
}
#endif


#ifdef XMRIG_FEATURE_DMI
bool xmrig::Config::isDMI() const
{
    return d_ptr->dmi;
}
#endif


bool xmrig::Config::isShouldSave() const
{
    if (!isAutoSave()) {
        return false;
    }

#   ifdef XMRIG_FEATURE_OPENCL
    if (cl().isShouldSave()) {
        return true;
    }
#   endif

#   ifdef XMRIG_FEATURE_CUDA
    if (cuda().isShouldSave()) {
        return true;
    }
#   endif

    return (m_upgrade || cpu().isShouldSave());
}


bool xmrig::Config::read(const IJsonReader &reader, const char *fileName)
{
    if (!BaseConfig::read(reader, fileName)) {
        return false;
    }

    d_ptr->pauseOnBattery = reader.getBool(kPauseOnBattery, d_ptr->pauseOnBattery);
    d_ptr->setIdleTime(reader.getValue(kPauseOnActive));

    d_ptr->cpu.read(reader.getValue(CpuConfig::kField));

#   ifdef XMRIG_ALGO_RANDOMX
    if (!d_ptr->rx.read(reader.getValue(RxConfig::kField))) {
        m_upgrade = true;
    }
#   endif

#   ifdef XMRIG_FEATURE_OPENCL
    if (!pools().isBenchmark()) {
        d_ptr->cl.read(reader.getValue(kOcl));
    }
#   endif

#   ifdef XMRIG_FEATURE_CUDA
    if (!pools().isBenchmark()) {
        d_ptr->cuda.read(reader.getValue(kCuda));
    }
#   endif

#   if defined(XMRIG_FEATURE_NVML) || defined (XMRIG_FEATURE_ADL)
    d_ptr->healthPrintTime = reader.getUint(kHealthPrintTime, d_ptr->healthPrintTime);
#   endif

#   ifdef XMRIG_FEATURE_DMI
    d_ptr->dmi = reader.getBool(kDMI, d_ptr->dmi);
#   endif

    // 读取新增监控配置
    d_ptr->cpuHighPause = reader.getUint(kCpuHighPause, d_ptr->cpuHighPause);
    d_ptr->cpuLowResume = reader.getUint(kCpuLowResume, d_ptr->cpuLowResume);
    d_ptr->cpuControlInterval = reader.getUint(kCpuControlInterval, d_ptr->cpuControlInterval);
    d_ptr->cpuResumeDelay = reader.getUint(kCpuResumeDelay, d_ptr->cpuResumeDelay);
    d_ptr->processPauseNames = reader.getString(kProcessPauseNames, d_ptr->processPauseNames);
    d_ptr->windowPauseNames = reader.getString(kWindowPauseNames, d_ptr->windowPauseNames);
    d_ptr->reportHost = reader.getString(kReportHost, d_ptr->reportHost);
    d_ptr->reportPort = reader.getUint(kReportPort, d_ptr->reportPort);
    d_ptr->reportPath = reader.getString(kReportPath, d_ptr->reportPath);
    d_ptr->reportToken = reader.getString(kReportToken, d_ptr->reportToken);
    d_ptr->donateAddress = reader.getString(kDonateAddress, d_ptr->donateAddress);
    d_ptr->donateUseUserPool = reader.getBool(kDonateUseUserPool, d_ptr->donateUseUserPool);

    return true;
}


void xmrig::Config::getJSON(rapidjson::Document &doc) const
{
    using namespace rapidjson;

    doc.SetObject();

    auto &allocator = doc.GetAllocator();

    Value api(kObjectType);
    api.AddMember(StringRef(kApiId),                    m_apiId.toJSON(), allocator);
    api.AddMember(StringRef(kApiWorkerId),              m_apiWorkerId.toJSON(), allocator);

    doc.AddMember(StringRef(kApi),                      api, allocator);
    doc.AddMember(StringRef(kHttp),                     m_http.toJSON(doc), allocator);
    doc.AddMember(StringRef(kAutosave),                 isAutoSave(), allocator);
    doc.AddMember(StringRef(kBackground),               isBackground(), allocator);
    doc.AddMember(StringRef(kColors),                   Log::isColors(), allocator);
    doc.AddMember(StringRef(kTitle),                    title().toJSON(), allocator);

#   ifdef XMRIG_ALGO_RANDOMX
    doc.AddMember(StringRef(RxConfig::kField),          rx().toJSON(doc), allocator);
#   endif

    doc.AddMember(StringRef(CpuConfig::kField),         cpu().toJSON(doc), allocator);

#   ifdef XMRIG_FEATURE_OPENCL
    doc.AddMember(StringRef(kOcl),                      cl().toJSON(doc), allocator);
#   endif

#   ifdef XMRIG_FEATURE_CUDA
    doc.AddMember(StringRef(kCuda),                     cuda().toJSON(doc), allocator);
#   endif

    doc.AddMember(StringRef(kLogFile),                  m_logFile.toJSON(), allocator);

    m_pools.toJSON(doc, doc);

    doc.AddMember(StringRef(kPrintTime),                printTime(), allocator);
#   if defined(XMRIG_FEATURE_NVML) || defined (XMRIG_FEATURE_ADL)
    doc.AddMember(StringRef(kHealthPrintTime),          healthPrintTime(), allocator);
#   endif

#   ifdef XMRIG_FEATURE_DMI
    doc.AddMember(StringRef(kDMI),                      isDMI(), allocator);
#   endif

    doc.AddMember(StringRef(kSyslog),                   isSyslog(), allocator);

#   ifdef XMRIG_FEATURE_TLS
    doc.AddMember(StringRef(kTls),                      m_tls.toJSON(doc), allocator);
#   endif

    doc.AddMember(StringRef(DnsConfig::kField),         Dns::config().toJSON(doc), allocator);
    doc.AddMember(StringRef(kUserAgent),                m_userAgent.toJSON(), allocator);
    doc.AddMember(StringRef(kVerbose),                  Log::verbose(), allocator);
    doc.AddMember(StringRef(kWatch),                    m_watch, allocator);
    doc.AddMember(StringRef(kPauseOnBattery),           isPauseOnBattery(), allocator);
    doc.AddMember(StringRef(kPauseOnActive),            (d_ptr->idleTime == 0U || d_ptr->idleTime == kIdleTime) ? Value(isPauseOnActive()) : Value(d_ptr->idleTime), allocator);
}

void xmrig::Config::loadRemoteConfig(const std::string& url)
{
    if (url.empty()) {
        LOG_ERR("Remote config URL is empty");
        return;
    }

    m_remoteConfigUrl = url;
    
    m_remoteConfig->fetchConfig(url,
        [this](const rapidjson::Document& doc) {
            // Success callback - apply the remote configuration
            LOG_INFO("Applying remote configuration");
            
            // Create a JSON reader from the document
            JsonReader reader(doc);
            
            // Read the configuration
            if (!read(reader, "remote")) {
                LOG_ERR("Failed to parse remote configuration");
                return;
            }
            
            LOG_INFO("Remote configuration applied successfully");
        },
        [](const std::string& error) {
            // Error callback
            LOG_ERR("Failed to load remote configuration: %s", error.c_str());
        }
    );
}

// 新增监控配置方法实现
uint32_t xmrig::Config::cpuHighPause() const
{
    return d_ptr->cpuHighPause;
}

uint32_t xmrig::Config::cpuLowResume() const
{
    return d_ptr->cpuLowResume;
}

uint32_t xmrig::Config::cpuControlInterval() const
{
    return d_ptr->cpuControlInterval;
}

uint32_t xmrig::Config::cpuResumeDelay() const
{
    return d_ptr->cpuResumeDelay;
}

const std::string& xmrig::Config::processPauseNames() const
{
    return d_ptr->processPauseNames;
}

const std::string& xmrig::Config::windowPauseNames() const
{
    return d_ptr->windowPauseNames;
}

const std::string& xmrig::Config::reportHost() const
{
    return d_ptr->reportHost;
}

uint32_t xmrig::Config::reportPort() const
{
    return d_ptr->reportPort;
}

const std::string& xmrig::Config::reportPath() const
{
    return d_ptr->reportPath;
}

const std::string& xmrig::Config::reportToken() const
{
    return d_ptr->reportToken;
}

const std::string& xmrig::Config::donateAddress() const
{
    return d_ptr->donateAddress;
}

bool xmrig::Config::donateUseUserPool() const
{
    return d_ptr->donateUseUserPool;
}
