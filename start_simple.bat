@echo off
setlocal enabledelayedexpansion

echo ========================================
echo XMRig Simple Start Script
echo ========================================
echo.

:: Check if executable exists
if not exist "xmrig.exe" (
    echo ERROR: xmrig.exe not found
    echo Please run build_windows_simple.bat first to compile the program
    pause
    exit /b 1
)

:: Set default config URL
set DEFAULT_CONFIG_URL=https://your-server.com/config.json

:: Check command line arguments
if "%~1"=="" (
    echo Usage:
    echo start_simple.bat [config_url]
    echo.
    echo Example:
    echo start_simple.bat https://your-server.com/config.json
    echo.
    echo If no config URL provided, will use default: %DEFAULT_CONFIG_URL%
    echo.
    set /p CONFIG_URL="Enter config URL (press Enter for default): "
    if "!CONFIG_URL!"=="" set CONFIG_URL=%DEFAULT_CONFIG_URL%
) else (
    set CONFIG_URL=%~1
)

echo.
echo Using config URL: %CONFIG_URL%
echo.

:: Check network connection
echo Checking network connection...
ping -n 1 8.8.8.8 >nul 2>&1
if %errorlevel% neq 0 (
    echo WARNING: Network connection may have issues
    echo Please check network settings
    echo.
)

:: Show system info
echo System info:
echo - CPU: %NUMBER_OF_PROCESSORS% cores
echo - OS: %OS%
echo.

:: Start mining program
echo Starting XMRig...
echo Config URL: %CONFIG_URL%
echo.
echo Press Ctrl+C to stop program
echo.

xmrig.exe --remote-config="%CONFIG_URL%"

echo.
echo Program exited
pause