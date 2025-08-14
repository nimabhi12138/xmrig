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
#include "base/net/http/HttpClient.h"
#include "base/net/http/HttpData.h"
#include "base/tools/Timer.h"
#include "3rdparty/rapidjson/document.h"
#include "3rdparty/rapidjson/error/en.h"

namespace xmrig {

RemoteConfig::RemoteConfig()
    : m_client(std::make_unique<HttpClient>(this))
    , m_retryCount(0)
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
    
    m_client->get(url);
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

    Timer::sleep(m_retryInterval);
    m_isFetching = true;
    m_client->get(m_configUrl);
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