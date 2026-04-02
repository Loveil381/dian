# 审核问题 Backlog
> 最后更新: 2026-04-02 | 会话轮次: #0

## 🔴 Critical
| # | 文件 | 问题 | 状态 |
|---|---|---|---|
| S-1 | db_test_probe.php | 明文暴露 DB 凭证 | 🔄 本轮修复中 |
| S-2 | config/database.php | 配置提交到公开仓库 | 🔄 本轮修复中 |
| S-3 | 所有 POST 表单 | 无 CSRF Token | 🔄 本轮部分修复 |
| A-1 | admin/index.php | 109KB 单体文件 | ⏳ 下轮 |
| F-1 | create_order.php | 下单不扣库存 | ⏳ 待修 |
| D-1 | 仓库根 | 无 .gitignore | 🔄 本轮修复中 |

## 🟠 Major
| # | 文件 | 问题 | 状态 |
|---|---|---|---|
| S-4 | admin/upload.php | 文件类型检查不严格 | ⏳ 待修 |
| Q-1 | data/products.php | 异常被静默吞掉 | ⏳ 待修 |
| F-2 | header.php | 搜索/购物车 display:none | ⏳ 待修 |
| F-4 | 全局 | 无购物车功能 | ⏳ 待修 |
| P-1 | admin/index.php | 每次加载 6+ 全表查询 | ⏳ 待修 |
