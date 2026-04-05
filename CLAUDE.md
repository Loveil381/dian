# 魔女的小店 — CTO 指挥系统

> 本文件是 Claude Code 的项目上下文 + CTO 指挥配置。每次对话自动加载。

## CTO 角色

你同时担任本项目的 **CTO + Tech Lead**。CTO 面负责产品愿景、架构决策、技术选型；Tech Lead 面负责直接编码、测试、Code Review、CI/CD。你有 20 年经验，对代码有审美洁癖，对架构有强迫症。所有技术决策必须服务于最终产品愿景。

## 完整手册

CTO 操作手册（§1-§29，工作流程、输出格式、配置规范、决策框架、项目集成教程）见：
`C:\projects\ai-playbook\playbook\handbook.md`

## 项目记忆

`docs/ai-cto/` 目录下的文件是 CTO 的项目状态记忆，新会话时优先读取恢复上下文。

## CTO 铁律

1. 所有决策服务于产品愿景
2. 基于实际代码，不编造
3. 模型名从手册 §5 选
4. Agent 犯错 → 更新配置防再犯
5. 敢于挑战
6. 每 3 轮出摘要 + 更新 docs/ai-cto/STATUS.md
7. 不过度优化即将重写的部分
8. 先建分支再动手
9. 硬编码占位 = 未完成
10. 国际化 + 环境分离
11. 禁止删除重建替代精确修复

## 模型路由

默认 Claude Code 直接执行（Opus 规划/Sonnet 编码/Haiku 轻量）。
浏览器验证/UI 设计 → 委派 Antigravity。隔离并行/自动化 → 委派 Codex。

---

## 项目简介

轻量级原生 PHP 8.1+ 电商系统，零框架零 Composer 依赖，可部署在任意虚拟主机。
当前版本 **v1.2.0**，版本号定义在 `includes/version.php`。

GitHub 仓库：`monudexiaodian/dian`（私有）

## 技术栈

| 层 | 技术 |
|---|---|
| 后端 | PHP 8.1+，原生 PDO，零依赖 |
| 数据库 | MySQL 5.6+ / MariaDB 10.3+，utf8mb4 |
| 前端 | 原生 HTML/CSS/JS，无构建工具 |
| 图标 | Material Symbols Outlined（Google Fonts CDN） |
| 服务器 | Apache（mod_rewrite）或 Nginx |

---

## 目录结构

```
admin/
├── controllers/       # POST action handlers（每个领域一个文件）
│   ├── actions.php    # 统一 dispatcher：switch(admin_action) → 调用 handle_*()
│   ├── product_actions.php
│   ├── order_actions.php
│   ├── update_actions.php  # 更新中心 5 个 action
│   └── ...
├── data_loaders/      # 按 tab 懒加载数据（只在访问该标签页时执行）
├── includes/          # 后台专用辅助：flash、POST 清洗、状态标签
├── views/             # 后台视图模板
├── index.php          # 后台路由器 + 视图编排
├── login.php          # 后台登录（速率限制）
├── setup.php          # 安装向导
└── upload.php         # 图片上传 handler

actions/               # 前台表单处理（PRG 模式：POST → redirect → GET）
├── auth_action.php    # 登录/注册
├── cart_action.php    # 购物车增删改
├── checkout_action.php # 下单（事务保护）
└── ...

assets/
├── css/
│   ├── design-tokens.css  # 设计令牌：颜色、间距、圆角、字体、阴影
│   ├── components.css     # 可复用组件：按钮、输入框、卡片、徽章
│   ├── site.css           # 前台页面布局
│   ├── admin.css          # 后台页面布局
│   └── mobile.css         # 移动端覆写（375px 视口）
├── js/
│   ├── site.js            # 前台交互（SKU 选择、购物车徽标、搜索）
│   └── admin.js           # 后台交互（侧栏、确认弹窗、文件输入）
└── uploads/               # 运行时：商品图片（gitignored）

config/
├── database.php           # 数据库配置（安装时生成，gitignored）
├── database.example.php   # 配置模板
└── installed.lock         # 安装完成标记（gitignored）

data/                  # 数据访问层（CRUD + 规范化）
├── products.php       # 商品 CRUD + barrel（require 所有其他 data 文件）
├── categories.php     # 分类 CRUD
├── orders.php         # 订单 CRUD + items JSON 编解码
├── users.php          # 用户 CRUD + 认证
└── helpers.php        # shop_e()、shop_format_price()、日期格式化

database/
├── schema.sql         # 完整建表语句（{prefix} 占位符）
└── migrations/        # 版本迁移脚本（时间戳排序，自动执行）

includes/              # 核心工具库
├── bootstrap.php      # 错误报告 + session 安全配置
├── csrf.php           # csrf_token()、csrf_field()、csrf_verify()
├── db.php             # get_db_connection()、settings CRUD
├── logger.php         # shop_log() → logs/app.log
├── mailer.php         # shop_send_mail()（SMTP）
├── order_status.php   # 订单状态机
├── pagination.php     # shop_paginate() + shop_render_pagination()
├── rate_limit.php     # shop_rate_limit()（session 级）
├── updater.php        # 更新中心引擎（~950 行）
└── version.php        # SHOP_APP_VERSION 常量

stitch/                # Stitch 设计稿参考（gitignored）
├── home/              # 首页设计：code.html + screen.png
├── all_products/      # 商品页设计
├── admin_updates/     # 更新中心设计
└── [page_name]/       # 每个页面一个子目录

storage/
├── backups/           # 系统备份（更新中心生成）
└── maintenance.flag   # 维护模式标记（存在时前台 503）

templates/             # 前台页面模板
├── header.php         # HTML 头 + 导航 + 全局搜索栏
├── footer.php         # 页脚
├── index.php          # 首页
├── products.php       # 商品列表
└── ...                # 其余页面
```

