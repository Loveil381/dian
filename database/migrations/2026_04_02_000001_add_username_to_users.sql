-- 迁移: 为 users 表添加 username 和 password_hash 字段
-- 每条操作独立执行，确保 updater 容错逻辑不会因单条失败而跳过后续操作
ALTER TABLE `{prefix}users` ADD COLUMN `username` VARCHAR(50) NULL AFTER `name`;
ALTER TABLE `{prefix}users` ADD UNIQUE KEY `uk_users_username` (`username`);
ALTER TABLE `{prefix}users` ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `username`;
