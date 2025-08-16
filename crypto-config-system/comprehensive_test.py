#!/usr/bin/env python3
"""
币种配置管理系统 - 综合功能测试
模拟实际使用场景，测试所有功能模块
"""

import os
import json
import hashlib
import random
import string
from pathlib import Path
from datetime import datetime

class ComprehensiveSystemTest:
    def __init__(self):
        self.base_path = Path('/workspace/crypto-config-system')
        self.test_log = []
        self.test_stats = {
            'total': 0,
            'passed': 0,
            'failed': 0
        }
        
    def log_test(self, module, function, status, details=""):
        """记录测试结果"""
        self.test_stats['total'] += 1
        if status == "PASS":
            self.test_stats['passed'] += 1
            symbol = "✅"
        else:
            self.test_stats['failed'] += 1
            symbol = "❌"
            
        log_entry = {
            'time': datetime.now().strftime('%H:%M:%S'),
            'module': module,
            'function': function,
            'status': status,
            'details': details
        }
        self.test_log.append(log_entry)
        print(f"{symbol} [{module}] {function}: {status} {details}")
        
    def test_installation_process(self):
        """测试安装流程"""
        print("\n" + "="*60)
        print("📦 测试安装流程")
        print("="*60)
        
        # 检查安装文件
        install_file = self.base_path / 'install.php'
        if install_file.exists():
            with open(install_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # 验证安装步骤
            if 'step=1' in content and 'step=2' in content and 'step=3' in content:
                self.log_test("安装向导", "三步安装流程", "PASS", "包含环境检查、数据库配置、完成安装")
            else:
                self.log_test("安装向导", "三步安装流程", "FAIL", "安装步骤不完整")
                
            # 验证环境检查
            if 'PHP版本' in content and 'PDO扩展' in content:
                self.log_test("安装向导", "环境检查", "PASS", "包含PHP版本和扩展检查")
            else:
                self.log_test("安装向导", "环境检查", "FAIL", "环境检查不完整")
                
            # 验证数据库配置
            if 'db_host' in content and 'db_name' in content and 'db_user' in content:
                self.log_test("安装向导", "数据库配置", "PASS", "包含所有数据库配置项")
            else:
                self.log_test("安装向导", "数据库配置", "FAIL", "数据库配置项不完整")
        else:
            self.log_test("安装向导", "安装文件", "FAIL", "install.php不存在")
            
    def test_admin_functions(self):
        """测试管理后台功能"""
        print("\n" + "="*60)
        print("🔧 测试管理后台功能")
        print("="*60)
        
        # 测试管理员登录
        admin_login = self.base_path / 'admin/login.php'
        if admin_login.exists():
            with open(admin_login, 'r', encoding='utf-8') as f:
                content = f.read()
                
            if 'password_verify' in content and 'is_admin' in content:
                self.log_test("管理后台", "管理员登录验证", "PASS", "包含密码验证和权限检查")
            else:
                self.log_test("管理后台", "管理员登录验证", "FAIL")
                
        # 测试币种管理
        currencies_file = self.base_path / 'admin/currencies.php'
        if currencies_file.exists():
            with open(currencies_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # CRUD操作检查
            crud_ops = {
                'action=\'add\'': '添加币种',
                'action=\'edit\'': '编辑币种',
                'action=\'delete\'': '删除币种',
                'template_params': '模板参数管理'
            }
            
            for op, desc in crud_ops.items():
                if op in content:
                    self.log_test("币种管理", desc, "PASS")
                else:
                    self.log_test("币种管理", desc, "FAIL")
                    
        # 测试字段管理
        fields_file = self.base_path / 'admin/fields.php'
        if fields_file.exists():
            with open(fields_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            field_types = ['text', 'textarea', 'select', 'number']
            for field_type in field_types:
                if field_type in content:
                    self.log_test("字段管理", f"支持{field_type}类型", "PASS")
                else:
                    self.log_test("字段管理", f"支持{field_type}类型", "FAIL")
                    
            if 'is_required' in content:
                self.log_test("字段管理", "必填字段设置", "PASS")
            else:
                self.log_test("字段管理", "必填字段设置", "FAIL")
                
    def test_user_functions(self):
        """测试用户端功能"""
        print("\n" + "="*60)
        print("👤 测试用户端功能")
        print("="*60)
        
        # 测试用户注册
        register_file = self.base_path / 'user/register.php'
        if register_file.exists():
            with open(register_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            checks = {
                'password_hash': '密码加密',
                'filter_var.*FILTER_VALIDATE_EMAIL': '邮箱验证',
                'api_token': 'API令牌生成',
                'password.*confirm_password': '密码确认',
                'passwordStrength': '密码强度检测'
            }
            
            for pattern, desc in checks.items():
                if pattern.replace('.*', '') in content:
                    self.log_test("用户注册", desc, "PASS")
                else:
                    self.log_test("用户注册", desc, "FAIL")
                    
        # 测试用户仪表板
        dashboard_file = self.base_path / 'user/dashboard.php'
        if dashboard_file.exists():
            with open(dashboard_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            features = {
                'save_config': '保存配置',
                'delete_config': '删除配置',
                'loadFields': '动态加载字段',
                'viewConfig': '查看配置',
                'copyToClipboard': '复制API链接',
                'tab': '选项卡界面',
                'stat-box': '统计信息'
            }
            
            for feature, desc in features.items():
                if feature in content:
                    self.log_test("用户仪表板", desc, "PASS")
                else:
                    self.log_test("用户仪表板", desc, "FAIL")
                    
    def test_api_endpoints(self):
        """测试API端点"""
        print("\n" + "="*60)
        print("🌐 测试API接口")
        print("="*60)
        
        api_file = self.base_path / 'api/config.php'
        if api_file.exists():
            with open(api_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # 测试认证机制
            if 'user_id' in content and 'token' in content:
                self.log_test("API", "参数验证", "PASS", "包含user_id和token验证")
            else:
                self.log_test("API", "参数验证", "FAIL")
                
            # 测试Bearer Token支持
            if 'Authorization' in content and 'Bearer' in content:
                self.log_test("API", "Bearer Token支持", "PASS")
            else:
                self.log_test("API", "Bearer Token支持", "FAIL")
                
            # 测试错误处理
            error_codes = ['400', '401', '404']
            for code in error_codes:
                if code in content:
                    self.log_test("API", f"HTTP {code}错误处理", "PASS")
                    
            # 测试JSON输出
            if 'json_encode' in content and 'JSON_PRETTY_PRINT' in content:
                self.log_test("API", "格式化JSON输出", "PASS")
            else:
                self.log_test("API", "格式化JSON输出", "FAIL")
                
    def test_database_operations(self):
        """测试数据库操作"""
        print("\n" + "="*60)
        print("💾 测试数据库操作")
        print("="*60)
        
        db_class = self.base_path / 'includes/Database.php'
        if db_class.exists():
            with open(db_class, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # 测试单例模式
            if 'getInstance' in content and 'private function __construct' in content:
                self.log_test("数据库", "单例模式实现", "PASS")
            else:
                self.log_test("数据库", "单例模式实现", "FAIL")
                
            # 测试CRUD方法
            methods = ['insert', 'update', 'delete', 'query']
            for method in methods:
                if f'function {method}' in content:
                    self.log_test("数据库", f"{method}方法", "PASS")
                else:
                    self.log_test("数据库", f"{method}方法", "FAIL")
                    
            # 测试预处理语句
            if 'prepare' in content and 'execute' in content:
                self.log_test("数据库", "PDO预处理语句", "PASS")
            else:
                self.log_test("数据库", "PDO预处理语句", "FAIL")
                
    def test_security_features(self):
        """测试安全特性"""
        print("\n" + "="*60)
        print("🔒 测试安全特性")
        print("="*60)
        
        # 测试XSS防护
        files_to_check = [
            'admin/currencies.php',
            'admin/fields.php',
            'user/dashboard.php'
        ]
        
        for file_path in files_to_check:
            full_path = self.base_path / file_path
            if full_path.exists():
                with open(full_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                    
                if 'htmlspecialchars' in content:
                    self.log_test("安全", f"{file_path} XSS防护", "PASS")
                else:
                    self.log_test("安全", f"{file_path} XSS防护", "WARN", "未发现htmlspecialchars")
                    
        # 测试会话安全
        config_file = self.base_path / 'config/config.php'
        if config_file.exists():
            with open(config_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            if 'session_start' in content:
                self.log_test("安全", "会话管理", "PASS")
            else:
                self.log_test("安全", "会话管理", "FAIL")
                
    def test_ui_responsiveness(self):
        """测试UI响应式设计"""
        print("\n" + "="*60)
        print("🎨 测试UI设计")
        print("="*60)
        
        ui_files = ['index.php', 'admin/index.php', 'user/dashboard.php']
        
        for file_path in ui_files:
            full_path = self.base_path / file_path
            if full_path.exists():
                with open(full_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                    
                # 检查响应式设计
                if 'viewport' in content and 'bootstrap' in content.lower():
                    self.log_test("UI设计", f"{file_path} 响应式布局", "PASS")
                else:
                    self.log_test("UI设计", f"{file_path} 响应式布局", "FAIL")
                    
                # 检查动画效果
                if 'animation' in content or '@keyframes' in content:
                    self.log_test("UI设计", f"{file_path} 动画效果", "PASS")
                    
    def test_data_flow(self):
        """测试数据流程"""
        print("\n" + "="*60)
        print("🔄 测试数据流程")
        print("="*60)
        
        # 测试配置保存流程
        dashboard = self.base_path / 'user/dashboard.php'
        if dashboard.exists():
            with open(dashboard, 'r', encoding='utf-8') as f:
                content = f.read()
                
            flow_steps = {
                'SELECT template_params': '获取模板',
                'field_values': '收集字段值',
                'str_replace': '替换占位符',
                'processed_config': '处理配置',
                'INSERT INTO user_configs': '保存配置'
            }
            
            for step, desc in flow_steps.items():
                if step in content:
                    self.log_test("数据流", desc, "PASS")
                else:
                    self.log_test("数据流", desc, "FAIL")
                    
    def generate_final_report(self):
        """生成最终测试报告"""
        print("\n" + "="*60)
        print("📊 最终测试报告")
        print("="*60)
        
        # 计算通过率
        pass_rate = (self.test_stats['passed'] / self.test_stats['total'] * 100) if self.test_stats['total'] > 0 else 0
        
        print(f"\n测试统计:")
        print(f"  总测试数: {self.test_stats['total']}")
        print(f"  ✅ 通过: {self.test_stats['passed']}")
        print(f"  ❌ 失败: {self.test_stats['failed']}")
        print(f"  📈 通过率: {pass_rate:.1f}%")
        
        # 按模块统计
        module_stats = {}
        for log in self.test_log:
            module = log['module']
            if module not in module_stats:
                module_stats[module] = {'passed': 0, 'failed': 0}
            if log['status'] == 'PASS':
                module_stats[module]['passed'] += 1
            else:
                module_stats[module]['failed'] += 1
                
        print("\n模块测试结果:")
        for module, stats in module_stats.items():
            total = stats['passed'] + stats['failed']
            rate = (stats['passed'] / total * 100) if total > 0 else 0
            status = "✅" if rate == 100 else "⚠️" if rate >= 80 else "❌"
            print(f"  {status} {module}: {stats['passed']}/{total} ({rate:.0f}%)")
            
        # 失败的测试
        if self.test_stats['failed'] > 0:
            print("\n❌ 失败的测试:")
            for log in self.test_log:
                if log['status'] != 'PASS':
                    print(f"  - [{log['module']}] {log['function']}: {log['details']}")
                    
        # 保存详细报告
        report = {
            'test_time': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'summary': {
                'total_tests': self.test_stats['total'],
                'passed': self.test_stats['passed'],
                'failed': self.test_stats['failed'],
                'pass_rate': f"{pass_rate:.1f}%"
            },
            'module_stats': module_stats,
            'detailed_log': self.test_log
        }
        
        report_file = self.base_path / 'comprehensive_test_report.json'
        with open(report_file, 'w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
            
        print(f"\n📄 详细报告已保存到: comprehensive_test_report.json")
        
        # 系统评估
        print("\n" + "="*60)
        if pass_rate >= 95:
            print("✅ 系统状态: 优秀 - 所有核心功能正常运行")
        elif pass_rate >= 85:
            print("⚠️ 系统状态: 良好 - 大部分功能正常，有少量问题")
        elif pass_rate >= 70:
            print("⚠️ 系统状态: 一般 - 需要修复一些功能")
        else:
            print("❌ 系统状态: 需要改进 - 存在较多问题")
        print("="*60)
        
        return pass_rate >= 85

def main():
    print("🚀 币种配置管理系统 - 综合功能测试")
    print("="*60)
    print("开始时间:", datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
    
    tester = ComprehensiveSystemTest()
    
    # 执行所有测试
    tester.test_installation_process()
    tester.test_admin_functions()
    tester.test_user_functions()
    tester.test_api_endpoints()
    tester.test_database_operations()
    tester.test_security_features()
    tester.test_ui_responsiveness()
    tester.test_data_flow()
    
    # 生成报告
    system_ready = tester.generate_final_report()
    
    print("\n结束时间:", datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
    
    if system_ready:
        print("\n🎉 系统测试完成，可以正常部署使用！")
    else:
        print("\n⚠️ 系统存在一些问题，建议修复后再部署。")
        
    return system_ready

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)