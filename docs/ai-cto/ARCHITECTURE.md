# 架构说明

最近更新时间：2026-04-02
当前轮次：#18

## 总体结构

当前项目采用“单入口路由 + 前后台分层 + 公共工具层”的轻量架构：

- `index.php`
说明：前台统一路由入口，负责按 `page` 参数分发到 `templates/`

- `templates/`
说明：前台页面模板层，承载首页、商品、购物车、结算、订单、用户、错误页等展示逻辑

- `admin/controllers/`
说明：后台 POST 行为入口，集中处理管理动作与状态变更

- `admin/views/`
说明：后台展示层，负责列表、表单、筛选、分页与管理界面输出

- `admin/includes/`
说明：后台专用辅助函数与视图辅助逻辑

## 数据层

- `data/products.php`
说明：商城核心领域函数，负责商品、分类、订单、用户等数据标准化与读写辅助

- `includes/db.php`
说明：数据库连接与前缀配置入口，项目统一使用 `get_db_connection()`

## 安全层

- `includes/csrf.php`
说明：提供 `csrf_field()` 和 `csrf_verify()`，用于所有 POST 表单与处理入口

- `includes/error_handler.php`
说明：统一 403 / 404 / 500 友好错误页输出

## 工具层

- `includes/mailer.php`
说明：双模邮件发送，优先 SMTP，失败时回退 `mail()`

- `includes/logger.php`
说明：统一日志写入 `logs/app.log`，失败时回退 `error_log()`

## 配置层

- `config/database.php`
说明：实际数据库配置文件，部署后生成，不纳入版本控制

- `config/mail.example.php`
说明：邮件配置模板，说明 SMTP 所需环境变量与替代方式

## 部署层

- `install.php`
说明：安装向导，负责生成数据库配置、执行建表、初始化管理员

- `database/schema.sql`
说明：完整建表脚本，安装时按 `{prefix}` 占位符替换执行

- `.htaccess`
说明：Apache 安全规则、敏感目录保护、上传目录执行限制与安全头

## 请求流转

1. 访客请求进入 `index.php`
2. 路由分发到 `templates/` 或后台入口
3. 页面层调用 `data/products.php` 与 `includes/` 公共能力
4. 数据访问统一经过 `get_db_connection()`
5. 表单提交统一经过 CSRF 校验
6. 邮件、日志、错误页等横切能力统一由 `includes/` 承担

## 当前结论

项目已经形成清晰的目录边界与职责边界，代码层面达到可上线、可交接、可持续维护的最终架构状态。
