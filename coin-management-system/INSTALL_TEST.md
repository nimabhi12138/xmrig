# 币种管理系统 - 安装测试指南

## 🚀 快速开始

### 环境要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web服务器 (Apache/Nginx)

### 安装步骤

1. **上传文件**
   ```bash
   # 将项目文件上传到Web服务器目录
   # 例如: /var/www/html/coin-management/
   ```

2. **设置权限**
   ```bash
   chmod 755 config/
   chmod 644 config/database.php
   ```

3. **开始安装**
   - 在浏览器中访问: `http://your-domain.com/install.php`
   - 按照安装向导完成配置

### 安装过程详解

#### 第1步：环境检查
系统会自动检查：
- ✅ PHP版本 (>= 7.4)
- ✅ 必要的PHP扩展
- ✅ 目录权限
- ✅ 文件完整性

#### 第2步：数据库配置
填写数据库信息：
- **主机**: localhost (或您的数据库服务器地址)
- **端口**: 3306 (默认MySQL端口)
- **数据库名**: coin_management (或自定义)
- **用户名**: 数据库用户名
- **密码**: 数据库密码

**注意**: 确保数据库用户具有CREATE DATABASE权限

#### 第3步：安装数据库
系统会自动：
- 创建数据库表结构
- 插入默认系统配置
- 生成配置文件

#### 第4步：创建管理员
设置管理员账户：
- **用户名**: admin (或自定义)
- **密码**: 建议使用强密码
- **邮箱**: 可选

#### 第5步：安装完成
- 🎉 安装成功！
- 🗑️ 请删除 `install.php` 文件
- 🔗 访问管理后台或用户端

## 🔧 常见问题解决

### 1. 安装过程中卡在某一步
**原因**: 可能是权限问题或数据库连接问题

**解决方案**:
```bash
# 检查config目录权限
ls -la config/

# 重新设置权限
chmod 755 config/
chmod 644 config/database.php

# 检查数据库连接
mysql -u username -p -h localhost
```

### 2. 数据库连接失败
**常见原因**:
- 数据库服务未启动
- 用户名/密码错误
- 主机地址错误
- 用户权限不足

**解决方案**:
```sql
-- 检查MySQL服务状态
sudo systemctl status mysql

-- 创建数据库用户
CREATE USER 'coin_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON coin_management.* TO 'coin_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. 页面显示空白或错误
**检查步骤**:
1. 查看PHP错误日志
2. 检查文件权限
3. 确认所有文件已上传

```bash
# 查看PHP错误日志
tail -f /var/log/php_errors.log

# 检查Apache错误日志
tail -f /var/log/apache2/error.log
```

### 4. 安装完成后无法访问
**检查项目**:
- Web服务器配置
- 虚拟主机设置
- 防火墙设置

## 🧪 本地测试环境搭建

### 使用XAMPP (Windows/Mac/Linux)
1. 下载安装XAMPP
2. 启动Apache和MySQL
3. 将项目放到 `htdocs` 目录
4. 访问 `http://localhost/coin-management/install.php`

### 使用WAMP (Windows)
1. 下载安装WAMP
2. 启动所有服务
3. 将项目放到 `www` 目录
4. 访问 `http://localhost/coin-management/install.php`

### 使用宝塔面板
1. 登录宝塔面板
2. 创建新网站
3. 上传项目文件
4. 配置数据库
5. 访问域名进行安装

### 使用小皮面板
1. 登录小皮面板
2. 创建站点
3. 上传源码
4. 创建数据库
5. 开始安装

## 📊 安装后验证

### 1. 访问管理后台
- URL: `http://your-domain.com/admin/login.php`
- 使用安装时创建的管理员账户登录

### 2. 访问用户端
- URL: `http://your-domain.com/user/login.php`
- 可以注册新用户或使用现有账户

### 3. 测试API
```bash
# 首先注册一个用户，获取API令牌
# 然后测试API端点
curl "http://your-domain.com/api/config/USER_ID?token=API_TOKEN"
```

### 4. 功能测试清单
- [ ] 管理员登录
- [ ] 用户注册/登录
- [ ] 币种管理
- [ ] 自定义字段配置
- [ ] 用户配置创建
- [ ] API端点访问
- [ ] 日志记录

## 🔒 安全建议

### 安装完成后的安全措施
1. **删除安装文件**
   ```bash
   rm install.php
   rm test_install.php
   ```

2. **设置合适的文件权限**
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod 600 config/database.php
   ```

3. **配置Web服务器安全头**
   - 参考项目中的 `nginx.conf.example`
   - 启用HTTPS
   - 配置防火墙

4. **定期备份**
   - 数据库备份
   - 代码备份
   - 配置文件备份

## 📞 获取支持

如果在安装过程中遇到问题：

1. 首先查看本文档的常见问题部分
2. 检查系统日志文件
3. 确认环境要求是否满足
4. 如问题仍未解决，请提供：
   - 详细的错误信息
   - 服务器环境信息
   - 安装步骤描述

---

**祝您安装顺利！** 🎉