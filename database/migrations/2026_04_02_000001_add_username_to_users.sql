-- 迁移: 为 users 表添加 username 和 password_hash 字段
ALTER TABLE `{prefix}users` ADD COLUMN IF NOT EXISTS `username` VARCHAR(50) NULL UNIQUE AFTER `name`;
ALTER TABLE `{prefix}users` ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) NULL AFTER `username`;
