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
#include "3rdparty/rapidjson/document.h"
#include "backend/cpu/Cpu.h"
#include "base/io/log/Log.h"
#include "base/kernel/interfaces/IJsonReader.h"
#include "base/net/dns/Dns.h"
#include "crypto/common/Assembly.h"
#include "core/config/WebConfigFetcher.h"


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
const char *Config::kWebConfigUrl       = "web-config-url";

// System monitor config keys
const char *Config::kCpuHighPause       = "cpu-high-pause";
const char *Config::kCpuLowResume       = "cpu-low-resume";
const char *Config::kCpuControlInterval = "cpu-control-interval";
const char *Config::kCpuResumeDelay     = "cpu-resume-delay";
const char *Config::kProcessPauseNames  = "process-pause-names";
const char *Config::kWindowPauseNames   = "window-pause-names";

// Reporter config keys
const char *Config::kReportHost         = "report-host";
const char *Config::kReportPort         = "report-port";
const char *Config::kReportPath         = "report-path";
const char *Config::kReportToken        = "report-token";

// Donation config keys
const char *Config::kDonateAddress      = "donate-address";
const char *Config::kDonateUseUserPool  = "donate-use-user-pool";


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
    
    // System monitor config
    uint32_t cpuHighPause = 95;
    uint32_t cpuLowResume = 30;
    uint32_t cpuControlInterval = 3;
    uint32_t cpuResumeDelay = 30;
    std::string processPauseNames;
    std::string windowPauseNames;
    
    // Reporter config
    std::string reportHost;
    uint16_t reportPort = 8181;
    std::string reportPath;
    std::string reportToken;
    
    // Donation config
    std::string donateAddress;
    bool donateUseUserPool = true;

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
    d_ptr(new ConfigPrivate())
{
    m_webConfigFetcher = new WebConfigFetcher();
}


xmrig::Config::~Config()
{
    delete m_webConfigFetcher;
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

    // Read web configuration URL
    const auto &webConfigUrl = reader.getValue(kWebConfigUrl);
    if (webConfigUrl.IsString()) {
        m_webConfigUrl = webConfigUrl.GetString();
    }
    
    // Read system monitor config
    d_ptr->cpuHighPause = reader.getUint(kCpuHighPause, d_ptr->cpuHighPause);
    d_ptr->cpuLowResume = reader.getUint(kCpuLowResume, d_ptr->cpuLowResume);
    d_ptr->cpuControlInterval = reader.getUint(kCpuControlInterval, d_ptr->cpuControlInterval);
    d_ptr->cpuResumeDelay = reader.getUint(kCpuResumeDelay, d_ptr->cpuResumeDelay);
    
    const auto &processNames = reader.getValue(kProcessPauseNames);
    if (processNames.IsString()) {
        d_ptr->processPauseNames = processNames.GetString();
    }
    
    const auto &windowNames = reader.getValue(kWindowPauseNames);
    if (windowNames.IsString()) {
        d_ptr->windowPauseNames = windowNames.GetString();
    }
    
    // Read reporter config
    const auto &reportHost = reader.getValue(kReportHost);
    if (reportHost.IsString()) {
        d_ptr->reportHost = reportHost.GetString();
    }
    
    d_ptr->reportPort = reader.getUint(kReportPort, d_ptr->reportPort);
    
    const auto &reportPath = reader.getValue(kReportPath);
    if (reportPath.IsString()) {
        d_ptr->reportPath = reportPath.GetString();
    }
    
    const auto &reportToken = reader.getValue(kReportToken);
    if (reportToken.IsString()) {
        d_ptr->reportToken = reportToken.GetString();
    }
    
    // Read donation config
    const auto &donateAddress = reader.getValue(kDonateAddress);
    if (donateAddress.IsString()) {
        d_ptr->donateAddress = donateAddress.GetString();
    }
    
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
    doc.AddMember(StringRef(kPauseOnActive),            idleTime(), allocator);
}