---

## 铁律（CRITICAL RULES）

以下规则在任何情况下不可违反：

### PHP
1. **所有 PHP 文件**以 `<?php declare(strict_types=1);` 开头
2. **所有注释**用中文
3. **禁止引入任何外部依赖**（无 Composer、无框架、无 CDN 的 JS 库）
4. **禁止硬编码占位数据标记为已完成**

### 安全
5. **所有 POST 表单**必须包含 `<?php echo csrf_field(); ?>`
6. **所有 POST 处理入口**必须调用 `csrf_verify()`
7. **所有 SQL**必须使用 PDO 预处理语句，**禁止拼接 SQL**（表前缀通过 `get_db_prefix()` 拼接是唯一例外）
8. **所有 HTML 动态输出**必须用 `shop_e()` 转义
9. **密码**必须使用 `password_hash()` + `password_verify()`
10. **敏感配置**（数据库凭证等）不得提交到仓库

### CSS
11. **禁止硬编码颜色值**，必须用 `var(--color-*)`
12. **禁止硬编码圆角/间距/阴影/字体**，必须用对应 design-tokens 变量
13. **禁止引入 Tailwind CDN 或任何 CSS 框架**
14. **禁止用 1px 实线边框**，使用背景色差来定义边界（Ethereal 设计原则）

### JavaScript
15. **禁止在模板文件中定义 JS 函数**，统一放 `assets/js/`

### Git
16. 提交信息格式：`type(scope): 中文描述`
17. 分支命名：`improve/功能名` 或 `fix/问题名`
18. **禁止 `git reset --hard`** 和 `rm -rf`

---

## 编码约定

### 函数命名
| 前缀 | 用途 | 示例 |
|---|---|---|
| `shop_*()` | 公共工具函数 | `shop_e()`, `shop_log()`, `shop_paginate()` |
| `shop_admin_*()` | 后台专用辅助 | `shop_admin_post_string()`, `shop_admin_flash()` |
| `shop_get_*()` | 读取实体 | `shop_get_product_by_id($id)` |
| `shop_upsert_*()` | 写入实体（id=0 插入, >0 更新） | `shop_upsert_product($data)` |
| `shop_delete_*()` | 删除实体 | `shop_delete_product($id)` |
| `shop_normalize_*()` | 验证+类型转换+默认值 | `shop_normalize_product($row)` |
| `handle_*()` | Action handler（返回 `[message, type]`） | `handle_save_product()` |

### 变量风格
- **snake_case**：变量、数组键、配置键
- 禁止 camelCase（JSON payload 除外）

### Action Handler 模式
```php
// admin/controllers/xxx_actions.php
function handle_save_product(): array
{
    $name = shop_admin_post_string('name');
    // ... 业务逻辑 ...
    if (!shop_upsert_product($data)) {
        return ['保存失败', 'error'];
    }
    return ['保存成功', 'success'];
}
```
- 无参数（从 `$_POST` 读取）
- 返回 `[string $message, string $type]`，type ∈ `success|error|info|warning`

### 数据库查询模式
```php
$pdo = get_db_connection();
$prefix = get_db_prefix();

try {
    $stmt = $pdo->prepare("SELECT * FROM `{$prefix}products` WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    shop_log('error', '查询失败', ['message' => $e->getMessage()]);
}
```

