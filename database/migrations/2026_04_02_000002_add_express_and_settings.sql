-- 迁移: orders 表添加 express_company，products 表 sku 改为 TEXT，admin_users 添加 name，创建 settings 表
ALTER TABLE `{prefix}orders` ADD COLUMN `express_company` VARCHAR(80) NULL DEFAULT '' AFTER `pay_method`;
ALTER TABLE `{prefix}products` MODIFY COLUMN `sku` TEXT NULL;
ALTER TABLE `{prefix}admin_users` ADD COLUMN `name` VARCHAR(80) NULL DEFAULT '' AFTER `username`;
CREATE TABLE IF NOT EXISTS `{prefix}settings` (
    `key` VARCHAR(50) NOT NULL,
    `value` TEXT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
