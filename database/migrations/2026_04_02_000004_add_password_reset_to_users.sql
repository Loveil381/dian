-- 为用户表补充密码找回能力
-- 除 reset_token / reset_expires 外，同时补充 email 字段，便于以前台注册邮箱作为找回凭据

-- 每条操作独立执行，确保 updater 容错逻辑不会因单条失败而跳过后续操作
ALTER TABLE `{prefix}users` ADD COLUMN `email` VARCHAR(120) NULL DEFAULT NULL AFTER `name`;
ALTER TABLE `{prefix}users` ADD COLUMN `reset_token` VARCHAR(128) NULL DEFAULT NULL AFTER `password_hash`;
ALTER TABLE `{prefix}users` ADD COLUMN `reset_expires` DATETIME NULL DEFAULT NULL AFTER `reset_token`;
ALTER TABLE `{prefix}users` ADD UNIQUE KEY `uk_users_email` (`email`);
ALTER TABLE `{prefix}users` ADD KEY `idx_users_reset_token` (`reset_token`);
