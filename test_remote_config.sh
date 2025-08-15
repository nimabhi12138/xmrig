#!/bin/bash

# XMRig 远程配置测试脚本

echo "=== XMRig 远程配置功能测试 ==="
echo

# 检查是否编译成功
if [ ! -f "build/xmrig" ]; then
    echo "错误: 未找到编译后的xmrig程序"
    echo "请先运行: mkdir build && cd build && cmake .. && make"
    exit 1
fi

echo "1. 测试帮助信息..."
./build/xmrig --help | grep -i "remote-config" || echo "警告: 未找到remote-config参数说明"

echo
echo "2. 测试无效URL..."
./build/xmrig --remote-config="invalid-url" 2>&1 | head -10

echo
echo "3. 测试本地JSON文件..."
# 创建一个临时的本地配置文件
cat > test_config.json << 'EOF'
{
    "pools": [
        {
            "algo": "rx/0",
            "coin": "XMR",
            "url": "pool.example.com:3333",
            "user": "test-wallet-address",
            "pass": "x",
            "enabled": true
        }
    ],
    "donate-level": 0,
    "donate-over-proxy": 0
}
EOF

echo "使用本地配置文件测试..."
./build/xmrig --remote-config="file://$(pwd)/test_config.json" --dry-run 2>&1 | head -10

echo
echo "4. 清理测试文件..."
rm -f test_config.json

echo
echo "=== 测试完成 ==="
echo
echo "使用说明:"
echo "1. 准备远程配置文件 (参考 remote_config_example.json)"
echo "2. 将配置文件上传到Web服务器"
echo "3. 运行: ./build/xmrig --remote-config=https://your-server.com/config.json"
echo
echo "注意事项:"
echo "- 确保远程配置文件是有效的JSON格式"
echo "- 建议使用HTTPS协议"
echo "- 配置文件中应包含完整的挖矿配置"