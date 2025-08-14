# XMRig Web管理系统

一个功能完整的XMRig矿工配置Web管理系统，支持多币种、多用户管理，具有图形化界面，易于在宝塔面板部署。

## 🌟 功能特点

### 用户端功能
- ✅ 用户注册/登录（带验证码）
- ✅ 配置多个矿工
- ✅ 自定义钱包地址
- ✅ 设置进程/窗口暂停列表
- ✅ 一键生成配置文件
- ✅ 查看挖矿状态

### 管理员功能
- ✅ 币种管理（添加/编辑/删除）
- ✅ 动态字段配置
- ✅ 用户管理
- ✅ 配置审核
- ✅ 登录日志查看
- ✅ 系统统计

### 系统特性
- ✅ 全中文界面
- ✅ 响应式设计
- ✅ 安全验证码
- ✅ 密码加密存储
- ✅ 会话管理
- ✅ CSRF保护

## 📦 系统要求

- PHP >= 7.4
- MySQL >= 5.7 或 MariaDB >= 10.3
- PHP扩展：PDO、GD、JSON、Session
- Web服务器：Apache/Nginx

## 🚀 宝塔面板安装步骤

### 1. 创建网站

1. 登录宝塔面板
2. 点击"网站" -> "添加站点"
3. 输入域名（例如：xmrig.yourdomain.com）
4. 选择PHP版本（建议7.4或8.0）
5. 选择MySQL
6. 点击"提交"

### 2. 上传文件

1. 将 `web-manager` 文件夹内的所有文件上传到网站根目录
2. 设置文件权限：
```bash
chmod -R 755 /www/wwwroot/xmrig.yourdomain.com
chmod -R 777 /www/wwwroot/xmrig.yourdomain.com/assets/fonts
```

### 3. 创建数据库

1. 在宝塔面板中点击"数据库"
2. 创建数据库（记住数据库名、用户名、密码）
3. 点击"管理"进入phpMyAdmin
4. 导入 `install.sql` 文件

### 4. 配置系统

1. 编辑 `config.php` 文件：
```php
define('DB_HOST', 'localhost');        // 数据库主机
define('DB_NAME', 'your_db_name');     // 数据库名
define('DB_USER', 'your_db_user');     // 数据库用户名
define('DB_PASS', 'your_db_pass');     // 数据库密码
define('SITE_URL', 'http://xmrig.yourdomain.com'); // 网站URL
```

### 5. 安装字体（验证码需要）

1. 下载Arial字体文件
2. 上传到 `assets/fonts/` 目录
3. 重命名为 `arial.ttf`

或者修改 `captcha.php` 使用系统字体：
```php
// 将这行
__DIR__ . '/assets/fonts/arial.ttf'
// 改为
'/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf'
```

### 6. 配置伪静态（Nginx）

在宝塔面板网站设置中添加伪静态规则：
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/tmp/php-cgi.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## 📱 使用说明

### 默认管理员账号
- 用户名：admin
- 密码：admin123
- **请立即修改默认密码！**

### 管理员操作流程

1. **登录管理后台**
   - 访问：http://your-domain.com/login.php
   - 使用管理员账号登录

2. **添加币种**
   - 进入"币种管理"
   - 点击"添加币种"
   - 填写币种信息和公共配置

3. **配置字段**
   - 选择币种
   - 添加用户需要填写的字段
   - 设置字段类型（文本/数字/下拉/文本域）

### 用户操作流程

1. **注册账号**
   - 访问注册页面
   - 填写用户名、邮箱、密码
   - 输入验证码完成注册

2. **创建配置**
   - 登录后选择币种
   - 填写钱包地址等信息
   - 保存配置

3. **获取配置**
   - 点击"查看配置"
   - 复制配置URL或下载配置文件

## 🔧 XMRig集成

### 修改XMRig获取配置

在XMRig程序中添加配置获取：
```bash
# 通过用户名和矿工名获取配置
./xmrig --config-url "http://your-domain.com/api/get_config.php?user=username&worker=worker1"
```

### API接口说明

**获取配置接口**
```
GET /api/get_config.php
参数：
  user: 用户名
  worker: 矿工名称
  token: API令牌（可选）

返回：JSON格式的完整配置
```

## 🔐 安全建议

1. **修改默认密码**
   - 立即修改admin账号密码
   - 使用强密码策略

2. **启用HTTPS**
   - 在宝塔面板申请SSL证书
   - 强制HTTPS访问

3. **限制访问**
   - 设置IP白名单
   - 启用防火墙规则

4. **定期备份**
   - 定期备份数据库
   - 备份用户配置

5. **监控日志**
   - 定期查看登录日志
   - 监控异常访问

## 📊 数据库结构

- `users` - 用户表
- `coins` - 币种表
- `coin_fields` - 币种字段配置
- `user_configs` - 用户配置
- `login_logs` - 登录日志

## 🛠️ 故障排除

### 验证码不显示
- 检查GD库是否安装
- 检查字体文件是否存在
- 查看PHP错误日志

### 数据库连接失败
- 检查数据库配置
- 确认数据库服务运行
- 检查用户权限

### 登录后立即退出
- 检查session配置
- 确认session目录可写
- 检查cookie设置

## 📝 更新日志

### v1.0.0 (2024-01)
- 初始版本发布
- 基础功能实现
- 支持XMR币种

## 🤝 技术支持

如遇到问题，请检查：
1. PHP错误日志
2. MySQL错误日志
3. Web服务器错误日志

## 📄 许可证

本项目基于MIT许可证开源。

## ⚠️ 免责声明

本系统仅供学习和研究使用，使用者需遵守当地法律法规。作者不对使用本系统产生的任何后果负责。

---

**重要提示：**
1. 请确保您有合法的挖矿权限
2. 遵守当地的法律法规
3. 合理使用系统资源
4. 保护用户隐私数据