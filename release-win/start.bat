@echo off
chcp 65001 >nul
title XMRig 定制版启动器

echo ================================
echo XMRig 定制版启动器
echo ================================
echo.

if not exist xmrig.exe (
    echo 错误: 找不到 xmrig.exe 文件
    echo 请确保在正确的目录运行此脚本
    pause
    exit /b 1
)

echo 请选择运行模式：
echo 1. 正常运行（前台窗口）
echo 2. 后台运行（隐藏窗口）
echo 3. 测试模式（不实际挖矿）
echo 4. 自定义用户名运行
echo 5. 查看日志
echo 6. 退出
echo.

set /p choice=请输入选项 [1-6]: 

if "%choice%"=="1" (
    echo 启动挖矿程序...
    xmrig.exe
    pause
) else if "%choice%"=="2" (
    echo 后台启动挖矿程序...
    start /min xmrig.exe
    echo 程序已在后台运行
    echo 可以在任务管理器中查看
    pause
) else if "%choice%"=="3" (
    echo 启动测试模式...
    xmrig.exe --dry-run
    pause
) else if "%choice%"=="4" (
    set /p username=请输入用户名: 
    if "%username%"=="" (
        echo 用户名不能为空
        pause
        exit /b 1
    )
    echo 复制程序为 %username%.exe...
    copy xmrig.exe "%username%.exe" >nul
    echo 启动 %username%.exe...
    "%username%.exe"
    pause
) else if "%choice%"=="5" (
    if exist *.log (
        echo 显示最新日志文件...
        type *.log | more
    ) else (
        echo 没有找到日志文件
    )
    pause
) else if "%choice%"=="6" (
    echo 退出
    exit /b 0
) else (
    echo 无效选项
    pause
)