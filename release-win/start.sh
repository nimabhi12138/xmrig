#!/bin/bash

# XMRig 快速启动脚本

echo "================================"
echo "XMRig 定制版启动器"
echo "================================"

# 检查可执行文件
if [ ! -f "xmrig-linux-x64" ]; then
    echo "错误: 找不到 xmrig-linux-x64 文件"
    echo "请确保在正确的目录运行此脚本"
    exit 1
fi

# 给予执行权限
chmod +x xmrig-linux-x64

# 显示菜单
echo ""
echo "请选择运行模式："
echo "1. 正常运行（前台）"
echo "2. 后台运行"
echo "3. 测试模式（不实际挖矿）"
echo "4. 自定义用户名运行"
echo "5. 退出"
echo ""
read -p "请输入选项 [1-5]: " choice

case $choice in
    1)
        echo "启动挖矿程序..."
        ./xmrig-linux-x64
        ;;
    2)
        echo "后台启动挖矿程序..."
        nohup ./xmrig-linux-x64 > xmrig.log 2>&1 &
        echo "程序已在后台运行，PID: $!"
        echo "查看日志: tail -f xmrig.log"
        ;;
    3)
        echo "启动测试模式..."
        ./xmrig-linux-x64 --dry-run
        ;;
    4)
        read -p "请输入用户名: " username
        if [ -z "$username" ]; then
            echo "用户名不能为空"
            exit 1
        fi
        echo "复制程序为 $username..."
        cp xmrig-linux-x64 "$username"
        chmod +x "$username"
        echo "启动 $username..."
        ./"$username"
        ;;
    5)
        echo "退出"
        exit 0
        ;;
    *)
        echo "无效选项"
        exit 1
        ;;
esac