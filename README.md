# 魔女的小店

轻量级原生 PHP 在线商城，无框架、无 Composer 依赖，可部署在任意支持 PHP 8.1+ 与 MySQL 的虚拟主机。

**当前版本：v1.5.1**

---

## 功能

**前台（客户端）**
- 商品列表与分类筛选（pill 标签）、关键字搜索
- 首页品类快捷入口，商品详情页 SKU 选择 + 数量选择器
- 购物车（数量调整、合计实时更新）
- 结算与下单（事务保护库存扣减，服务端价格校验）
- 优惠券码输入（满减 / 折扣，结算二次验证）
- 订单列表与详情、物流信息展示
- 用户注册、登录、修改资料、找回密码（邮件 Token）
- 在线咨询气泡（可后台配置微信号 / 二维码）

**后台（管理端）**
- 商品管理：新增 / 编辑 / 上下架 / 批量操作 / 排序 / 图片上传
- 分类管理：增删改，重命名自动同步商品表
- 订单管理：状态流转（待付款 → 已付款 → 已发货 → 已完成）、快递信息录入
- 用户管理：封禁 / 恢复，分页列表
- 库存看板：低库存 / 零库存预警
- 销售数据看板：KPI 卡片、7 天趋势图（纯 CSS）、热销排行 Top 10、状态分布
- 优惠券系统：CRUD、有效期、使用次数限制、原子防并发超限
- 操作日志：全后台写操作记录，支持筛选分页
- 订单通知：邮件通知引擎，5 个独立开关（下单 / 付款 / 发货 / 完成 / 管理员）
- 支付设置：上传收款二维码（微信 / 支付宝）
- 更新中心：一键升级（备份 → 下载 → 部署 → 迁移 → 健康检查，失败自动回滚）

**安全**
- 全站 CSRF 保护、bcrypt 密码哈希、PDO 预处理语句、XSS 转义
- 上传文件 finfo MIME 检测、管理后台登录限流（5 次失败锁定 15 分钟）
- 安全响应头（CSP / X-Frame-Options / Referrer-Policy / Permissions-Policy）

---

## 环境要求

| 依赖 | 版本 |
|------|------|
| PHP | 8.1+ |
| MySQL | 5.6+ / MariaDB 10.3+ |
| Web 服务器 | Apache（`mod_rewrite` + `mod_headers`）或 Nginx |
| PHP 扩展 | `pdo_mysql`、`mbstring`、`fileinfo`、`zip`（更新中心需要） |

---

## 安装

1. 下载最新 Release 并上传到主机网站根目录解压
2. 确保以下目录对 Web 服务可写：
   ```
   config/
   assets/uploads/
   logs/
   storage/
   ```
3. 浏览器访问 `https://your-domain/install.php`
4. 填写数据库连接信息，创建管理员账号，点击安装
5. 安装向导会自动生成 `config/database.php` 与 `config/installed.lock`

---

## 升级

已有站点：后台 → **更新中心** → 检查更新 → 一键升级

更新中心会自动完成：备份 → 下载新版 → 部署文件 → 执行数据库迁移 → 健康检查。
任何步骤失败均自动从备份回滚。

---

## 目录结构

```
├── actions/          # 前台表单处理（PRG 模式）
├── admin/            # 后台管理
│   ├── controllers/  # 后台 Action 处理
│   ├── data_loaders/ # 按需数据加载
│   └── views/        # 后台页面模板
├── assets/
│   ├── css/          # 样式（design-tokens / components / site / admin / mobile）
│   ├── js/           # 前端脚本
│   └── uploads/      # 上传图片（运行时生成）
├── config/           # 配置文件（数据库、邮件）
├── data/             # 数据访问层
├── database/         # SQL Schema 与迁移脚本
├── includes/         # 核心工具（db / csrf / mailer / updater 等）
└── templates/        # 前台页面模板
```

---

## 配置

| 文件 | 说明 |
|------|------|
| `config/database.example.php` | 数据库配置模板 |
| `config/mail.example.php` | 邮件（SMTP）配置模板 |

邮件配置用于找回密码与订单通知，支持任意 SMTP 服务商（QQ 邮箱、阿里云等）。
