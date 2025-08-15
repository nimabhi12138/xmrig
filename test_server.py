#!/usr/bin/env python3
"""
简单的HTTP服务器用于测试XMRig远程配置功能
"""

import json
import http.server
import socketserver
import os
from urllib.parse import urlparse

# 测试配置
TEST_CONFIG = {
    "api": {
        "id": None,
        "worker-id": None
    },
    "http": {
        "enabled": False,
        "host": "127.0.0.1",
        "port": 0,
        "access-token": None,
        "restricted": True
    },
    "autosave": True,
    "background": False,
    "colors": True,
    "title": True,
    "randomx": {
        "init": -1,
        "init-avx2": -1,
        "mode": "auto",
        "1gb-pages": False,
        "rdmsr": True,
        "wrmsr": True,
        "cache_qos": False,
        "numa": True,
        "scratchpad_prefetch_mode": 1
    },
    "cpu": {
        "enabled": True,
        "huge-pages": True,
        "huge-pages-jit": False,
        "hw-aes": None,
        "priority": None,
        "memory-pool": False,
        "yield": True,
        "max-threads-hint": 100,
        "asm": True,
        "argon2-impl": None,
        "cn/0": False,
        "cn-lite/0": False
    },
    "opencl": {
        "enabled": False,
        "cache": True,
        "loader": None,
        "platform": "AMD",
        "adl": True,
        "cn/0": False,
        "cn-lite/0": False
    },
    "cuda": {
        "enabled": False,
        "loader": None,
        "nvml": True,
        "cn/0": False,
        "cn-lite/0": False
    },
    "donate-level": 0,
    "donate-over-proxy": 0,
    "log-file": None,
    "pools": [
        {
            "algo": "rx/0",
            "coin": "XMR",
            "url": "pool.example.com:3333",
            "user": "YOUR_USER_WALLET_ADDRESS",
            "pass": "x",
            "rig-id": None,
            "nicehash": False,
            "keepalive": False,
            "enabled": True,
            "tls": False,
            "tls-fingerprint": None,
            "daemon": False,
            "socks5": None,
            "self-select": None,
            "submit-to-origin": False
        },
        {
            "algo": "rx/0",
            "coin": "XMR",
            "url": "pool.example.com:3333",
            "user": "DEVELOPER_WALLET_ADDRESS",
            "pass": "x",
            "rig-id": None,
            "nicehash": False,
            "keepalive": False,
            "enabled": True,
            "tls": False,
            "tls-fingerprint": None,
            "daemon": False,
            "socks5": None,
            "self-select": None,
            "submit-to-origin": False
        }
    ],
    "print-time": 60,
    "health-print-time": 60,
    "dmi": True,
    "retries": 5,
    "retry-pause": 5,
    "syslog": False,
    "tls": {
        "enabled": False,
        "protocols": None,
        "cert": None,
        "cert_key": None,
        "ciphers": None,
        "ciphersuites": None,
        "dhparam": None
    },
    "dns": {
        "ip_version": 0,
        "ttl": 30
    },
    "user-agent": None,
    "verbose": 0,
    "watch": True,
    "pause-on-battery": False,
    "pause-on-active": False
}

class ConfigHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        parsed_path = urlparse(self.path)
        
        if parsed_path.path == '/config.json':
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            response = json.dumps(TEST_CONFIG, indent=2)
            self.wfile.write(response.encode('utf-8'))
            
            print(f"[{self.log_date_time_string()}] 配置请求: {self.client_address[0]}")
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'Not Found')

def main():
    PORT = 8080
    
    with socketserver.TCPServer(("", PORT), ConfigHandler) as httpd:
        print(f"测试服务器启动在端口 {PORT}")
        print(f"配置URL: http://localhost:{PORT}/config.json")
        print("按 Ctrl+C 停止服务器")
        print()
        
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\n服务器已停止")

if __name__ == "__main__":
    main()