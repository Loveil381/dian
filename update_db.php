<?php
require_once __DIR__ . '/includes/db.php';
$config = require __DIR__ . '/config/database.php';
if (empty($config['host'])) $config['host'] = getenv('DB_HOST') ?: '127.0.0.1';
if (empty($config['dbname'])) $config['dbname'] = getenv('DB_NAME') ?: 'shop_demo';
if (empty($config['username'])) $config['username'] = getenv('DB_USER') ?: 'root';
if (!isset($config['password'])) $config['password'] = getenv('DB_PASSWORD') ?: '';

try {
    $dsn = sprintf(
        '%s:host=%s;port=%d;dbname=%s;charset=%s',
        $config['driver'] ?? 'mysql',
        $config['host'],
        $config['port'] ?? 3306,
        $config['dbname'],
        $config['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL UNIQUE AFTER name");
        echo "Added username column.\n";
    } catch (Exception $e) { echo $e->getMessage() . "\n"; }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER username");
        echo "Added password_hash column.\n";
    } catch (Exception $e) { echo $e->getMessage() . "\n"; }

} catch (PDOException $e) {
    echo $e->getMessage() . "\n";
}
