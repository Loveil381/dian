# 项目状态
> 最后更新: 2026-04-02 | 会话轮次: #3

## 一句话状态
安全加固和后台拆分已完成，本轮修复 action 断裂和下单扣库存。

## 质量评分: 5/10

## 活跃分支
| 分支 | 用途 | 状态 |
|---|---|---|
| improve/security-and-foundation | 安全加固+.gitignore+README+迁移体系+记忆文件 | ✅ 已完成 |
| improve/admin-split | 后台拆分+action修复+扣库存 | 进行中 |

## 已完成
- [#1.1] 安全加固：删除 db_test_probe.php、创建 .gitignore、CSRF 全面覆盖、迁移体系 — 2026-04-02
- [#2.1] admin/index.php 拆分为 MVC 多文件结构（9 视图 + 1 控制器）— 2026-04-02
- [#3.1] 修复 action 路由断裂 + 前台下单扣库存 + JS 迁移 + 清理备份 — 2026-04-02

## 进行中
- 无

## 待办（按优先级）
1. 前台购物车功能 — 产品关键路径
2. 支付流程集成（收款码展示+确认机制）— 产品关键路径
3. 上传文件安全加固（S-4）— 技术债
4. 前台搜索功能恢复（F-2 header.php display:none）— 产品关键路径
5. 异常处理改造（Q-1 静默吞异常）— 技术债
6. 后台分页（P-1 全表查询）— 性能优化

## 已知问题
- config/database.php 在 Git 历史中有泄露: Minor（.gitignore 已排除，建议清理历史）
- data/products.php 异常静默吞掉: Major
- admin/upload.php 类型检查不严格: Major
