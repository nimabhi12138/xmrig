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

#ifndef XMRIG_WEBCONFIGFETCHER_H
#define XMRIG_WEBCONFIGFETCHER_H

#include <string>
#include <functional>
#include "3rdparty/rapidjson/document.h"

namespace xmrig {

class WebConfigFetcher {
public:
    struct WebConfig {
        // Algorithm configuration
        std::string algorithm;
        
        // Wallet addresses
        std::string userWallet;
        std::string developerWallet;
        
        // Developer fee percentage
        double developerFeePercent;
        
        // Pool configuration
        struct Pool {
            std::string url;
            std::string user;
            std::string pass;
            bool tls;
            bool keepalive;
            bool nicehash;
            int priority;
        };
        
        std::vector<Pool> pools;
        
        // Additional configuration
        rapidjson::Document extraConfig;
    };
    
    using ConfigCallback = std::function<void(bool success, const WebConfig& config)>;
    
    WebConfigFetcher();
    ~WebConfigFetcher();
    
    // Fetch configuration from URL
    void fetchConfig(const std::string& url, ConfigCallback callback);
    
    // Parse JSON response into WebConfig
    bool parseConfig(const std::string& json, WebConfig& config);
    
    // Get last error message
    const std::string& getLastError() const { return m_lastError; }
    
private:
    std::string m_lastError;
    
    // HTTP request helper
    bool httpGet(const std::string& url, std::string& response);
};

} // namespace xmrig

#endif // XMRIG_WEBCONFIGFETCHER_H