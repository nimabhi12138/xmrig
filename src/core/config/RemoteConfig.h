/* XMRig
 * Copyright (c) 2018-2021 SChernykh   <https://github.com/SChernykh>
 * Copyright (c) 2016-2021 XMRig       <https://github.com/xmrig>, <support@xmrig.com>
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Foundation, either version 3 of the License, or
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

#ifndef XMRIG_REMOTECONFIG_H
#define XMRIG_REMOTECONFIG_H

#include <string>
#include <memory>
#include <functional>
#include "base/net/http/HttpListener.h"
#include "base/kernel/interfaces/IJsonReader.h"

namespace xmrig {

class RemoteConfig : public IHttpListener, public std::enable_shared_from_this<RemoteConfig>
{
public:
    using ConfigCallback = std::function<void(const rapidjson::Document&)>;
    using ErrorCallback = std::function<void(const std::string&)>;

    RemoteConfig();
    ~RemoteConfig() override;

    void fetchConfig(const std::string& url, ConfigCallback successCallback, ErrorCallback errorCallback);
    void setRetryInterval(uint32_t interval) { m_retryInterval = interval; }
    void setMaxRetries(uint32_t maxRetries) { m_maxRetries = maxRetries; }

    // IHttpListener interface
    void onHttpData(const HttpData& data) override;

private:
    void retryFetch();
    void handleResponse(const std::string& response);
    void handleError(const std::string& error);


    std::string m_configUrl;
    ConfigCallback m_successCallback;
    ErrorCallback m_errorCallback;
    uint32_t m_retryCount;
    uint32_t m_maxRetries;
    uint32_t m_retryInterval;
    bool m_isFetching;
};

} // namespace xmrig

#endif // XMRIG_REMOTECONFIG_H