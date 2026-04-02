# 架构设计
> 最后更新: 2026-04-02 | 会话轮次: #0

## 当前架构
index.php（路由） → templates/（前台视图+逻辑） + admin/（后台一体化）
data/products.php（数据层） + includes/db.php（DB 连接）
config/database.php（配置） + database/schema.sql（表结构）

## 目标架构（阶段性）
阶段 2 目标：
- admin/ 拆分为 admin/controllers/ + admin/views/ + admin/includes/
- 统一中间件层（认证、CSRF、日志）
- 数据库迁移体系
