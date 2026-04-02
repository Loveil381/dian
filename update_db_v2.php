<?php
require_once __DIR__ . '/includes/db.php';
$pdo = get_db_connection();
$prefix = get_db_prefix();

if ($pdo) {
    try {
        $pdo->exec("ALTER TABLE `{$prefix}orders` ADD COLUMN express_company VARCHAR(80) NULL DEFAULT '' AFTER pay_method");
        echo "Added express_company to orders.\n";
    } catch (Exception $e) { echo $e->getMessage() . "\n"; }
    
    try {
        $pdo->exec("ALTER TABLE `{$prefix}products` MODIFY COLUMN sku TEXT NULL");
        echo "Modified sku to TEXT in products.\n";
    } catch (Exception $e) { echo $e->getMessage() . "\n"; }

    try {
        $pdo->exec("ALTER TABLE `{$prefix}admin_users` ADD COLUMN name VARCHAR(80) NULL DEFAULT '' AFTER username");
        echo "Added name to admin_users.\n";
    } catch (Exception $e) { echo $e->getMessage() . "\n"; }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
            `key` VARCHAR(50) NOT NULL,
            `value` TEXT NULL,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "Created settings table.\n";
    } catch (Exception $e) { echo $e->getMessage() . "\n"; }
    
} else {
    echo "DB connection failed.\n";
}
