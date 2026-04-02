# 项目状态
> 最后更新: 2026-04-02 | 会话轮次: #4

## 一句话状态
前台购物流程构建完毕，包括购物车、搜索恢复与结算支付链路，代码规范规则已就位。

## 质量评分: 6/10

## 活跃分支
| 分支 | 用途 | 状态 |
|---|---|---|
| improve/security-and-foundation | 安全加固+.gitignore+README+迁移体系+记忆文件 | ✅ 已完成 |
| improve/admin-split | 后台拆分+action修复+扣库存 | ✅ 已完成 |
| improve/frontend-shopping | 购物车+结算单+收款码+搜索还原+规则基石 | ✅ 工作完成 |

## 已完成
- [#1.1] 安全加固：删除 db_test_probe.php、创建 .gitignore、CSRF 全面覆盖、迁移体系 — 2026-04-02
- [#2.1] admin/index.php 拆分为 MVC 多文件结构（9 视图 + 1 控制器）— 2026-04-02
- [#3.1] 修复 action 路由断裂 + 前台下单扣库存 + JS 迁移 + 清理备份 — 2026-04-02
- [#4.1] 前台购物流程（Session购物车、结算页带收款码展示、无库存售罄逻辑及搜索栏恢复）与 AGENTS.md 部署 — 2026-04-02

## 进行中
- 无

## 待办（按优先级）
1. 支付确认机制优化（订单防重、异步回调验证机制）
2. 上传文件安全加固（S-4）
3. 异常处理改造（Q-1，抛弃静默报错）
4. 后台分页审查优化（P-1）
5. 用户找回密码功能开发
6. 接通或部署全站 AGENTS.md 工程指引

## 已知问题
- config/database.php 在 Git 历史中有泄露: Minor（.gitignore 已排除，建议清理历史）
- data/products.php 异常静默吞掉: Major
- admin/upload.php 类型检查不严格: Major
