@echo off
setlocal enabledelayedexpansion

echo ========================================
echo XMRig 快速启动脚本
echo ========================================
echo.

:: 检查可执行文件是否存在
if not exist "xmrig.exe" (
    echo 错误: 未找到 xmrig.exe
    echo 请先运行 build_windows.bat 编译程序
    pause
    exit /b 1
)

:: 设置默认配置URL
set DEFAULT_CONFIG_URL=https://your-server.com/config.json

:: 检查是否有命令行参数
if "%~1"=="" (
    echo 使用方法:
    echo start_mining.bat [配置URL]
    echo.
    echo 示例:
    echo start_mining.bat https://your-server.com/config.json
    echo.
    echo 如果没有提供配置URL，将使用默认URL: %DEFAULT_CONFIG_URL%
    echo.
    set /p CONFIG_URL="请输入配置URL (直接回车使用默认): "
    if "!CONFIG_URL!"=="" set CONFIG_URL=%DEFAULT_CONFIG_URL%
) else (
    set CONFIG_URL=%~1
)

echo.
echo 使用配置URL: %CONFIG_URL%
echo.

:: 检查网络连接
echo 检查网络连接...
ping -n 1 8.8.8.8 >nul 2>&1
if %errorlevel% neq 0 (
    echo 警告: 网络连接可能有问题
    echo 请检查网络设置
    echo.
)

:: 显示系统信息
echo 系统信息:
echo - CPU: %NUMBER_OF_PROCESSORS% 核心
echo - 内存: 
for /f "tokens=2" %%i in ('wmic computersystem get TotalPhysicalMemory /value ^| find "="') do set TOTAL_MEM=%%i
set /a TOTAL_MEM_GB=%TOTAL_MEM:~0,-1%/1024/1024/1024
echo   %TOTAL_MEM_GB% GB
echo - 操作系统: %OS%
echo.

:: 询问是否以管理员身份运行
echo 建议以管理员身份运行以获得最佳性能
set /p RUN_AS_ADMIN="是否以管理员身份运行? (y/n): "
if /i "!RUN_AS_ADMIN!"=="y" (
    echo 尝试以管理员身份运行...
    powershell -Command "Start-Process '%~dpnx0' -ArgumentList '%CONFIG_URL%' -Verb RunAs"
    exit /b 0
)

:: 启动挖矿程序
echo 启动XMRig...
echo 配置URL: %CONFIG_URL%
echo.
echo 按 Ctrl+C 停止程序
echo.

xmrig.exe --remote-config="%CONFIG_URL%"

echo.
echo 程序已退出
pause