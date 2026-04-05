# AI CTO 状态

> 版本: v1.4.0 (即将发布 v1.5.0) | 质量分: 9.3/10 | 进度: 运营工具四大功能已实现

## 活跃分支
- `improve/v1.4.0-infra` — 已合并
- `master` — v1.5.0 开发中（直推）

## 已完成
- [x] 全部 PHP 功能代码（Round #1-#21）
- [x] 安全加固（Round #19-#20）
- [x] E2E 审计修复（Round #21）
- [x] Stitch 设计稿 25/25 页面
- [x] Phase 1: design-tokens.css, components.css, header/footer/index 改造
- [x] Phase 1 收尾: CSS 外置, Flash 兼容, color-mix 降级, 底部导航 badge
- [x] 字号/字体工具类 (components.css)
- [x] P2: product_detail.php 改版
- [x] P3: cart.php 改版
- [x] P4: checkout.php 改版
- [x] P5: auth.php 改版
- [x] P6: profile.php 改版
- [x] P7: orders.php 改版
- [x] P8: order_detail.php 改版

## 已完成 — Round #25 修复
- [x] Fix: profile.php inline style → flash 组件类
- [x] Fix: auth.php 注册密码确认校验
- [x] Fix: STATUS.md 同步

## 已完成 — Phase 2 前台页面
- [x] P9: templates/products.php ← stitch/all_products
- [x] P10: templates/forgot_password.php ← stitch/forgot_password
- [x] P11: templates/reset_password.php ← stitch/reset_password

## 已完成 — Phase 3 后台页面
- [x] Prep: admin.css 承接后台改版样式；AGENTS.md 增补粒度规则；site.css 约 73KB，Phase 3 后台样式禁止继续写入
- [x] P12: admin/views/layout.php ← stitch/admin_dashboard_panel (导航)
- [x] P13: admin/views/dashboard.php ← stitch/admin_dashboard_panel
- [x] P14: admin/views/categories.php ← stitch/category_management
- [x] Round #27: dashboard fallback, inventory/products/orders 后台改版
- [x] P15: admin/views/inventory.php ← stitch/inventory_management
- [x] P16: admin/views/products.php ← stitch/product_management
- [x] P17: admin/views/orders.php ← stitch/order_management
- [x] P18: admin/views/users.php ← stitch/user_management
- [x] P19: admin/views/payment.php ← stitch/payment_settings
- [x] P20: admin/views/settings.php ← stitch/system_settings

## 已完成 — Round #29 全局润色
- [x] 前后台 23 文件文案/标签统一润色
- [x] admin views 补充 declare(strict_types=1)
- [x] upload.php CSRF 加固 + finfo MIME 检测
- [x] admin.js 上传逻辑集中化 + CSRF 集成
- [x] order_detail.php 5 处 null 合并修复
- [x] settings.php autocomplete 属性修正

## 已完成 — v1.4.0 基础设施补全
- [x] 动态 sitemap.xml + .htaccess 重写
- [x] 安全响应头（CSP/Referrer-Policy/Permissions-Policy）
- [x] 分页组件内联样式外置到 CSS
- [x] 硬编码值可配置化（每页条数/库存阈值/密码长度）
- [x] session secure flag 按 HTTPS 自动切换

## 已完成 — v1.5.0 运营工具
- [x] 销售数据看板：KPI 卡片 + CSS 柱状图 + 热销排行 + 状态分布
- [x] 管理员操作日志：shop_admin_log() + 全部 handler 注入 + 日志 tab + 筛选分页
- [x] 优惠券系统：CRUD + 验证 + 原子递增 + 结算集成（二次验证）+ 管理 tab
- [x] 订单通知框架：事件驱动邮件通知 + 管理员/客户双通道 + 5 个独立开关

## 已完成 — v1.3.0 UX 改进
- [x] 在线咨询气泡 + 后台配置
- [x] 商品详情页数量选择器
- [x] 用户名前后端校验
- [x] 游客入口改为登录优先
- [x] CSS 缓存破坏 + SKU 消失修复 + 无效按钮清理

## 此前已完成 — v1.0-v1.2


### 改动
- [x] 商品详情页新增数量选择器（`- 1 +` 步进器），支持加入购物车和立即购买时选数量
  - `templates/product_detail.php` — 数量步进器 HTML + hidden inputs
  - `assets/js/site.js` — 步进器交互逻辑（增减、库存上限、切换 SKU 重置）
  - `assets/css/site.css` — 步进器样式（Ethereal 设计体系）
  - `actions/cart_action.php` — add action 接受 quantity 参数
  - `actions/checkout_action.php` — quick_buy action 接受 quantity 参数
- [x] 在线咨询聊天气泡功能（SPEC: docs/ai-cto/SPEC-online-consult.md）
  - `admin/data_loaders/settings.php` — 新建，加载 consult_* 设置
  - `admin/index.php` — 默认变量 + loaderMap 注册
  - `admin/views/settings.php` — 新增"在线咨询"设置卡片（含二维码上传）
  - `admin/controllers/setting_actions.php` — handle_save_consult()
  - `admin/controllers/actions.php` — save_consult dispatcher
  - `templates/index.php` — 条件渲染气泡+药师区块+弹窗
  - `assets/js/site.js` — 弹窗开关 + 遮罩关闭 + 复制微信号
  - `assets/css/site.css` — 弹窗样式（Ethereal 设计体系）

### 评估后不做
- **封禁用户全局实时踢出**：评估后决定不加。理由：
  1. 现有两道拦截（登录时 + 下单时）已封死关键操作路径
  2. 被封禁用户能做的只有只读浏览（看商品、看历史订单），游客也能做，无安全风险
  3. 全局检查需要每个已登录用户每次页面加载多一次 DB 查询，性能代价不值
  4. 封禁是极低频管理操作，不值得让全体用户为此买单

## 待办 — 部署后
- [ ] Ops-1: 生产 SMTP 集成
- [ ] Ops-2: 日志监控搭建
- [ ] UX-3: 真机移动端测试

## 设计差异记录
- AUTH: Stitch 社交登录区省略（无后端支持）
- ORDER_DETAIL: "正品保证""环保包装"装饰卡省略；"再次购买"按钮省略（无 re-order 功能）
- ORDERS: Tab Filter 省略（无 tab 过滤 PHP 逻辑）
