# 币种配置管理系统

一个基于PHP的现代化币种配置管理系统，采用科技感UI设计，支持动态字段配置和API接口。

## 系统特性

- 🎨 **科技感UI设计** - 深色主题、流光动画、现代化界面
- 🔧 **灵活配置** - 支持自定义币种模板和动态字段
- 🔐 **安全认证** - 用户注册登录、API令牌验证
- 📡 **API接口** - RESTful API提供配置数据
- 💾 **数据管理** - 完整的CRUD操作
- 📱 **响应式设计** - 支持各种设备访问

## 系统要求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache/Nginx Web服务器
- 支持PDO扩展

## 快速安装

### 1. 下载项目

```bash
git clone https://github.com/your-repo/crypto-config-system.git
cd crypto-config-system
```

### 2. 导入数据库

```bash
mysql -u root -p < database/schema.sql
```

### 3. 配置数据库连接

编辑 `config/config.php` 文件：

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'crypto_config');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. 设置Web服务器

#### Apache配置
```apache
<VirtualHost *:80>
    ServerName crypto.local
    DocumentRoot /path/to/crypto-config-system
    
    <Directory /path/to/crypto-config-system>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx配置
```nginx
server {
    listen 80;
    server_name crypto.local;
    root /path/to/crypto-config-system;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 宝塔面板部署

1. 在宝塔面板创建网站
2. 上传项目文件到网站根目录
3. 创建MySQL数据库并导入 `database/schema.sql`
4. 修改 `config/config.php` 中的数据库配置
5. 设置运行目录为网站根目录
6. PHP版本选择7.4或更高

## 默认账户

管理员账户需要手动创建，运行以下SQL：

```sql
INSERT INTO users (username, email, password, is_admin) 
VALUES ('admin', 'admin@example.com', '$2y$10$YourHashedPasswordHere', 1);
```

生成密码哈希：
```php
echo password_hash('your_password', PASSWORD_DEFAULT);
```

## 目录结构

```
crypto-config-system/
├── admin/              # 管理后台
│   ├── index.php      # 仪表板
│   ├── currencies.php # 币种管理
│   ├── fields.php     # 字段管理
│   └── login.php      # 管理员登录
├── api/               # API接口
│   └── config.php     # 配置API端点
├── assets/            # 静态资源
├── config/            # 配置文件
│   └── config.php     # 系统配置
├── database/          # 数据库
│   └── schema.sql     # 数据库结构
├── includes/          # 核心类库
│   └── Database.php   # 数据库操作类
├── user/              # 用户端
│   ├── register.php   # 用户注册
│   ├── login.php      # 用户登录
│   └── dashboard.php  # 用户仪表板
└── index.php          # 首页
```

## API使用

### 获取用户配置

**端点：** `GET /api/config.php`

**参数：**
- `user_id` - 用户ID
- `token` - API令牌

**示例请求：**
```bash
curl "https://your-domain.com/api/config.php?user_id=1&token=your_api_token"
```

**响应示例：**
```json
{
    "success": true,
    "user_id": 1,
    "configurations": [
        {
            "currency": {
                "name": "Bitcoin",
                "symbol": "BTC"
            },
            "config": {
                "network": "mainnet",
                "wallet": "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa",
                "api_endpoint": "https://api.bitcoin.com"
            },
            "created_at": "2024-01-01 12:00:00"
        }
    ]
}
```

## 使用流程

### 管理员操作

1. 登录管理后台
2. 添加币种并设置模板参数（使用 `{{变量名}}` 作为占位符）
3. 为每个币种添加自定义字段
4. 设置字段的类型、占位符变量、是否必填等

### 用户操作

1. 注册账户
2. 登录系统
3. 选择币种
4. 填写该币种的自定义字段
5. 系统自动生成配置并提供API访问

## 安全建议

1. 修改默认的 `SECRET_KEY`
2. 使用HTTPS协议
3. 定期备份数据库
4. 限制API请求频率
5. 使用强密码策略

## 技术栈

- **后端：** PHP 7.4+, PDO
- **数据库：** MySQL 5.7+
- **前端：** Bootstrap 5, Bootstrap Icons
- **样式：** 自定义CSS动画效果

## 许可证

MIT License

## 支持

如有问题，请提交Issue或联系技术支持。