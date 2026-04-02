-- 为用户表补充密码找回能力
-- 除 reset_token / reset_expires 外，同时补充 email 字段，便于以前台注册邮箱作为找回凭据

ALTER TABLE `{PREFIX}users`
    ADD COLUMN `email` VARCHAR(120) NULL DEFAULT NULL AFTER `name`,
    ADD COLUMN `reset_token` VARCHAR(128) NULL DEFAULT NULL AFTER `password_hash`,
    ADD COLUMN `reset_expires` DATETIME NULL DEFAULT NULL AFTER `reset_token`,
    ADD UNIQUE KEY `uk_users_email` (`email`),
    ADD KEY `idx_users_reset_token` (`reset_token`);
