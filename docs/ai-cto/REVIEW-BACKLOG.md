# 评审问题 Backlog
> 最后更新: 2026-04-02 | 当前轮次: #10

## Critical
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| S-1 | `db_test_probe.php` | 调试探针暴露数据库连接信息 | ✅ 已修复 |
| S-2 | `config/database.php` | 敏感配置文件存在泄露风险 | ✅ 已修复 |
| S-3 | 全部 POST 表单 | CSRF 覆盖不完整 | ✅ 已修复 |
| C-2 | `templates/product_detail.php` | `paidForm` 缺少 CSRF | ✅ 已修复 |
| Q-6.3 | `admin/views/orders.php` | 后台订单金额字段名不匹配 | ✅ 已修复 |
| Q-7.1 | `templates/forgot_password.php` | reset token 以明文存储在数据库中 | ✅ 已修复 |
| Q-7.2 | `templates/forgot_password.php` | 密码找回接口缺少速率限制 | ✅ 已修复 |
| Q-7.4 | `templates/auth.php` | 注册密码缺少最小长度校验 | ✅ 已修复 |

## Major
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| S-4 | `admin/upload.php` | 上传仅依赖扩展名与客户端 MIME | ✅ 已修复 |
| Q-1 | `data/products.php` | 异常被静默吞掉 | ✅ 已修复 |
| C-3 | `orders.items` | 订单商品信息为拼接字符串 | ✅ 已修复 |
| C-4 | `create_order.php` / `checkout.php` | 存在两条下单路径 | ✅ 已修复 |
| I-7 | `footer.php` | 页脚内联脚本未迁移 | ✅ 已修复 |
| Q-6.1 | `templates/product_detail.php` | 缩略图 inline onclick 存在注入风险 | ✅ 已修复 |
| Q-8.6 | `admin/views/products.php` | SKU 编辑仍依赖内联脚本初始化 | ✅ 已修复 |

## Minor
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| F-2 | `header.php` | 搜索交互仍较轻量 | ⏳ 待优化 |
| UX-2 | 找回密码 | 尚未接入邮件服务 | ⏳ 技术债 |
| P-1 | 后台列表 | 缺少分页 | ✅ 已修复 |
| Q-9.1 | `admin/controllers/actions.php` | `update_order` 缺少状态流转校验 | ✅ 已修复 |
| Q-9.2 | `admin/views/*.php` | 后台视图残留多处内联事件属性 | ✅ 已修复 |
| Q-9.3 | `templates/header.php` | header 仍保留内联样式块 | ✅ 已修复 |
| Q-9.5 | `assets/js/site.js` / `assets/css/mobile.css` | 移动端菜单显隐联动需最终收口 | ✅ 已修复 |
| Q-10.2 | `assets/js/admin.js` | `updateQrPreview()` 使用 `innerHTML` 渲染预览 | ✅ 已修复 |
| Q-10.3 | `assets/js/site.js` | `cartBtn` 冗余点击监听可移除 | ✅ 已修复 |
| A-11.1 | `admin/views/orders.php` / `admin/index.php` | 订单列表缺少状态筛选 | ✅ 已修复 |
| A-11.2 | `admin/views/products.php` / `admin/index.php` | 商品列表缺少分类与状态筛选 | ✅ 已修复 |
| A-11.3 | `admin/views/products.php` / `admin/controllers/actions.php` | 商品缺少批量上下架与批量删除 | ✅ 已修复 |