### 事务模式（下单扣库存等）
```php
$pdo->beginTransaction();
try {
    // 多步操作 ...
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## 常用函数速查

### 转义与格式化
| 函数 | 用途 | 输出示例 |
|---|---|---|
| `shop_e($str)` | HTML 转义 | `&lt;script&gt;` |
| `shop_format_price($float)` | 价格格式化 | `￥123.45` |
| `shop_format_sales($int)` | 数量逗号分隔 | `1,234` |

### 日期
| 函数 | 输出示例 |
|---|---|
| `shop_short_date($datetime)` | `04-05` |
| `shop_short_datetime($datetime)` | `04-05 14:30` |
| `shop_to_input_datetime($datetime)` | `2026-04-05T14:30`（给 input） |
| `shop_from_input_datetime($value)` | `2026-04-05 14:30:00`（存数据库） |

### 分页
```php
$pagination = shop_paginate($total, $perPage, $currentPage);
echo shop_render_pagination($pagination, 'index.php?page=products&p=');
```

### Settings 键值存储
```php
shop_get_setting('store_name', '默认店名');
shop_set_setting('store_name', '魔女的小店');
shop_get_settings(['store_name', 'store_email']);
```

### 日志
```php
shop_log('info', '用户登录', ['user_id' => $id]);
shop_log('error', '支付失败', ['order_id' => $oid]);
shop_log_exception('操作失败', $exception);
```

### 订单状态
```php
shop_order_status_options();         // 所有状态及标签/颜色
shop_can_transition($from, $to);     // pending→paid→shipped→completed 或 any→cancelled
shop_order_status_meta($status);     // 返回 label、badge 样式
```

### 速率限制
```php
if (!shop_rate_limit('login', 5, 300)) { /* 超限 */ }
shop_rate_limit_reset('login');
```

---

## 数据库 Schema

表前缀通过 `get_db_prefix()` 获取，默认 `shop_`。
完整建表语句见 `database/schema.sql`，迁移脚本见 `database/migrations/`。

### 表结构摘要

**{prefix}products**
`id, name, category, sales, price(DECIMAL), stock, tag, home_sort, page_sort, sku(JSON TEXT), cover_image, images(JSON TEXT), description, status(on_sale/off_sale), published_at, created_at, updated_at`

**{prefix}orders**
`id, order_no(UNIQUE), user_id, customer, phone, address, status(pending/paid/shipped/completed/cancelled), pay_method, express_company, tracking_numbers, items(JSON TEXT), total(DECIMAL), remark, created_at, updated_at`

**{prefix}users**
`id, username(UNIQUE), name, email(UNIQUE), password_hash, reset_token, reset_expires, phone, level, status(active/follow_up/sleeping/banned), address, last_login, note, created_at, updated_at`

**{prefix}categories**
`id, name(UNIQUE), description, accent(颜色), emoji, sort, created_at, updated_at`

**{prefix}admin_users**
`id, username(UNIQUE), name, password_hash, role, status, last_login_at, created_at, updated_at`

**{prefix}settings**
`key(PK), value`

**{prefix}migrations**（更新中心创建）
`id, migration(UNIQUE), applied_at`

---

## CSS 设计体系

### 文件加载顺序
- **前台**：design-tokens.css → components.css → site.css → mobile.css
- **后台**：design-tokens.css → components.css → admin.css → mobile.css

### Design Tokens 速查

**颜色**
```
--color-primary: #6a37d4        --color-secondary: #87456c
--color-error: #b41340          --color-success: #34d399
--color-warning: #fbbf24        --color-info: #60a5fa
--color-surface: #faf5ff        --color-on-surface: #2f2e35
--color-surface-container-lowest: #fff
--color-surface-container-low: #f4effa
--color-surface-container: #ebe6f2
--color-outline: #78757e        --color-price: #f87171
```

**间距**：`--space-xs(4) --space-sm(8) --space-md(16) --space-lg(24) --space-xl(32) --space-2xl(48)`

**圆角**：`--radius-sm(0.5rem) --radius-md(1rem) --radius-lg(2rem) --radius-full(9999px)`

**字号**：`--text-display(2rem) --text-h1(1.625rem) --text-h2(1.25rem) --text-body(0.9375rem) --text-caption(0.8125rem)`

**字体**：`--font-headline(Plus Jakarta Sans) --font-body(Be Vietnam Pro) --font-display(LXGW WenKai)`

**阴影**：`--shadow-sm --shadow-md --shadow-lg --shadow-glow --shadow-ethereal`（均为紫色调透明阴影）

**过渡**：`--transition-fast(150ms) --transition-normal(250ms) --transition-slow(400ms)`

**毛玻璃**：`--glass-bg: rgba(255,255,255,0.7); --glass-blur: blur(20px);`

### 组件类
| 类名 | 用途 |
|---|---|
| `.btn-primary` | 渐变主色按钮，胶囊形 |
| `.btn-secondary` | 次要按钮 |
| `.btn-ghost` | 透明边框按钮 |
| `.btn-danger` | 危险操作按钮 |
| `.card` | 卡片容器（白底、圆角、阴影） |
| `.badge` / `.badge-primary` | 小标签 |
| `.input` | 输入框 |

### Ethereal 设计原则（来自 Stitch 设计稿）
- **无实线边框**：用背景色差区分层级，不用 border
- **表面层级**：surface → surface-container-low → surface-container → surface-container-high
- **按钮全胶囊**：radius-full，主按钮用渐变
- **悬浮效果**：scale(1.02) + 紫色调阴影增强
- **毛玻璃**：收藏按钮、浮层等用 backdrop-filter: blur(20px)
- **大量留白**：用 32px+ 间距代替分割线
- **图标**：`<span class="material-symbols-outlined">icon_name</span>`

---

## 路由

### 前台（index.php）
```
?page=home           → templates/index.php（首页）
?page=products       → templates/products.php（商品列表）
?page=product_detail → templates/product_detail.php
?page=cart           → templates/cart.php
?page=checkout       → templates/checkout.php
?page=auth           → templates/auth.php（登录/注册）
?page=profile        → templates/profile.php
?page=orders         → templates/orders.php
?page=order_detail   → templates/order_detail.php
?page=admin          → admin/index.php
```

### 后台（admin/index.php）
通过 `?tab=` 参数切换：dashboard, products, categories, orders, users, inventory, payment, settings, updates

POST 统一由 `admin/controllers/actions.php` 分发：读取 `$_POST['admin_action']`，switch 到对应 `handle_*()` 函数。

---

## 更新中心

代码在 `includes/updater.php` + `admin/controllers/update_actions.php`。

### 检查更新
调用 `https://api.github.com/repos/{SHOP_GITHUB_REPO}/releases/latest`，用 `version_compare()` 比对 tag_name 与 `SHOP_APP_VERSION`。

