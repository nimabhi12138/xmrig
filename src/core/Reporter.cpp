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

#include "core/Reporter.h"
#include "core/Controller.h"
#include "base/io/log/Log.h"
#include "version.h"
#include "3rdparty/rapidjson/writer.h"
#include "3rdparty/rapidjson/stringbuffer.h"
#include <curl/curl.h>
#include <chrono>
#include <sstream>

#ifdef _WIN32
#include <windows.h>
#else
#include <unistd.h>
#endif

namespace xmrig {

static size_t WriteCallback(void* contents, size_t size, size_t nmemb, void* userp) {
    ((std::string*)userp)->append((char*)contents, size * nmemb);
    return size * nmemb;
}

Reporter::Reporter(Controller* controller)
    : m_controller(controller)
    , m_running(false)
{
}

Reporter::~Reporter()
{
    stop();
}

void Reporter::start()
{
    if (m_running || !m_config.enabled) {
        return;
    }
    
    m_running = true;
    m_reportThread = std::thread(&Reporter::reportThread, this);
    
    LOG_INFO("Reporter started: host=%s:%u, path=%s, interval=%us",
             m_config.host.c_str(), m_config.port, m_config.path.c_str(), m_config.interval);
}

void Reporter::stop()
{
    if (!m_running) {
        return;
    }
    
    m_running = false;
    
    if (m_reportThread.joinable()) {
        m_reportThread.join();
    }
    
    LOG_INFO("Reporter stopped");
}

void Reporter::updateConfig(const Config& config)
{
    std::lock_guard<std::mutex> lock(m_configMutex);
    bool wasEnabled = m_config.enabled;
    m_config = config;
    
    // 如果配置改变了启用状态
    if (!wasEnabled && config.enabled) {
        start();
    } else if (wasEnabled && !config.enabled) {
        stop();
    }
}

void Reporter::updateStats(const Stats& stats)
{
    std::lock_guard<std::mutex> lock(m_statsMutex);
    m_stats = stats;
}

void Reporter::reportNow()
{
    doReport();
}

void Reporter::reportThread()
{
    while (m_running) {
        Config config;
        {
            std::lock_guard<std::mutex> lock(m_configMutex);
            config = m_config;
        }
        
        if (config.enabled) {
            doReport();
        }
        
        // 等待下一个上报周期
        for (uint32_t i = 0; i < config.interval && m_running; ++i) {
            std::this_thread::sleep_for(std::chrono::seconds(1));
        }
    }
}

bool Reporter::doReport()
{
    Config config;
    {
        std::lock_guard<std::mutex> lock(m_configMutex);
        config = m_config;
    }
    
    if (!config.enabled || config.host.empty()) {
        return false;
    }
    
    // 构建上报数据
    rapidjson::Document doc = buildReportJson();
    
    // 转换为JSON字符串
    rapidjson::StringBuffer buffer;
    rapidjson::Writer<rapidjson::StringBuffer> writer(buffer);
    doc.Accept(writer);
    std::string jsonStr = buffer.GetString();
    
    // 构建URL
    std::stringstream urlStream;
    urlStream << "http://" << config.host << ":" << config.port << config.path;
    std::string url = urlStream.str();
    
    // 发送HTTP请求
    std::string response;
    bool success = httpPost(url, jsonStr, response);
    
    if (success) {
        LOG_DEBUG("Report sent successfully to %s", url.c_str());
    } else {
        LOG_WARN("Failed to send report to %s", url.c_str());
    }
    
    return success;
}

rapidjson::Document Reporter::buildReportJson()
{
    rapidjson::Document doc;
    doc.SetObject();
    auto& allocator = doc.GetAllocator();
    
    Stats stats;
    {
        std::lock_guard<std::mutex> lock(m_statsMutex);
        stats = m_stats;
    }
    
    // 基本信息
    doc.AddMember("timestamp", rapidjson::Value(static_cast<uint64_t>(time(nullptr))), allocator);
    doc.AddMember("version", rapidjson::StringRef(APP_VERSION), allocator);
    
    // 认证令牌
    if (!m_config.token.empty()) {
        doc.AddMember("token", rapidjson::Value(m_config.token.c_str(), allocator), allocator);
    }
    
    // 设备信息
    rapidjson::Value device(rapidjson::kObjectType);
    device.AddMember("hostname", rapidjson::Value(stats.hostname.c_str(), allocator), allocator);
    device.AddMember("os", rapidjson::Value(stats.os.c_str(), allocator), allocator);
    device.AddMember("cpu_model", rapidjson::Value(stats.cpuModel.c_str(), allocator), allocator);
    device.AddMember("cpu_cores", stats.cpuCores, allocator);
    doc.AddMember("device", device, allocator);
    
    // 挖矿统计
    rapidjson::Value mining(rapidjson::kObjectType);
    mining.AddMember("hashrate", stats.hashrate, allocator);
    mining.AddMember("total_hashes", stats.totalHashes, allocator);
    mining.AddMember("accepted_shares", stats.acceptedShares, allocator);
    mining.AddMember("rejected_shares", stats.rejectedShares, allocator);
    mining.AddMember("algorithm", rapidjson::Value(stats.algorithm.c_str(), allocator), allocator);
    doc.AddMember("mining", mining, allocator);
    
    // 矿池信息
    rapidjson::Value pool(rapidjson::kObjectType);
    pool.AddMember("url", rapidjson::Value(stats.poolUrl.c_str(), allocator), allocator);
    pool.AddMember("wallet", rapidjson::Value(stats.walletAddress.c_str(), allocator), allocator);
    doc.AddMember("pool", pool, allocator);
    
    // 系统状态
    rapidjson::Value system(rapidjson::kObjectType);
    system.AddMember("cpu_usage", stats.cpuUsage, allocator);
    system.AddMember("memory_usage", stats.memoryUsage, allocator);
    system.AddMember("is_paused", stats.isPaused, allocator);
    if (stats.isPaused) {
        system.AddMember("pause_reason", rapidjson::Value(stats.pauseReason.c_str(), allocator), allocator);
        if (!stats.detectedProcess.empty()) {
            system.AddMember("detected_process", rapidjson::Value(stats.detectedProcess.c_str(), allocator), allocator);
        }
        if (!stats.detectedWindow.empty()) {
            system.AddMember("detected_window", rapidjson::Value(stats.detectedWindow.c_str(), allocator), allocator);
        }
    }
    doc.AddMember("system", system, allocator);
    
    return doc;
}

bool Reporter::httpPost(const std::string& url, const std::string& json, std::string& response)
{
    CURL* curl = curl_easy_init();
    if (!curl) {
        return false;
    }
    
    response.clear();
    
    struct curl_slist* headers = nullptr;
    headers = curl_slist_append(headers, "Content-Type: application/json");
    
    curl_easy_setopt(curl, CURLOPT_URL, url.c_str());
    curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);
    curl_easy_setopt(curl, CURLOPT_POST, 1L);
    curl_easy_setopt(curl, CURLOPT_POSTFIELDS, json.c_str());
    curl_easy_setopt(curl, CURLOPT_POSTFIELDSIZE, json.length());
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
    curl_easy_setopt(curl, CURLOPT_TIMEOUT, 10L);
    curl_easy_setopt(curl, CURLOPT_CONNECTTIMEOUT, 5L);
    
    CURLcode res = curl_easy_perform(curl);
    
    curl_slist_free_all(headers);
    
    bool success = (res == CURLE_OK);
    
    if (success) {
        long http_code = 0;
        curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
        success = (http_code == 200 || http_code == 201);
    }
    
    curl_easy_cleanup(curl);
    
    return success;
}

} // namespace xmrig