@echo off
setlocal enabledelayedexpansion

echo ========================================
echo XMRig Windows 编译脚本
echo ========================================
echo.

:: 检查是否安装了必要的工具
echo 检查编译环境...

:: 检查Visual Studio
where cl >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误: 未找到Visual Studio编译器 (cl.exe)
    echo 请安装Visual Studio 2019或更新版本
    echo 或者运行 "Developer Command Prompt for VS"
    pause
    exit /b 1
)

:: 检查CMake
where cmake >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误: 未找到CMake
    echo 请安装CMake 3.10或更新版本
    pause
    exit /b 1
)

:: 检查Git
where git >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误: 未找到Git
    echo 请安装Git
    pause
    exit /b 1
)

echo 编译环境检查完成
echo.

:: 创建构建目录
if not exist "build" mkdir build
cd build

:: 配置CMake (针对Windows优化)
echo 配置CMake...
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
    echo CMake配置失败
    pause
    exit /b 1
)

echo CMake配置成功
echo.

:: 编译项目
echo 开始编译...
cmake --build . --config Release --parallel

if %errorlevel% neq 0 (
    echo 编译失败
    pause
    exit /b 1
)

echo.
echo ========================================
echo 编译成功！
echo ========================================
echo.
echo 可执行文件位置: build\Release\xmrig.exe
echo.
echo 使用方法:
echo xmrig.exe --remote-config=https://your-server.com/config.json
echo.
echo 测试命令:
echo xmrig.exe --remote-config=https://your-server.com/config.json --dry-run
echo.

:: 复制可执行文件到根目录
if exist "Release\xmrig.exe" (
    copy "Release\xmrig.exe" "..\xmrig.exe" >nul
    echo 已将xmrig.exe复制到项目根目录
    echo.
)

pause