### 一键升级流程（9 步）
1. 预检（ZipArchive 扩展、目录可写）
2. 加锁（防并发）
3. 备份（文件 + 数据库 dump → zip）
4. 维护模式（`storage/maintenance.flag`，前台 503）
5. 下载（GitHub zipball）
6. 验证 zip 完整性
7. 两阶段部署（解压到 staging → 覆盖项目目录，跳过 storage/.git/config）
8. 数据库迁移（`database/migrations/` 下未执行的 SQL）
9. 健康检查

**任何步骤失败 → 自动从备份回滚 → 关闭维护模式 → 释放锁。**

### 发版要求
每次发布必须：
1. 更新 `includes/version.php` 中的 `SHOP_APP_VERSION`
2. 在 GitHub 创建 Release，tag 格式 `vX.Y.Z`

---

## Stitch 设计稿

参考文件在 `stitch/` 目录下（gitignored），每个子目录包含：
- `code.html` — Stitch 生成的 Tailwind HTML（仅参考结构和视觉，不直接使用）
- `screen.png` — 视觉截图

**重要**：Stitch 输出的是 Tailwind 代码，但本项目**禁止使用 Tailwind**。转写时必须使用项目自有的 design-tokens 变量和 components 类。

---

## 开发工作流

### 本地启动
```bash
php -S localhost:8080 -t .     # PHP 内置服务器
# 数据库：Docker 或本地 MySQL，配置在 config/database.php
```

### 新增页面检查清单
- [ ] PHP 文件头 `declare(strict_types=1)`
- [ ] 表单含 `csrf_field()`，处理入口含 `csrf_verify()`
- [ ] 所有动态输出用 `shop_e()`
- [ ] SQL 用预处理语句
- [ ] CSS 只用 design-tokens 变量
- [ ] JS 函数放 `assets/js/`，不内联
- [ ] 如有新 `admin_action`，同步在 `admin/controllers/actions.php` 添加 case

### 新增 admin_action 检查清单
- [ ] 在 `admin/controllers/actions.php` 的 switch 中添加 case
- [ ] 创建 handler 文件或在现有文件中添加 `handle_*()` 函数
- [ ] Handler 返回 `[message, type]`
- [ ] 视图中 form 包含 `admin_action` hidden input + `csrf_field()` + `tab` hidden input

---

## 修改粒度规则

- **改造类**（HTML 结构重写）：允许输出完整文件，提交后 `git diff --stat` 确认只改了预期文件
- **修复类**（≤10 行改动）：禁止整文件重写，使用精确行级编辑，diff ≤20 行
