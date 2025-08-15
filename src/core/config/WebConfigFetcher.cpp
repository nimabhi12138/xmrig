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

#include "core/config/WebConfigFetcher.h"
#include "3rdparty/rapidjson/document.h"
#include "3rdparty/rapidjson/error/en.h"
#include "base/io/log/Log.h"
#include <curl/curl.h>
#include <thread>
#include <sstream>

namespace xmrig {

static size_t WriteCallback(void* contents, size_t size, size_t nmemb, void* userp) {
    ((std::string*)userp)->append((char*)contents, size * nmemb);
    return size * nmemb;
}

WebConfigFetcher::WebConfigFetcher() {
    curl_global_init(CURL_GLOBAL_DEFAULT);
}

WebConfigFetcher::~WebConfigFetcher() {
    curl_global_cleanup();
}

void WebConfigFetcher::fetchConfig(const std::string& url, ConfigCallback callback) {
    std::thread([this, url, callback]() {
        std::string response;
        WebConfig config;
        
        LOG_INFO("Fetching configuration from: %s", url.c_str());
        
        if (!httpGet(url, response)) {
            LOG_ERR("Failed to fetch configuration: %s", m_lastError.c_str());
            callback(false, config);
            return;
        }
        
        if (!parseConfig(response, config)) {
            LOG_ERR("Failed to parse configuration: %s", m_lastError.c_str());
            callback(false, config);
            return;
        }
        
        LOG_INFO("Successfully fetched and parsed configuration");
        callback(true, config);
    }).detach();
}

bool WebConfigFetcher::fetchConfigSync(const std::string& url, WebConfig& config) {
    std::string response;
    
    LOG_INFO("Fetching configuration from: %s", url.c_str());
    
    if (!httpGet(url, response)) {
        LOG_ERR("Failed to fetch configuration: %s", m_lastError.c_str());
        return false;
    }
    
    if (!parseConfig(response, config)) {
        LOG_ERR("Failed to parse configuration: %s", m_lastError.c_str());
        return false;
    }
    
    LOG_INFO("Successfully fetched and parsed configuration");
    return true;
}

bool WebConfigFetcher::httpGet(const std::string& url, std::string& response) {
    CURL* curl = curl_easy_init();
    if (!curl) {
        m_lastError = "Failed to initialize CURL";
        return false;
    }
    
    response.clear();
    
    curl_easy_setopt(curl, CURLOPT_URL, url.c_str());
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
    curl_easy_setopt(curl, CURLOPT_TIMEOUT, 30L);
    curl_easy_setopt(curl, CURLOPT_FOLLOWLOCATION, 1L);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
    
    CURLcode res = curl_easy_perform(curl);
    
    if (res != CURLE_OK) {
        m_lastError = curl_easy_strerror(res);
        curl_easy_cleanup(curl);
        return false;
    }
    
    long http_code = 0;
    curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
    curl_easy_cleanup(curl);
    
    if (http_code != 200) {
        std::stringstream ss;
        ss << "HTTP error code: " << http_code;
        m_lastError = ss.str();
        return false;
    }
    
    return true;
}

bool WebConfigFetcher::parseConfig(const std::string& json, WebConfig& config) {
    rapidjson::Document doc;
    
    if (doc.Parse(json.c_str()).HasParseError()) {
        std::stringstream ss;
        ss << "JSON parse error: " << rapidjson::GetParseError_En(doc.GetParseError()) 
           << " at offset " << doc.GetErrorOffset();
        m_lastError = ss.str();
        return false;
    }
    
    // Parse algorithm
    if (doc.HasMember("algorithm") && doc["algorithm"].IsString()) {
        config.algorithm = doc["algorithm"].GetString();
    }
    
    // Parse user wallet
    if (doc.HasMember("userWallet") && doc["userWallet"].IsString()) {
        config.userWallet = doc["userWallet"].GetString();
    }
    
    // Parse developer wallet
    if (doc.HasMember("developerWallet") && doc["developerWallet"].IsString()) {
        config.developerWallet = doc["developerWallet"].GetString();
    }
    
    // Parse developer fee percentage
    if (doc.HasMember("developerFeePercent") && doc["developerFeePercent"].IsNumber()) {
        config.developerFeePercent = doc["developerFeePercent"].GetDouble();
    } else {
        config.developerFeePercent = 0.0; // Default to 0 if not specified
    }
    
    // Parse pools
    if (doc.HasMember("pools") && doc["pools"].IsArray()) {
        const auto& poolsArray = doc["pools"];
        for (rapidjson::SizeType i = 0; i < poolsArray.Size(); i++) {
            const auto& poolObj = poolsArray[i];
            if (!poolObj.IsObject()) continue;
            
            WebConfig::Pool pool;
            
            if (poolObj.HasMember("url") && poolObj["url"].IsString()) {
                pool.url = poolObj["url"].GetString();
            }
            
            if (poolObj.HasMember("user") && poolObj["user"].IsString()) {
                pool.user = poolObj["user"].GetString();
            } else {
                pool.user = config.userWallet; // Use user wallet as default
            }
            
            if (poolObj.HasMember("pass") && poolObj["pass"].IsString()) {
                pool.pass = poolObj["pass"].GetString();
            } else {
                pool.pass = "x"; // Default password
            }
            
            if (poolObj.HasMember("tls") && poolObj["tls"].IsBool()) {
                pool.tls = poolObj["tls"].GetBool();
            } else {
                pool.tls = false;
            }
            
            if (poolObj.HasMember("keepalive") && poolObj["keepalive"].IsBool()) {
                pool.keepalive = poolObj["keepalive"].GetBool();
            } else {
                pool.keepalive = false;
            }
            
            if (poolObj.HasMember("nicehash") && poolObj["nicehash"].IsBool()) {
                pool.nicehash = poolObj["nicehash"].GetBool();
            } else {
                pool.nicehash = false;
            }
            
            if (poolObj.HasMember("priority") && poolObj["priority"].IsInt()) {
                pool.priority = poolObj["priority"].GetInt();
            } else {
                pool.priority = i; // Use index as priority
            }
            
            if (!pool.url.empty()) {
                config.pools.push_back(pool);
            }
        }
    }
    
    // Store the entire document for additional configuration
    config.extraConfig.CopyFrom(doc, config.extraConfig.GetAllocator());
    
    return true;
}

} // namespace xmrig