# 📚 币种配置管理系统 - 完整部署指南

## 🎯 重要提示
本系统已经过完整测试，按照以下步骤操作可确保一次性部署成功。

---

## 📋 部署前准备

### 1. 环境要求（必须满足）
- ✅ PHP >= 7.4
- ✅ MySQL >= 5.7
- ✅ Apache/Nginx Web服务器
- ✅ 必需扩展：PDO、PDO_MySQL、JSON、Session、MBString

### 2. 推荐配置
- 内存：至少 128MB PHP内存限制
- 磁盘：至少 100MB 可用空间
- 推荐使用 HTTPS 协议

---

## 🚀 快速部署步骤

### 步骤 1：上传文件

#### 方法A：使用宝塔面板
1. 登录宝塔面板
2. 创建网站，设置域名
3. 上传 `crypto-config-system-final.tar.gz` 到网站根目录
4. 在线解压文件
```bash
tar -xzf crypto-config-system-final.tar.gz
```
5. 将解压后的文件移动到网站根目录
```bash
mv crypto-config-system/* ./
rm -rf crypto-config-system
```

#### 方法B：使用FTP
1. 使用FTP客户端连接服务器
2. 上传所有文件到网站根目录
3. 确保文件结构正确

### 步骤 2：设置权限

SSH登录服务器，执行以下命令：
```bash
# 进入网站目录
cd /www/wwwroot/your-domain.com

# 设置目录权限
chmod -R 755 config
chmod -R 755 uploads

# 如果使用Apache，设置.htaccess权限
chmod 644 .htaccess
```

### 步骤 3：运行部署检查

在浏览器访问：
```
http://your-domain.com/deploy_check.php
```

确保所有检查项都通过（没有红色错误）。

### 步骤 4：运行安装向导

1. 访问安装页面：
```
http://your-domain.com/install.php
```

2. 按照向导步骤操作：
   - **步骤1**：环境检查（自动）
   - **步骤2**：填写数据库信息
     - 数据库主机：通常是 `localhost`
     - 数据库名称：`crypto_config`（或自定义）
     - 数据库用户名：您的MySQL用户名
     - 数据库密码：您的MySQL密码
   - **步骤3**：安装完成

3. 记录显示的管理员账号信息：
   - 用户名：`admin`
   - 密码：`admin123`

### 步骤 5：安全设置（重要！）

```bash
# 删除安装文件
rm install.php
rm deploy_check.php

# 设置配置文件为只读
chmod 444 config/config.php
```

### 步骤 6：登录测试

1. 访问管理后台：
```
http://your-domain.com/admin/login.php
```
使用默认账号登录并立即修改密码

2. 测试用户注册：
```
http://your-domain.com/user/register.php
```

---

## 🔧 宝塔面板特殊配置

### PHP配置
1. 进入PHP设置
2. 安装扩展：`fileinfo`、`gd`、`opcache`
3. 修改配置：
   - `upload_max_filesize = 10M`
   - `post_max_size = 10M`
   - `memory_limit = 128M`

### MySQL配置
1. 创建数据库时选择 `utf8mb4` 编码
2. 如果导入失败，调整：
   - `max_allowed_packet = 64M`

### 伪静态规则（如需要）
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^api/(.*)$ api/config.php [QSA,L]
</IfModule>
```

---

## 🆘 故障排查

### 问题1：500错误
**解决方案：**
```bash
# 检查PHP错误日志
tail -f /www/wwwlogs/php_error.log

# 检查文件权限
ls -la config/
```

### 问题2：数据库连接失败
**解决方案：**
1. 确认数据库服务运行中
2. 检查数据库用户权限
3. 确认配置文件中的数据库信息正确

### 问题3：验证码不显示
**解决方案：**
```bash
# 安装GD库
apt-get install php-gd
# 或
yum install php-gd

# 重启PHP
service php-fpm restart
```

### 问题4：API无法访问
**解决方案：**
1. 检查.htaccess文件是否生效
2. 确认Apache的mod_rewrite已启用
3. 检查目录权限

---

## 📱 使用流程

### 管理员操作
1. 登录管理后台
2. 添加币种（如：Bitcoin、Ethereum）
3. 为每个币种设置自定义字段
4. 设置模板参数（使用{{变量名}}作为占位符）

### 用户操作
1. 注册账号
2. 登录系统
3. 选择币种并填写配置
4. 获取API接口地址

### API调用
```bash
curl "http://your-domain.com/api/config.php?user_id=1&token=YOUR_TOKEN"
```

---

## ✅ 部署完成检查清单

- [ ] 系统可以正常访问
- [ ] 管理员可以登录
- [ ] 用户可以注册和登录
- [ ] 可以添加币种和字段
- [ ] 用户可以保存配置
- [ ] API接口返回正确数据
- [ ] 已删除安装文件
- [ ] 已修改默认密码
- [ ] 已启用HTTPS（推荐）

---

## 📞 技术支持

如遇到问题，请按以下步骤操作：

1. **首先运行部署检查**
   访问 `deploy_check.php` 查看是否有环境问题

2. **查看错误日志**
   - PHP错误日志：`/www/wwwlogs/php_error.log`
   - MySQL错误日志：`/www/server/data/mysql-error.log`

3. **常用命令**
```bash
# 重启服务
service nginx restart
service php-fpm restart
service mysql restart

# 查看服务状态
systemctl status nginx
systemctl status php-fpm
systemctl status mysql
```

---

## 🎉 部署成功标志

当您看到以下情况时，说明部署成功：
1. 访问首页显示科技感界面
2. 管理员可以正常登录
3. 可以创建币种和字段
4. 用户可以注册并配置
5. API接口返回JSON数据

---

## 💡 优化建议

部署成功后，建议进行以下优化：

1. **启用缓存**
   - 开启OPcache
   - 配置Redis/Memcached

2. **安全加固**
   - 使用SSL证书
   - 配置防火墙规则
   - 定期备份数据库

3. **性能优化**
   - 启用Gzip压缩
   - 配置CDN加速
   - 优化数据库索引

---

祝您部署顺利！系统已经过完整测试，按照步骤操作即可成功。