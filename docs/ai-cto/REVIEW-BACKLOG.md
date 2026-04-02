# 审核问题 Backlog
> 最后更新: 2026-04-02 | 会话轮次: #3

## 🔴 Critical
| # | 文件 | 问题 | 状态 |
|---|---|---|---|
| S-1 | db_test_probe.php | 明文暴露 DB 凭证 | ✅ 已删除 |
| S-2 | config/database.php | 配置提交到公开仓库 | ✅ 已加入 .gitignore |
| S-3 | 所有 POST 表单 | 无 CSRF Token | ✅ 已全面覆盖 |
| A-1 | admin/index.php | 109KB 单体文件 | ✅ 已拆分为 MVC |
| F-1 | create_order.php | 下单不扣库存 | ✅ 本轮修复 |
| D-1 | 仓库根 | 无 .gitignore | ✅ 已创建 |
| I-3 | orders.php / actions.php | action 名不匹配 | ✅ 本轮修复 |
| I-4 | users.php / actions.php | toggle_user_status 缺失 | ✅ 本轮修复 |
| I-5 | settings.php / actions.php | change_password/save_role 缺失 | ✅ 本轮修复 |

## 🟠 Major
| # | 文件 | 问题 | 状态 |
|---|---|---|---|
| S-4 | admin/upload.php | 文件类型检查不严格 | ⏳ 待修 |
| Q-1 | data/products.php | 异常被静默吞掉 | ⏳ 待修 |
| F-2 | header.php | 搜索/购物车 display:none | ⏳ 待修 |
| F-4 | 全局 | 无购物车功能 | ⏳ 待修 |
| P-1 | admin/index.php | 每次加载 6+ 全表查询 | ⏳ 待修 |

## 🟡 Minor
| # | 文件 | 问题 | 状态 |
|---|---|---|---|
| I-1 | products.php + payment.php | 内联 JS | ✅ 本轮迁移 |
| I-6 | admin/index.original.php | 备份文件残留 | ✅ 本轮删除 |