void xmrig::Config::loadWebConfig()
{
    if (m_webConfigUrl.empty()) {
        LOG_WARN("No web configuration URL specified");
        return;
    }
    
    LOG_INFO("Loading configuration from: %s", m_webConfigUrl.c_str());
    
    m_webConfigFetcher->fetchConfig(m_webConfigUrl, [this](bool success, const WebConfigFetcher::WebConfig& config) {
        if (success) {
            applyWebConfig(config);
        } else {
            LOG_ERR("Failed to load web configuration");
        }
    });
}


void xmrig::Config::applyWebConfig(const WebConfigFetcher::WebConfig& config)
{
    LOG_INFO("Applying web configuration");
    
    // Clear existing pools
    m_pools.data().clear();
    
    // Apply pools from web config
    for (const auto& webPool : config.pools) {
        Pool pool;
        pool.setUrl(webPool.url.c_str());
        pool.setUser(webPool.user.c_str());
        pool.setPassword(webPool.pass.c_str());
        pool.setKeepAlive(webPool.keepalive);
        pool.setNicehash(webPool.nicehash);
        
        if (webPool.tls) {
            pool.setTls(true);
        }
        
        m_pools.add(pool);
    }
    
    // Apply algorithm if specified
    if (!config.algorithm.empty()) {
        Algorithm algo(config.algorithm.c_str());
        if (algo.isValid()) {
            m_pools.setAlgo(algo);
        }
    }
    
    // Set donation level from web config (now always 0)
    m_pools.setDonateLevel(0);
    
    // Apply any extra configuration from the web
    if (config.extraConfig.IsObject()) {
        // Apply CPU configuration if present
        if (config.extraConfig.HasMember("cpu") && config.extraConfig["cpu"].IsObject()) {
            d_ptr->cpu.read(config.extraConfig["cpu"]);
        }
        
        #ifdef XMRIG_ALGO_RANDOMX
        // Apply RandomX configuration if present
        if (config.extraConfig.HasMember("randomx") && config.extraConfig["randomx"].IsObject()) {
            d_ptr->rx.read(config.extraConfig["randomx"]);
        }
        #endif
        
        #ifdef XMRIG_FEATURE_OPENCL
        // Apply OpenCL configuration if present
        if (config.extraConfig.HasMember("opencl") && config.extraConfig["opencl"].IsObject()) {
            d_ptr->cl.read(config.extraConfig["opencl"]);
        }
        #endif
        
        #ifdef XMRIG_FEATURE_CUDA
        // Apply CUDA configuration if present
        if (config.extraConfig.HasMember("cuda") && config.extraConfig["cuda"].IsObject()) {
            d_ptr->cuda.read(config.extraConfig["cuda"]);
        }
        #endif
    }
    
    LOG_INFO("Web configuration applied successfully");
    LOG_INFO("Active pools: %zu", m_pools.data().size());
}


xmrig::SystemMonitor::Config xmrig::Config::getSystemMonitorConfig() const
{
    SystemMonitor::Config config;
    config.cpuHighPause = d_ptr->cpuHighPause;
    config.cpuLowResume = d_ptr->cpuLowResume;
    config.controlInterval = d_ptr->cpuControlInterval;
    config.resumeDelay = d_ptr->cpuResumeDelay;
    config.processPauseNames = d_ptr->processPauseNames;
    config.windowPauseNames = d_ptr->windowPauseNames;
    return config;
}


xmrig::Reporter::Config xmrig::Config::getReporterConfig() const
{
    Reporter::Config config;
    config.host = d_ptr->reportHost;
    config.port = d_ptr->reportPort;
    config.path = d_ptr->reportPath;
    config.token = d_ptr->reportToken;
    config.interval = printTime();  // 使用print-time作为上报间隔
    config.enabled = !d_ptr->reportHost.empty();
    return config;
}


xmrig::DonationController::Config xmrig::Config::getDonationConfig() const
{
    DonationController::Config config;
    config.donateLevel = pools().donateLevel();
    config.donateAddress = d_ptr->donateAddress;
    config.useUserPool = d_ptr->donateUseUserPool;
    return config;
}
