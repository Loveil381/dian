# 魔女小店 (Witch Store)

轻量级原生 PHP + MySQL 在线商城系统，零依赖，极简部署。针对个人小商家，解决低成本快速建站卖货需求。

## 功能列表
- 商品管理 (CRUD, SKU支持)
- 订单管理
- 分类管理 (支持 emoji 标识)
- 用户注册与登录
- 前台展示与购物体验
- 简单库存管理
- 安全防护 (CSRF, 密码哈希加密)

## 环境要求
- PHP 8.1+
- MySQL 5.6+或等效版本

## 安装步骤
1. **复制配置文件**：将 `config/database.php.example` 复制或重命名为 `config/database.php`，并填写正确的数据库信息。
2. **导入数据库**：将 `database/schema.sql` 导入你的 MySQL 数据库中以建立基础表结构。或者使用内置的自动安装流。
3. **访问引导**：在浏览器中访问商城首页。如果没有完成配置或数据库建立，系统会自动跳转到引导安装页面。

## 目录结构说明
- `admin/` — 后台管理 (将进行MVC结构重构)
- `assets/` — 静态资源如 CSS, JS，以及 `uploads/` 用户上传的图片。
- `config/` — 数据库等敏感配置文件 (.gitignore忽略)。
- `data/` — 数据库的数据层隔离封装。
- `database/` — 放置 `schema.sql` 库结构与迁移历史脚本。
- `docs/` — 包含AI CTO记忆数据和相关文档。
- `includes/` — 认证、CSRF与基础工具函数。
- `templates/` — 前台的主题与视图页面。

## 开发说明
本项目不使用现代框架 (如 Laravel/ThinkPHP) 和包管理 (Composer)，以追求最致精简与极低服务器门槛（甚至廉价虚拟机也可稳定运行）。代码风格为现代原生 PHP，严格类型，PDO 预处理交互数据。

提交代码请遵循 `AGENTS.md` 的规范，包括使用中文注释、预处理等要求，并按规范提供 Git Commit。

## 许可证
MIT License
