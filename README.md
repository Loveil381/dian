# 魔女小店

轻量级原生 PHP 在线商城，适合部署在普通 Apache + MySQL 环境，无需 Composer 和前端构建工具。

## 环境要求

- PHP 8.1+
- MySQL 5.6+
- Apache，启用 `mod_rewrite` 与 `mod_headers`
- 允许 PHP 写入 `config/` 与 `assets/uploads/` 目录

## 主要能力

- 商品、分类、订单、用户后台管理
- 前台购物车、统一结算、订单详情
- 密码找回、邮件发送降级兜底
- 后台筛选、分页、批量操作
- 基础移动端适配与前台 AJAX 搜索

## 安装步骤

1. 将项目上传到站点根目录。
2. 确保 `config/` 目录可写，`assets/uploads/` 目录可写。
3. 访问 `/install.php`，填写数据库连接信息、表前缀，以及首个管理员用户名和密码。
4. 安装向导会自动生成 `config/database.php`，执行 `database/schema.sql` 初始化表结构，并创建一条 `super_admin` 管理员记录。
5. 安装成功后会生成 `config/installed.lock`，后续再次访问安装页会提示“已安装”。

## 首次登录后台

- 安装成功后访问 `index.php?page=admin_login`
- 使用安装向导中填写的管理员用户名和密码登录
- 首次进入后台后建议立即检查支付配置、站点设置与商品分类

## 生产配置

- 数据库配置模板见 `config/database.example.php`
- 邮件配置模板见 `config/mail.example.php`
- 建议通过环境变量注入数据库和 SMTP 凭证，不要将真实配置提交到仓库

## 目录权限说明

- `config/`：安装阶段需要写入
- `assets/uploads/`：上传图片需要写入
- `docs/`、`data/`、`includes/`、`config/`：生产环境建议禁止 Web 直接访问，项目已通过根目录 `.htaccess` 添加基础保护

## 部署建议

- 首次上线前先访问 `install.php` 完成初始化
- Apache 站点根目录指向项目根目录
- 保留根目录 `.htaccess`，用于安全头与敏感目录访问限制
- 若迁移到 Nginx，需要将 `.htaccess` 规则改写为对应配置
