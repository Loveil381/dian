<?php
declare(strict_types=1);

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
        error_log('[shop] 数据库连接失败: ' . $exception->getMessage());
        return null;
    }
}

/**
 * 获取表前缀。
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
 * 写入数据库配置文件。
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
