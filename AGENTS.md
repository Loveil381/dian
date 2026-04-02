# AGENTS.md — 魔女小店项目规则

## 项目概述
「魔女小店」是一个轻量级原生 PHP + MySQL 在线商城系统。
定位：零依赖、虚拟主机即可部署的个人独立小店。
仓库默认分支：master

## 技术栈
- PHP 8.1+（严格类型 `declare(strict_types=1)`）
- MySQL 5.6+（utf8mb4）
- 纯原生 PHP，不使用任何框架
- 前端：原生 HTML + CSS + JS，无构建工具

## 代码规范（所有注释用中文）
- 所有新建/修改的 PHP 文件顶部必须有 `declare(strict_types=1);`
- 所有注释必须使用中文
- 函数/变量命名：snake_case
- HTML 输出必须用 `htmlspecialchars()` 转义（项目中封装为 `shop_e()`）
- 所有数据库查询必须使用预处理语句（Prepared Statements）
- 禁止使用 `@` 错误抑制符
- 禁止空 catch 块——至少记录日志或返回有意义的错误信息
- PDO 异常不得被静默吞掉

## 安全铁律
- 所有 POST 表单必须包含 CSRF Token 验证
- 文件上传必须二次校验文件头（不仅靠 MIME type）
- 数据库凭证禁止出现在代码仓库中（必须走 .env 或 .gitignore 的配置文件）
- 禁止在可公网访问的文件中输出数据库凭证或调试信息
- Session 必须配置 httponly + samesite

## 数据库规范
- 表名使用配置中的前缀：`{prefix}table_name`
- 所有新表必须在 `database/` 目录下有对应的迁移文件
- 迁移文件命名格式：`YYYY_MM_DD_HHMMSS_description.sql`
- 每个迁移文件必须幂等（`IF NOT EXISTS` / `IF EXISTS`）

## Git 规范
- 先创建分支再动手：`git checkout -b improve/[任务名]`
- 每完成一个逻辑单元就 commit
- commit 信息格式：`类型(范围): 中文描述`
  - 类型：feat / fix / refactor / docs / security / chore
- 完成后 `git push origin 分支名`
- 禁止 `git reset --hard`、`rm -rf`

## 目录约定
- `admin/` — 后台管理
- `templates/` — 前台页面模板
- `includes/` — 公共函数库
- `data/` — 数据处理层
- `config/` — 配置文件（.gitignore 排除敏感内容）
- `database/` — 数据库 schema 和迁移
- `assets/` — 静态资源
- `docs/ai-cto/` — CTO AI 记忆文件（勿手动编辑）

## 禁止事项
- 禁止删除整个文件重建替代精确修复
- 禁止硬编码占位数据并标记为已完成
- 禁止在 HTML 中使用内联 JavaScript 事件处理器（onclick 等），应使用 addEventListener

💡 作用: 统一项目代码规范，约束 Agent 行为 🎯 服务于产品目标: 所有后续开发在一致的质量基线上进行
