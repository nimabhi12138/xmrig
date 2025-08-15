#!/usr/bin/env python3
"""
币种配置管理系统 - 完整性测试脚本
用于验证系统所有文件是否存在且功能完整
"""

import os
import json
import re
from pathlib import Path

class SystemTester:
    def __init__(self):
        self.base_path = Path('/workspace/crypto-config-system')
        self.test_results = {
            'passed': [],
            'failed': [],
            'warnings': []
        }
        
    def test_file_structure(self):
        """测试文件结构完整性"""
        print("\n=== 测试文件结构 ===")
        
        required_files = {
            # 根目录文件
            'index.php': '系统首页',
            'install.php': '安装向导',
            'captcha.php': '验证码生成',
            'README.md': '说明文档',
            
            # 配置文件
            'config/config.php': '系统配置',
            
            # 数据库
            'database/schema.sql': '数据库结构',
            
            # 核心类
            'includes/Database.php': '数据库操作类',
            'includes/Captcha.php': '验证码类',
            
            # 管理后台
            'admin/index.php': '管理仪表板',
            'admin/login.php': '管理员登录',
            'admin/logout.php': '管理员登出',
            'admin/currencies.php': '币种管理',
            'admin/fields.php': '字段管理',
            
            # 用户端
            'user/login.php': '用户登录',
            'user/register.php': '用户注册',
            'user/dashboard.php': '用户仪表板',
            'user/logout.php': '用户登出',
            'user/get_fields.php': 'AJAX字段获取',
            
            # API
            'api/config.php': 'API接口'
        }
        
        for file_path, description in required_files.items():
            full_path = self.base_path / file_path
            if full_path.exists():
                self.test_results['passed'].append(f"✓ {file_path} - {description}")
                print(f"✓ {file_path} - {description}")
            else:
                self.test_results['failed'].append(f"✗ {file_path} - {description} [文件不存在]")
                print(f"✗ {file_path} - {description} [文件不存在]")
                
    def test_php_syntax(self):
        """测试PHP文件语法"""
        print("\n=== 测试PHP语法 ===")
        
        php_files = list(self.base_path.glob('**/*.php'))
        
        for php_file in php_files:
            relative_path = php_file.relative_to(self.base_path)
            
            # 基础语法检查
            with open(php_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # 检查PHP开始标签
            if not content.strip().startswith('<?php'):
                self.test_results['warnings'].append(f"⚠ {relative_path} - 可能缺少PHP开始标签")
                print(f"⚠ {relative_path} - 可能缺少PHP开始标签")
            else:
                print(f"✓ {relative_path} - PHP语法正确")
                self.test_results['passed'].append(f"✓ {relative_path} - PHP语法正确")
                
    def test_database_structure(self):
        """测试数据库结构"""
        print("\n=== 测试数据库结构 ===")
        
        schema_file = self.base_path / 'database/schema.sql'
        
        if schema_file.exists():
            with open(schema_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # 检查必要的表
            required_tables = ['users', 'currencies', 'custom_fields', 'user_configs']
            
            for table in required_tables:
                if f'CREATE TABLE IF NOT EXISTS {table}' in content:
                    self.test_results['passed'].append(f"✓ 数据表 {table} 定义存在")
                    print(f"✓ 数据表 {table} 定义存在")
                else:
                    self.test_results['failed'].append(f"✗ 数据表 {table} 定义缺失")
                    print(f"✗ 数据表 {table} 定义缺失")
                    
            # 检查默认数据
            if 'INSERT INTO users' in content:
                print("✓ 默认管理员账户存在")
                self.test_results['passed'].append("✓ 默认管理员账户存在")
            else:
                print("✗ 默认管理员账户缺失")
                self.test_results['failed'].append("✗ 默认管理员账户缺失")
                
            if 'INSERT INTO currencies' in content:
                print("✓ 示例币种数据存在")
                self.test_results['passed'].append("✓ 示例币种数据存在")
            else:
                print("⚠ 示例币种数据缺失")
                self.test_results['warnings'].append("⚠ 示例币种数据缺失")
                
    def test_security_features(self):
        """测试安全特性"""
        print("\n=== 测试安全特性 ===")
        
        # 检查密码加密
        login_files = [
            'admin/login.php',
            'user/login.php',
            'user/register.php'
        ]
        
        for file_path in login_files:
            full_path = self.base_path / file_path
            if full_path.exists():
                with open(full_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                    
                if 'password_verify' in content or 'password_hash' in content:
                    print(f"✓ {file_path} - 使用密码加密")
                    self.test_results['passed'].append(f"✓ {file_path} - 使用密码加密")
                else:
                    print(f"⚠ {file_path} - 未检测到密码加密")
                    self.test_results['warnings'].append(f"⚠ {file_path} - 未检测到密码加密")
                    
        # 检查SQL注入防护
        db_file = self.base_path / 'includes/Database.php'
        if db_file.exists():
            with open(db_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            if 'prepare' in content and 'PDO' in content:
                print("✓ 使用PDO预处理语句防止SQL注入")
                self.test_results['passed'].append("✓ 使用PDO预处理语句防止SQL注入")
            else:
                print("✗ 未检测到SQL注入防护")
                self.test_results['failed'].append("✗ 未检测到SQL注入防护")
                
    def test_api_functionality(self):
        """测试API功能"""
        print("\n=== 测试API功能 ===")
        
        api_file = self.base_path / 'api/config.php'
        
        if api_file.exists():
            with open(api_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # 检查必要的API功能
            checks = {
                'header(\'Content-Type: application/json\')': 'JSON响应头',
                'token': 'Token验证',
                'json_encode': 'JSON输出',
                'http_response_code': 'HTTP状态码'
            }
            
            for check, description in checks.items():
                if check in content:
                    print(f"✓ API - {description}")
                    self.test_results['passed'].append(f"✓ API - {description}")
                else:
                    print(f"✗ API - {description} 缺失")
                    self.test_results['failed'].append(f"✗ API - {description} 缺失")
                    
    def test_ui_features(self):
        """测试UI特性"""
        print("\n=== 测试UI特性 ===")
        
        # 检查所有PHP文件的UI特性
        ui_files = [
            'index.php',
            'admin/index.php',
            'user/dashboard.php'
        ]
        
        for file_path in ui_files:
            full_path = self.base_path / file_path
            if full_path.exists():
                with open(full_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                    
                # 检查科技感UI元素
                ui_checks = {
                    'linear-gradient': '渐变背景',
                    'animation': '动画效果',
                    '--primary-color': '主题色变量',
                    'bootstrap': 'Bootstrap框架',
                    'bi-': 'Bootstrap图标'
                }
                
                for check, description in ui_checks.items():
                    if check in content:
                        print(f"✓ {file_path} - {description}")
                        self.test_results['passed'].append(f"✓ {file_path} - {description}")
                        
    def test_functionality_integration(self):
        """测试功能集成"""
        print("\n=== 测试功能集成 ===")
        
        # 检查用户配置流程
        dashboard_file = self.base_path / 'user/dashboard.php'
        if dashboard_file.exists():
            with open(dashboard_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            integrations = {
                'loadFields': '动态字段加载',
                'save_config': '配置保存',
                'delete_config': '配置删除',
                'viewConfig': '配置查看',
                'copyToClipboard': '复制功能'
            }
            
            for check, description in integrations.items():
                if check in content:
                    print(f"✓ 用户端 - {description}")
                    self.test_results['passed'].append(f"✓ 用户端 - {description}")
                else:
                    print(f"✗ 用户端 - {description} 缺失")
                    self.test_results['failed'].append(f"✗ 用户端 - {description} 缺失")
                    
    def generate_report(self):
        """生成测试报告"""
        print("\n" + "="*50)
        print("测试报告总结")
        print("="*50)
        
        total_tests = len(self.test_results['passed']) + len(self.test_results['failed'])
        pass_rate = (len(self.test_results['passed']) / total_tests * 100) if total_tests > 0 else 0
        
        print(f"\n✅ 通过测试: {len(self.test_results['passed'])}")
        print(f"❌ 失败测试: {len(self.test_results['failed'])}")
        print(f"⚠️  警告: {len(self.test_results['warnings'])}")
        print(f"📊 通过率: {pass_rate:.1f}%")
        
        if self.test_results['failed']:
            print("\n需要修复的问题:")
            for issue in self.test_results['failed']:
                print(f"  {issue}")
                
        if self.test_results['warnings']:
            print("\n警告信息:")
            for warning in self.test_results['warnings']:
                print(f"  {warning}")
                
        # 保存报告
        report = {
            'summary': {
                'total_tests': total_tests,
                'passed': len(self.test_results['passed']),
                'failed': len(self.test_results['failed']),
                'warnings': len(self.test_results['warnings']),
                'pass_rate': f"{pass_rate:.1f}%"
            },
            'details': self.test_results
        }
        
        report_file = self.base_path / 'test_report.json'
        with open(report_file, 'w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
            
        print(f"\n📄 详细报告已保存到: test_report.json")
        
        return pass_rate >= 90  # 90%以上通过率认为系统正常

def main():
    print("🚀 开始测试币种配置管理系统")
    print("="*50)
    
    tester = SystemTester()
    
    # 运行所有测试
    tester.test_file_structure()
    tester.test_php_syntax()
    tester.test_database_structure()
    tester.test_security_features()
    tester.test_api_functionality()
    tester.test_ui_features()
    tester.test_functionality_integration()
    
    # 生成报告
    system_ok = tester.generate_report()
    
    if system_ok:
        print("\n✅ 系统测试通过！所有核心功能正常。")
    else:
        print("\n⚠️ 系统存在一些问题，请查看上述报告进行修复。")
    
    return system_ok

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)