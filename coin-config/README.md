<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Coin Config API (Laravel 8 skeleton)

This project provides a PHP/Laravel 8 backend for managing coins, fields and per-user configuration templates with placeholder replacement and public API output.

## Requirements
- PHP 7.4+ with extensions: mbstring, tokenizer, json, xml, curl, zip, pdo_mysql
- MySQL 5.7+

If OS package manager is unavailable, you can run via Docker or your panel environment (BT/AApanel). Point webroot to `public/`.

## Setup
1. Copy `.env.example` to `.env` and set DB credentials
2. Generate key: `php artisan key:generate`
3. Run migrations: `php artisan migrate`

## API
- POST `/api/auth/register` `{ email, password }`
- POST `/api/auth/login` `{ email, password }`
- GET  `/api/coins` (token required)
- GET  `/api/coins/{coin_id}/fields` (token required)
- POST `/api/coins/{coin_id}/values` body `{ values: [{ field_id, value }] }` (token)
- GET  `/api/me/config?coin_id=` (token)
- GET  `/api/config/{user_id}?token=...&coin_id=...` (public with token)
- Admin: `/api/admin/*` requires token of an `admin` role user

## Notes
- Tokens are plain random values stored hashed in DB (`users.api_token_hash`).
- Admin can set user role and default coin:
  - PUT `/api/admin/users/{user_id}/role` `{ role: "admin"|"user" }`
  - PUT `/api/admin/users/{user_id}/default-coin` `{ default_coin_id: 1 }`

## 一次性部署步骤（Docker 推荐）

1. 解压后进入目录，执行：
   - `docker compose up -d --build`
2. 等待初始化（自动 composer install / key generate / migrate / seed）。
3. 管理员种子 Token（默认，可通过环境变量 ADMIN_SEED_TOKEN 覆盖）：
   - 文件位置：`storage/logs/seeded_admin_token.txt`
4. 验证：
   - 健康检查：GET `http://localhost:8080/api/healthz`
   - 币种列表（需 Token）：GET `http://localhost:8080/api/coins`
   - 公共配置：GET `http://localhost:8080/api/config/{user_id}?token={token}&coin_id={coin_id}`

若使用面板（宝塔/小皮）：
- 站点根指向 `public/`
- `.env` 配置数据库后：
  - `php artisan key:generate`
  - `php artisan migrate --force`
  - `php artisan db:seed --force`（生成管理员 Token 文件）
