# 币种管理系统

一个专业的数字货币参数配置管理平台，支持多币种动态配置和API对接。

## ✨ 功能特色

- 🎨 **科技感界面** - 深色主题，流光动画，现代化UI设计
- 🛠️ **灵活配置** - 支持JSON模板和占位符系统
- 🔐 **安全认证** - 完整的用户权限管理
- 📱 **响应式设计** - 完美适配各种设备
- 🚀 **高性能** - 优化的数据库查询和缓存机制
- 🔧 **易于部署** - 一键安装，支持宝塔/小皮面板

## 🏗️ 系统架构

```
币种管理系统
├── 管理后台 (admin/)
│   ├── 币种管理 - 添加/编辑币种和全局模板
│   ├── 自定义字段 - 配置字段类型和占位符
│   ├── 用户管理 - 管理注册用户
│   └── 系统监控 - 查看统计和日志
├── 用户系统 (user/)
│   ├── 用户注册/登录
│   ├── 币种配置 - 动态表单填写
│   ├── API文档 - 查看访问令牌和端点
│   └── 配置管理 - 查看和编辑配置
└── API接口 (api/)
    ├── 用户认证接口
    ├── 币种管理接口
    ├── 配置管理接口
    └── 外部API配置获取
```

## 📋 系统要求

- **PHP**: 7.4 或更高版本
- **MySQL**: 5.7 或更高版本
- **Web服务器**: Apache 2.4+ 或 Nginx 1.18+
- **PHP扩展**: PDO, PDO_MySQL, JSON, OpenSSL, Session
- **磁盘空间**: 至少 50MB

## 🚀 安装部署

### 方法一：一键安装（推荐）

1. 下载项目文件到Web目录
2. 访问 `http://your-domain.com/install.php`
3. 按照安装向导完成配置
4. 删除 `install.php` 文件

### 方法二：手动安装

1. **下载源码**
```bash
git clone https://github.com/your-repo/coin-management-system.git
cd coin-management-system
```

2. **配置数据库**
```bash
# 创建数据库
mysql -u root -p
CREATE DATABASE coin_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 导入表结构
mysql -u root -p coin_management < database/schema.sql
```

3. **配置文件**
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'coin_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

4. **设置权限**
```bash
chmod 755 config/
chmod 644 config/database.php
```

## 📖 使用指南

### 管理员操作

1. **登录管理后台**
   - 访问: `admin/login.php`
   - 默认账户: `admin` / `admin123`

2. **添加币种**
   ```json
   {
     "name": "Bitcoin",
     "symbol": "BTC",
     "pool_url": "{{POOL_URL}}",
     "wallet_address": "{{WALLET}}",
     "worker_name": "{{WORKER}}"
   }
   ```

3. **配置自定义字段**
   - 标题: 钱包地址
   - 类型: 文本框
   - 占位符: WALLET
   - 是否必填: 是

### 用户操作

1. **注册账户**
   - 访问: `user/login.php`
   - 填写用户名、邮箱、密码

2. **配置币种**
   - 选择币种 → 填写参数 → 保存配置

3. **获取API配置**
   ```bash
   curl "https://your-domain.com/api/config/USER_ID?token=API_TOKEN"
   ```

## 🔧 API文档

### 获取用户配置

**请求**
```http
GET /api/config/{user_id}?token={api_token}
```

**响应**
```json
{
  "status": "success",
  "message": "配置获取成功",
  "data": {
    "user_id": "1",
    "configs": [
      {
        "coin": {
          "id": 1,
          "name": "Bitcoin",
          "symbol": "BTC"
        },
        "config": {
          "pool_url": "stratum+tcp://pool.example.com:4444",
          "wallet_address": "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa",
          "worker_name": "worker001"
        },
        "updated_at": "2024-01-01 12:00:00"
      }
    ],
    "total": 1,
    "generated_at": "2024-01-01 12:00:00"
  }
}
```

### 认证接口

**用户登录**
```http
POST /api/auth/login
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}
```

**用户注册**
```http
POST /api/auth/register
Content-Type: application/json

{
  "username": "new_user",
  "email": "user@example.com",
  "password": "secure_password"
}
```

## 🎨 界面预览

### 管理后台
- 🌟 科技感仪表盘
- 📊 实时数据统计
- 🎯 直观的操作界面
- 📱 完美适配移动端

### 用户端
- 🚀 现代化设计
- ⚡ 流畅的动画效果
- 🔧 简洁的配置流程
- 📋 详细的API文档

## 🔒 安全特性

- **密码加密**: 使用 PHP password_hash()
- **SQL注入防护**: PDO预处理语句
- **XSS防护**: 输入数据过滤和转义
- **CSRF防护**: 会话令牌验证
- **API令牌**: 64位随机令牌认证

## 🛠️ 开发者指南

### 目录结构
```
coin-management-system/
├── admin/              # 管理后台
├── user/               # 用户端
├── api/                # API接口
├── assets/             # 静态资源
│   ├── css/
│   ├── js/
│   └── images/
├── config/             # 配置文件
├── database/           # 数据库文件
├── includes/           # 核心函数库
└── logs/              # 日志文件
```

### 核心类库

**Database** - 数据库操作类
```php
$db = Database::getInstance();
$users = $db->fetchAll("SELECT * FROM users WHERE status = ?", [1]);
```

**Security** - 安全工具类
```php
$hash = Security::hashPassword($password);
$token = Security::generateToken();
```

**TemplateEngine** - 模板引擎
```php
$config = TemplateEngine::replacePlaceholders($template, $values);
```

### 添加新功能

1. **创建API端点**
```php
// api/new_feature.php
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
}
```

2. **添加路由规则**
```php
// api/index.php
$routes = [
    'GET /new-feature' => 'new_feature.php',
    // ...
];
```

## 🐛 故障排除

### 常见问题

**1. 数据库连接失败**
- 检查数据库配置信息
- 确认数据库服务运行状态
- 验证用户权限

**2. 安装时权限错误**
```bash
chmod 755 config/
chown www-data:www-data config/
```

**3. API返回500错误**
- 检查PHP错误日志
- 确认所有扩展已安装
- 验证数据库表结构

**4. 前端样式异常**
- 检查CSS文件路径
- 确认CDN资源可访问
- 清除浏览器缓存

### 日志查看
```bash
# 应用日志
tail -f logs/app.log

# PHP错误日志
tail -f /var/log/php_errors.log

# Web服务器日志
tail -f /var/log/apache2/error.log
```

## 🤝 贡献指南

我们欢迎任何形式的贡献！

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情。

## 📞 支持

- 📧 Email: support@example.com
- 🐛 Issues: [GitHub Issues](https://github.com/your-repo/coin-management-system/issues)
- 📖 Wiki: [项目文档](https://github.com/your-repo/coin-management-system/wiki)

## 🚀 更新日志

### v1.0.0 (2024-01-01)
- ✨ 初始版本发布
- 🎨 科技感界面设计
- 🔧 完整的币种管理功能
- 📱 响应式布局适配
- 🔐 安全认证系统
- 📚 完整的API文档

---

**⭐ 如果这个项目对您有帮助，请给我们一个星标！**