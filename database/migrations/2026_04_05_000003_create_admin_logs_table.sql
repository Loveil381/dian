-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `{prefix}admin_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `admin_name` VARCHAR(80) NOT NULL DEFAULT '',
  `action` VARCHAR(50) NOT NULL,
  `target_type` VARCHAR(30) NOT NULL DEFAULT '',
  `target_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `detail` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_logs_created` (`created_at`),
  KEY `idx_admin_logs_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
