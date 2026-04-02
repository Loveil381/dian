# 代码审查 Backlog
> 最后更新: 2026-04-02 | 当前轮次: #15

## Critical
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| S-1 | `db_test_probe.php` | 测试探针暴露数据库连接信息 | ✅ 已修复 |
| S-2 | `config/database.php` | 敏感配置明文提交风险 | ✅ 已修复 |
| S-3 | 全部 POST 表单 | CSRF 防护不完整 | ✅ 已修复 |
| C-2 | `templates/product_detail.php` | `paidForm` 缺少 CSRF | ✅ 已修复 |
| Q-6.3 | `admin/views/orders.php` | 后台订单金额字段名不匹配 | ✅ 已修复 |
| Q-7.1 | `templates/forgot_password.php` | reset token 明文存储 | ✅ 已修复 |
| Q-7.2 | `templates/forgot_password.php` | 找回密码缺少速率限制 | ✅ 已修复 |
| Q-7.4 | `templates/auth.php` | 注册密码长度校验不足 | ✅ 已修复 |

## Major
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| S-4 | `admin/upload.php` | 上传类型校验依赖客户端声明，缺少真实 MIME 验证 | ✅ 已修复 |
| Q-1 | `data/products.php` | 异常处理缺少统一日志 | ✅ 已修复 |
| C-3 | `orders.items` | 订单商品字段为拼接字符串 | ✅ 已修复 |
| C-4 | `create_order.php` / `checkout.php` | 两条下单路径不一致 | ✅ 已修复 |
| I-7 | `footer.php` | 页脚残留内联脚本 | ✅ 已修复 |
| Q-6.1 | `templates/product_detail.php` | 缩略图 inline onclick 存在注入风险 | ✅ 已修复 |
| Q-8.6 | `admin/views/products.php` | SKU 管理脚本仍在视图内定义 | ✅ 已修复 |
| F-2 | `templates/products.php` / `assets/js/site.js` | 搜索交互未完成异步增强 | ✅ 已修复 |
| UX-2 | 密码找回流程 | 尚未接入邮件发送 | ✅ 已修复 |
| Q-12.2 | `templates/auth.php` / `templates/forgot_password.php` / `templates/reset_password.php` | 认证页仍残留 inline style | ✅ 已修复 |
| Q-12.3 | `templates/products.php` | 商品卡片仍使用 `onclick` 跳转 | ✅ 已修复 |
| Q-13.1 | `install.php` | 安装后缺少管理员初始化 | ✅ 已修复 |

## Minor
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| P-1 | 后台列表页 | 缺少分页能力 | ✅ 已修复 |
| Q-9.1 | `admin/controllers/actions.php` | `update_order` 缺少状态流转校验 | ✅ 已修复 |
| Q-9.2 | `admin/views/*.php` | 后台视图保留大量内联事件属性 | ✅ 已修复 |
| Q-9.3 | `templates/header.php` | Header 内联样式未迁移 | ✅ 已修复 |
| Q-9.5 | `assets/js/site.js` / `assets/css/mobile.css` | 移动端菜单交互未完全闭环 | ✅ 已修复 |
| Q-10.2 | `assets/js/admin.js` | `updateQrPreview()` 使用 `innerHTML` 注入图片 | ✅ 已修复 |
| Q-10.3 | `assets/js/site.js` | `cartBtn` 冗余点击监听 | ✅ 已修复 |
| A-11.1 | `admin/views/orders.php` / `admin/index.php` | 订单列表缺少状态筛选 | ✅ 已修复 |
| A-11.2 | `admin/views/products.php` / `admin/index.php` | 商品列表缺少分类/状态筛选 | ✅ 已修复 |
| A-11.3 | `admin/views/products.php` / `admin/controllers/actions.php` | 商品缺少批量操作能力 | ✅ 已修复 |
| Q-14.4 | `templates/product_detail.php` / `assets/css/site.css` | 商品详情页残留大量 inline style | ✅ 已修复 |
| Q-14.5 | `templates/header.php` | canonical URL 包含临时查询参数 | ✅ 已修复 |
| Q-14.6 | `templates/header.php` | cart 区域残留 inline style | ✅ 已修复 |
| UX-3 | 移动端体验 | 需要真机测试验证细节与可用性 | ⏳ 待处理 |
