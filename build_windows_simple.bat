@echo off
setlocal enabledelayedexpansion

echo ========================================
echo XMRig Windows Build Script
echo ========================================
echo.

:: Check if required tools are installed
echo Checking build environment...

:: Check Visual Studio
where cl >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Visual Studio compiler (cl.exe) not found
    echo Please install Visual Studio 2019 or later
    echo Or run "Developer Command Prompt for VS"
    pause
    exit /b 1
)

:: Check CMake
where cmake >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: CMake not found
    echo Please install CMake 3.10 or later
    pause
    exit /b 1
)

:: Check Git
where git >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Git not found
    echo Please install Git
    pause
    exit /b 1
)

echo Build environment check completed
echo.

:: Create build directory
if not exist "build" mkdir build
cd build

:: Configure CMake (optimized for Windows)
echo Configuring CMake...
cmake .. -G "Visual Studio 16 2019" -A x64 ^
    -DWITH_OPENCL=OFF ^
    -DWITH_CUDA=OFF ^
    -DWITH_NVML=OFF ^
    -DWITH_ADL=OFF ^
    -DWITH_DMI=OFF ^
    -DWITH_EMBEDDED_CONFIG=OFF ^
    -DWITH_DEBUG_LOG=OFF ^
    -DWITH_PROFILING=OFF ^
    -DWITH_BENCHMARK=OFF ^
    -DWITH_SECURE_JIT=OFF ^
    -DWITH_STRICT_CACHE=OFF ^
    -DWITH_INTERLEAVE_DEBUG_LOG=OFF ^
    -DWITH_CN_LITE=ON ^
    -DWITH_CN_HEAVY=ON ^
    -DWITH_CN_PICO=ON ^
    -DWITH_CN_FEMTO=ON ^
    -DWITH_RANDOMX=ON ^
    -DWITH_ARGON2=ON ^
    -DWITH_KAWPOW=ON ^
    -DWITH_GHOSTRIDER=ON ^
    -DWITH_HTTP=ON ^
    -DWITH_TLS=ON ^
    -DWITH_ASM=ON ^
    -DWITH_MSR=ON ^
    -DWITH_ENV_VARS=ON ^
    -DWITH_SSE4_1=ON ^
    -DWITH_AVX2=ON ^
    -DWITH_VAES=ON ^
    -DWITH_HWLOC=ON

if %errorlevel% neq 0 (
    echo CMake configuration failed
    pause
    exit /b 1
)

echo CMake configuration successful
echo.

:: Build project
echo Starting build...
echo This may take 10-30 minutes, please wait...
cmake --build . --config Release --parallel

if %errorlevel% neq 0 (
    echo Build failed
    pause
    exit /b 1
)

echo.
echo ========================================
echo Build successful!
echo ========================================
echo.

:: Check executable file
if exist "Release\xmrig.exe" (
    echo Executable location: build\Release\xmrig.exe
    
    :: Copy to root directory
    copy "Release\xmrig.exe" "..\xmrig.exe" >nul
    echo xmrig.exe copied to project root directory
    echo.
    
    :: Show file info
    for %%A in ("Release\xmrig.exe") do (
        echo File size: %%~zA bytes
        echo Created: %%~tA
    )
    echo.
) else (
    echo WARNING: Compiled executable not found
)

:: Return to root directory
cd ..

echo Usage:
echo xmrig.exe --remote-config=https://your-server.com/config.json
echo.
echo Test command:
echo xmrig.exe --remote-config=https://your-server.com/config.json --dry-run
echo.

:: Check if config file exists
if exist "complete_config_example.json" (
    echo Found config file: complete_config_example.json
    echo You can use this as a reference for remote configuration
    echo.
)

echo Build completed!
pause