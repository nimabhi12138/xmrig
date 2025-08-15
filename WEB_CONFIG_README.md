# Web Configuration for XMRig

This modified version of XMRig fetches all configuration from a web URL at runtime, removing all hardcoded donation settings, pools, and algorithms.

## Features

- **No hardcoded donations**: All donation code has been removed
- **Web-based configuration**: Fetch configuration from any HTTP/HTTPS URL
- **Dynamic pool management**: Configure pools from web server
- **Flexible algorithm selection**: Set mining algorithm from web config
- **Developer fee control**: Set developer fee percentage from web (can be 0)

## Configuration

### 1. Local Configuration File

Edit `config.json` and set the `web-config-url` parameter:

```json
{
    "web-config-url": "https://your-server.com/mining-config.json",
    "pools": []
}
```

### 2. Web Configuration Format

Host a JSON file on your web server with the following format:

```json
{
    "algorithm": "rx/0",
    "userWallet": "YOUR_WALLET_ADDRESS",
    "developerWallet": "DEVELOPER_WALLET_ADDRESS",
    "developerFeePercent": 0.0,
    "pools": [
        {
            "url": "pool.example.com:3333",
            "user": "YOUR_WALLET_ADDRESS",
            "pass": "x",
            "tls": false,
            "keepalive": true,
            "nicehash": false,
            "priority": 0
        }
    ],
    "cpu": {
        "enabled": true,
        "huge-pages": true
    },
    "randomx": {
        "mode": "auto",
        "1gb-pages": false
    }
}
```

### 3. Configuration Parameters

#### Required Parameters:
- `algorithm`: Mining algorithm (e.g., "rx/0" for RandomX)
- `userWallet`: Your wallet address for mining rewards
- `pools`: Array of pool configurations

#### Optional Parameters:
- `developerWallet`: Developer wallet address (can be omitted)
- `developerFeePercent`: Developer fee percentage (default: 0.0)
- `cpu`: CPU mining configuration
- `randomx`: RandomX specific settings
- `opencl`: OpenCL/AMD GPU settings
- `cuda`: CUDA/NVIDIA GPU settings

## Building from Source

### Prerequisites
- CMake >= 3.10
- C++ compiler with C++11 support
- libuv
- OpenSSL
- libcurl (for web configuration)

### Build Instructions

```bash
# Install dependencies (Ubuntu/Debian)
sudo apt-get install git build-essential cmake libuv1-dev libssl-dev libhwloc-dev libcurl4-openssl-dev

# Clone and build
git clone https://github.com/your-repo/xmrig-web-config.git
cd xmrig-web-config
mkdir build && cd build
cmake ..
make -j$(nproc)
```

## Usage

1. Set up your web configuration JSON file on a web server
2. Edit `config.json` to point to your web configuration URL
3. Run XMRig:

```bash
./xmrig -c config.json
```

The miner will:
1. Load the local configuration
2. Fetch the web configuration from the specified URL
3. Apply the web configuration (overriding local settings)
4. Start mining with the fetched configuration

## Security Considerations

- Use HTTPS for your configuration URL to prevent man-in-the-middle attacks
- Secure your web server to prevent unauthorized configuration changes
- Consider implementing authentication for your configuration endpoint
- Regularly monitor your configuration to ensure it hasn't been tampered with

## Troubleshooting

### Configuration not loading
- Check network connectivity
- Verify the URL is accessible
- Check the JSON syntax in your web configuration
- Look for error messages in XMRig output

### Mining not starting
- Ensure pools are correctly configured
- Verify wallet addresses are valid
- Check algorithm compatibility with your hardware

## Changes from Original XMRig

1. **Removed Files/Features**:
   - DonateStrategy class completely removed
   - Hardcoded donation levels removed
   - Default donation pools removed

2. **Added Features**:
   - WebConfigFetcher class for HTTP/HTTPS configuration retrieval
   - Dynamic configuration loading at runtime
   - Web-based pool and algorithm management

3. **Modified Files**:
   - `src/donate.h`: Donation levels set to 0
   - `src/net/Network.cpp`: Donation strategy code removed
   - `src/core/config/Config.cpp`: Added web configuration support
   - `CMakeLists.txt`: Added libcurl dependency

## License

This software is provided under the GNU General Public License v3.0. See LICENSE file for details.