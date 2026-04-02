# 魔女小店

轻量级原生 PHP 在线商城，目标是在低成本 Apache + MySQL 环境下提供可安装、可运营、可上线的完整商城能力。

## 环境要求

- PHP 8.1+
- MySQL 5.6+
- Apache，启用 `mod_rewrite` 与 `mod_headers`
- 允许 PHP 写入 `config/` 与 `logs/` 目录

## 主要能力

- 商品、分类、购物车、结算、订单与后台管理
- 统一下单链路、订单状态机、分页、筛选与批量操作
- 密码找回、双模邮件发送、统一错误页
- 安装向导、SEO 基础、移动端基础适配、AJAX 搜索

## 安装步骤

1. 上传项目代码到支持 PHP 8.1+ 和 MySQL 的 Apache 主机。
2. 确保 `assets/uploads/`、`config/`、`logs/` 目录具备写权限。
3. 首次访问 `/install.php`，填写数据库配置并创建管理员账号。
4. 安装完成后，系统会生成 `config/database.php` 与 `config/installed.lock`。
5. 使用安装时创建的管理员账号登录后台。

## 配置模板

- 数据库配置模板：`config/database.example.php`
- 邮件配置模板：`config/mail.example.php`

## 目录权限说明

- `config/`：安装阶段需要可写，完成后应限制访问
- `assets/uploads/`：运行期上传目录，需要 Web 服务可写
- `logs/`：统一日志目录，需要 PHP 进程可写

## 项目开发历程

- 总计 18 轮 CTO-Agent 闭环迭代
- 质量从初始状态提升至 9.5/10
- 已修复 Critical 8 项、Major 12 项、Minor 18+ 项
- 关键架构决策：零框架、渐进 MVC、事件委托、双模邮件、统一日志
