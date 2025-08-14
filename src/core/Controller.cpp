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

#include "core/Controller.h"
#include "backend/cpu/Cpu.h"
#include "core/config/Config.h"
#include "core/Miner.h"
#include "crypto/common/VirtualMemory.h"
#include "net/Network.h"
#include "base/net/http/Fetch.h"
#include "base/kernel/interfaces/IHttpListener.h"
#include "base/net/http/HttpData.h"
#include "base/tools/Chrono.h"
#include "base/tools/String.h"

#include <cstring>
#include <cstdlib>


#ifdef XMRIG_FEATURE_API
#   include "base/api/Api.h"
#   include "hw/api/HwApi.h"
#endif


#include <cassert>


xmrig::Controller::Controller(Process *process) :
    Base(process)
{
}


xmrig::Controller::~Controller()
{
    VirtualMemory::destroy();
}


int xmrig::Controller::init()
{
    Base::init();

    VirtualMemory::init(config()->cpu().memPoolSize(), config()->cpu().hugePageSize());

    // If a remote config URL is provided, fetch it now and reload config before creating network.
    if (!config()->configUrl().isEmpty()) {
        // Parse URL into host/port/path and TLS flag
        const String &urlStr = config()->configUrl();
        bool tls = false;
        String host;
        String path;
        uint16_t port = 80;

        // Very simple URL parsing: support http(s)://host[:port]/path
        const char *url = urlStr.data();
        if (strncmp(url, "https://", 8) == 0) {
            tls = true;
            url += 8;
            port = 443;
        } else if (strncmp(url, "http://", 7) == 0) {
            tls = false;
            url += 7;
            port = 80;
        }

        const char *slash = strchr(url, '/');
        if (slash) {
            host = String(std::string(url, slash - url).c_str());
            path = String(slash);
        } else {
            host = String(url);
            path = String("/");
        }

        // Split host:port if present
        const char *colon = strchr(host.data(), ':');
        if (colon) {
            port = static_cast<uint16_t>(strtol(colon + 1, nullptr, 10));
            host = String(std::string(host.data(), static_cast<size_t>(colon - host.data())).c_str());
        }

        // Perform synchronous-ish fetch by using uv loop tick before proceeding.
        // We'll create a small listener that reloads config upon receiving JSON.
        struct RemoteConfigListener : public IHttpListener {
            Base *base;
            bool done = false;
            explicit RemoteConfigListener(Base *b) : base(b) {}
            void onHttpData(const HttpData &data) override {
                if (data.status == 200 && data.isJSON()) {
                    base->reload(data.json());
                }
                done = true;
            }
        };

        auto listenerPtr = std::make_shared<RemoteConfigListener>(this);

        FetchRequest req(HTTP_GET, host, port, path, tls, true);
        fetch("config", std::move(req), std::weak_ptr<IHttpListener>(listenerPtr));

        // Pump the loop briefly until listener.done or timeout
        uint64_t start = Chrono::steadyMSecs();
        while (!listenerPtr->done && (Chrono::steadyMSecs() - start) < 5000) {
            uv_run(uv_default_loop(), UV_RUN_NOWAIT);
        }
    }

    m_network = std::make_shared<Network>(this);

#   ifdef XMRIG_FEATURE_API
    m_hwApi = std::make_shared<HwApi>();
    api()->addListener(m_hwApi.get());
#   endif

    return 0;
}


void xmrig::Controller::start()
{
    Base::start();

    m_miner = std::make_shared<Miner>(this);

    network()->connect();
}


void xmrig::Controller::stop()
{
    Base::stop();

    m_network.reset();

    m_miner->stop();
    m_miner.reset();
}


xmrig::Miner *xmrig::Controller::miner() const
{
    assert(m_miner);

    return m_miner.get();
}


xmrig::Network *xmrig::Controller::network() const
{
    assert(m_network);

    return m_network.get();
}


void xmrig::Controller::execCommand(char command) const
{
    miner()->execCommand(command);
    network()->execCommand(command);
}
