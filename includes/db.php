<?php
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

/**
 * 获取数据库连接。
 */
function get_db_connection(): ?PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config_path = __DIR__ . '/../config/database.php';
    if (!file_exists($config_path)) {
        return null;
    }

    $config = require $config_path;
    $host = (string) ($config['host'] ?? getenv('DB_HOST') ?: '127.0.0.1');
    $port = (int) ($config['port'] ?? getenv('DB_PORT') ?: 3306);
    $name = (string) ($config['name'] ?? $config['dbname'] ?? getenv('DB_NAME') ?: '');
    $user = (string) ($config['user'] ?? $config['username'] ?? getenv('DB_USER') ?: 'root');
    $password = (string) ($config['password'] ?? getenv('DB_PASSWORD') ?: '');
    $charset = (string) ($config['charset'] ?? getenv('DB_CHARSET') ?: 'utf8mb4');
    $driver = (string) ($config['driver'] ?? 'mysql');

    if (strtolower($host) === 'localhost') {
        $host = '127.0.0.1';
    }

    if ($name === '') {
        return null;
    }

    try {
        $dsn = sprintf('%s:host=%s;port=%d;dbname=%s;charset=%s', $driver, $host, $port, $name, $charset);
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (PDOException $exception) {
        shop_log('error', '数据库连接失败', ['message' => $exception->getMessage()]);
        return null;
    }
}

/**
 * 获取数据库表前缀。
 */
function get_db_prefix(): string
{
    $config_path = __DIR__ . '/../config/database.php';
    if (!file_exists($config_path)) {
        return '';
    }

    $config = require $config_path;
    return (string) ($config['prefix'] ?? '');
}

/**
 * 更新数据库配置文件。
 */
function update_db_config(string $host, int $port, string $name, string $user, string $password, string $prefix = ''): bool
{
    $config_path = __DIR__ . '/../config/database.php';

    if (strtolower($host) === 'localhost') {
        $host = '127.0.0.1';
    }

    $config = [
        'driver' => 'mysql',
        'host' => $host,
        'port' => $port,
        'name' => $name,
        'user' => $user,
        'password' => $password,
        'prefix' => $prefix,
        'charset' => 'utf8mb4',
    ];

    $content = "<?php\n";
    $content .= "declare(strict_types=1);\n\n";
    $content .= 'return ' . var_export($config, true) . ";\n";

    return file_put_contents($config_path, $content) !== false;
}

/* ─── Settings 键值存取（{prefix}settings 表） ─── */

/**
 * 读取单个设置项。
 */
function shop_get_setting(string $key, string $default = ''): string
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return $default;
    }
    try {
        $prefix = get_db_prefix();
        $stmt = $pdo->prepare("SELECT `value` FROM `{$prefix}settings` WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return is_array($row) ? (string) $row['value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * 写入单个设置项（不存在则插入，存在则更新）。
 */
function shop_set_setting(string $key, string $value): bool
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    try {
        $prefix = get_db_prefix();
        $stmt = $pdo->prepare("REPLACE INTO `{$prefix}settings` (`key`, `value`) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * 批量读取设置项，返回 key => value 关联数组。
 */
function shop_get_settings(array $keys): array
{
    $pdo = get_db_connection();
    if (!$pdo || empty($keys)) {
        return [];
    }
    try {
        $prefix = get_db_prefix();
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ({$placeholders})");
        $stmt->execute(array_values($keys));
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[(string) $row['key']] = (string) $row['value'];
        }
        return $result;
    } catch (PDOException $e) {
        return [];
    }
}
