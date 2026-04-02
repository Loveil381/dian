# 魔女小店 — 项目规则

## 项目概述
轻量级原生 PHP 8.1+ 在线商城系统，零框架依赖，可部署在廉价虚拟主机。

## 技术栈
- 语言: PHP 8.1+ (declare strict_types)
- 数据库: MySQL 5.6+, PDO, 预处理语句
- 前端: 原生 HTML/CSS/JS, 无构建工具
- Web 服务器: Apache + mod_rewrite

## 编码规范
- 所有 PHP 文件以 <?php declare(strict_types=1); 开头
- 所有代码注释必须用中文
- 函数命名: shop_ 前缀（公共函数），shop_admin_ 前缀（后台函数）
- 变量和数组键: 小写蛇形 (snake_case)
- HTML 输出使用 shop_e() 转义
- 价格使用 shop_format_price()，销量使用 shop_format_sales()

## 安全规范
- 所有 POST 表单必须包含 <?php echo csrf_field(); ?>
- 所有 POST 处理入口必须调用 csrf_verify()
- 数据库操作必须使用 PDO 预处理语句，禁止拼接 SQL
- 用户密码必须使用 password_hash() + password_verify()
- 上传文件必须验证 MIME 类型，只允许 JPEG/PNG/GIF/WEBP
- 敏感配置（数据库凭证）不得提交到仓库

## 数据库规范
- 表名使用 {prefix} 前缀机制，通过 get_db_prefix() 获取
- 所有查询使用 try/catch 包裹 PDOException
- 涉及多表修改的操作使用事务 (beginTransaction/commit/rollBack)

## Git 规范
- 分支命名: improve/[功能名]
- 提交信息格式: type(scope): 中文描述
- 每完成一个逻辑单元就 commit
- 禁止 git reset --hard 和 rm -rf

## 目录约定
- admin/controllers/ — 后台 POST 处理
- admin/views/ — 后台视图模板
- admin/includes/ — 后台辅助函数
- templates/ — 前台页面模板
- includes/ — 公共工具（db.php, csrf.php）
- assets/css/ — 样式表
- assets/js/ — 脚本文件
- docs/ai-cto/ — CTO AI 记忆文件

## 关键规则
- 新增或修改任何 <form> 的 admin_action 值时，
  必须同步在 admin/controllers/actions.php 中添加对应 case
- 禁止在视图文件中定义 JavaScript 函数，统一放 assets/js/
- 禁止硬编码占位数据标记为已完成
- UI 元素不可交互 = 未完成
