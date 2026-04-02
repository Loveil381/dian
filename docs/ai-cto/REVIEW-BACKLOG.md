# 评审问题 Backlog
> 最近更新: 2026-04-02 | 当前轮次: #5

## Critical
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| S-1 | `db_test_probe.php` | 生产探针暴露数据库连接信息 | ✅ 已修复 |
| S-2 | `config/database.php` | 敏感配置误入仓库风险 | ✅ 已通过 `.gitignore` 规避 |
| S-3 | 全部 POST 表单 | 缺少 CSRF Token | ✅ 已补齐主要入口 |
| C-2 | `templates/product_detail.php` | `paidForm` 缺少 CSRF | ✅ 本轮修复 |
| A-1 | `admin/index.php` | 单文件过大、职责混杂 | ✅ 已拆分 |
| F-1 | `create_order.php` | 单买链路缺少一致性校验 | ⚠️ 仍需统一 |
| D-1 | 仓库根目录 | 缺少忽略规则与工程基线 | ✅ 已补齐 |
| I-3 | `orders.php` / `actions.php` | action 路由缺失 | ✅ 已修复 |
| I-4 | `users.php` / `actions.php` | `toggle_user_status` 缺失 | ✅ 已修复 |
| I-5 | `settings.php` / `actions.php` | `change_password` / `save_role` 缺失 | ✅ 已修复 |

## Major
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| S-4 | `admin/upload.php` | 上传仅靠扩展名与客户端 MIME 判断 | ✅ 本轮修复 |
| Q-1 | `data/products.php` | 异常处理与失败回退不足 | ⏳ 待处理 |
| F-2 | `header.php` | 搜索与导航体验仍较粗糙 | ⏳ 待处理 |
| F-4 | 前台全局 | 交互脚本曾散落在视图中 | ✅ 本轮已迁移主要内联 JS |
| P-1 | 后台列表 | 缺少分页能力 | ⏳ 待处理 |
| C-3 | `orders.items` 字段 | 订单商品信息以拼接字符串存储 | ⏳ 技术债 |
| C-4 | `create_order.php` / `checkout.php` | 两条下单路径行为不一致 | ⏳ 技术债 |

## Minor
| # | 位置 | 问题 | 状态 |
|---|---|---|---|
| I-1 | `products.php` / `payment.php` | JS 组织方式不统一 | ✅ 已部分收敛 |
| I-6 | `admin/index.original.php` | 历史备份文件残留风险 | ✅ 已清理 |
