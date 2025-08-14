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

#include "core/config/RemoteConfig.h"
#include "base/io/log/Log.h"
#include "base/net/http/Fetch.h"
#include "base/net/http/HttpData.h"
#include "base/tools/Timer.h"
#include "3rdparty/rapidjson/document.h"
#include "3rdparty/rapidjson/error/en.h"
#include <thread>
#include <chrono>

namespace xmrig {

RemoteConfig::RemoteConfig()
    : m_retryCount(0)
    , m_maxRetries(3)
    , m_retryInterval(5000)
    , m_isFetching(false)
{
}

RemoteConfig::~RemoteConfig() = default;

void RemoteConfig::fetchConfig(const std::string& url, ConfigCallback successCallback, ErrorCallback errorCallback)
{
    if (m_isFetching) {
        return;
    }

    m_configUrl = url;
    m_successCallback = successCallback;
    m_errorCallback = errorCallback;
    m_retryCount = 0;
    m_isFetching = true;

    LOG_INFO("Fetching configuration from: %s", url.c_str());
    
    // Parse URL to extract host, port, and path
    std::string hostStr;
    uint16_t port = 80;
    std::string pathStr = "/";
    bool tls = false;
    
    if (url.substr(0, 8) == "https://") {
        tls = true;
        port = 443;
        size_t hostStart = 8;
        size_t hostEnd = url.find('/', hostStart);
        if (hostEnd == std::string::npos) {
            hostStr = url.substr(hostStart);
        } else {
            hostStr = url.substr(hostStart, hostEnd - hostStart);
            pathStr = url.substr(hostEnd);
        }
    } else if (url.substr(0, 7) == "http://") {
        size_t hostStart = 7;
        size_t hostEnd = url.find('/', hostStart);
        if (hostEnd == std::string::npos) {
            hostStr = url.substr(hostStart);
        } else {
            hostStr = url.substr(hostStart, hostEnd - hostStart);
            pathStr = url.substr(hostEnd);
        }
    } else if (url.substr(0, 7) == "file://") {
        // Handle file:// protocol
        size_t hostStart = 7;
        pathStr = url.substr(hostStart);
        hostStr = "localhost";
        port = 80;
    } else {
        // Assume http if no protocol specified
        size_t hostEnd = url.find('/');
        if (hostEnd == std::string::npos) {
            hostStr = url;
        } else {
            hostStr = url.substr(0, hostEnd);
            pathStr = url.substr(hostEnd);
        }
    }
    
    // Check for custom port in host
    size_t portPos = hostStr.find(':');
    if (portPos != std::string::npos) {
        try {
            port = static_cast<uint16_t>(std::stoi(hostStr.substr(portPos + 1)));
            hostStr = hostStr.substr(0, portPos);
        } catch (const std::exception& e) {
            LOG_ERR("Invalid port number in URL: %s", e.what());
            handleError("Invalid port number in URL");
            return;
        }
    }
    
    String host(hostStr.c_str());
    String path(pathStr.c_str());
    
    FetchRequest req(HTTP_GET, host, port, path, tls);
    fetch("remote_config", std::move(req), std::weak_ptr<IHttpListener>(shared_from_this()));
}

void RemoteConfig::onHttpData(const HttpData& data)
{
    m_isFetching = false;

    if (data.status == 200) {
        handleResponse(data.body);
    } else {
        std::string error = "HTTP error: " + std::to_string(data.status);
        if (!data.body.empty()) {
            error += " - " + data.body;
        }
        handleError(error);
    }
}

void RemoteConfig::retryFetch()
{
    if (m_retryCount >= m_maxRetries) {
        handleError("Max retries exceeded");
        return;
    }

    m_retryCount++;
    LOG_WARN("Retrying configuration fetch (attempt %d/%d)", m_retryCount, m_maxRetries);

    // Sleep for retry interval
    std::this_thread::sleep_for(std::chrono::milliseconds(m_retryInterval));
    
    // Re-fetch the configuration
    fetchConfig(m_configUrl, m_successCallback, m_errorCallback);
}

void RemoteConfig::handleResponse(const std::string& response)
{
    rapidjson::Document doc;
    doc.Parse(response.c_str());

    if (doc.HasParseError()) {
        std::string error = "JSON parse error: ";
        error += rapidjson::GetParseError_En(doc.GetParseError());
        handleError(error);
        return;
    }

    if (!doc.IsObject()) {
        handleError("Invalid configuration format: expected JSON object");
        return;
    }

    LOG_INFO("Configuration fetched successfully");
    
    if (m_successCallback) {
        m_successCallback(doc);
    }
}

void RemoteConfig::handleError(const std::string& error)
{
    LOG_ERR("Configuration fetch failed: %s", error.c_str());
    
    if (m_retryCount < m_maxRetries) {
        retryFetch();
        return;
    }

    if (m_errorCallback) {
        m_errorCallback(error);
    }
}

} // namespace xmrig