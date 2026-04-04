# 魔女小店

轻量级原生 PHP 在线商城，无框架、无 Composer 依赖，可部署在任意支持 PHP 8.1+ 与 MySQL 的虚拟主机。

**版本：v1.1.0**

---

## 功能

**前台（客户端）**
- 商品列表与分类筛选、关键字搜索
- 商品详情页，固定底部操作栏（加入购物车 / 立即购买）
- 购物车（± 数量调整，自动提交）
- 结算与下单（事务保护库存扣减）
- 订单列表（状态过滤：待付款 / 待收货 / 已完成）、订单详情与物流信息展示
- 用户注册、登录、修改资料、找回密码（邮件 Token）

**后台（管理端）**
- 商品管理：新增 / 编辑 / 上下架 / 批量操作 / 排序
- 分类管理：增删改，重命名自动同步商品表
- 订单管理：状态流转（待付款→待发货→已发货→已完成）、录入快递信息
- 用户管理：封禁 / 恢复，统计卡片
- 库存看板：低库存 / 零库存预警 KPI 卡片
- 支付设置：上传收款二维码（微信 / 支付宝）
- 安全：全站 CSRF 保护、bcrypt 密码、PDO 预处理语句、XSS 转义

---

## 环境要求

| 依赖 | 版本 |
|------|------|
| PHP | 8.1+ |
| MySQL | 5.6+ |
| Web 服务器 | Apache（`mod_rewrite` + `mod_headers`）或 Nginx |
| PHP 扩展 | `pdo_mysql`、`mbstring`、`fileinfo` |

---

## 安装

1. 上传项目代码到主机网站根目录
2. 确保以下目录对 Web 服务可写：
   ```
   config/
   assets/uploads/
   logs/
   ```
3. 浏览器访问 `https://your-domain/install.php`
4. 填写数据库连接信息，创建管理员账号，点击安装
5. 安装完成后**删除或重命名 `install.php`**

安装向导会自动生成 `config/database.php` 与 `config/installed.lock`。

---

## 配置

| 文件 | 说明 |
|------|------|
| `config/database.example.php` | 数据库配置模板 |
| `config/mail.example.php` | 邮件（SMTP）配置模板 |

邮件配置用于找回密码功能，支持任意 SMTP 服务商（QQ 邮箱、阿里云等）。

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
├── data/             # 数据访问层（products / categories / orders / users）
├── database/         # SQL Schema 与迁移脚本
├── includes/         # 核心工具（db / csrf / mailer / pagination 等）
└── templates/        # 前台页面模板
```

---

## 开发与协作

分支规范：

```
master          ← 稳定分支，只合并经过测试的代码
feature/xxx     ← 新功能开发
fix/xxx         ← Bug 修复
```

代码规范见 `CONTRIBUTING.md`。
