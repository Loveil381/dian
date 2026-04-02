<?php
declare(strict_types=1);

// This file is used to manage database connections.

function get_db_connection(): ?PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        return null; // Need to configure DB
    }

    $config = require $configPath;
    
    // Fallback to environment variables if config is empty or default
    if (empty($config['host'])) $config['host'] = getenv('DB_HOST') ?: '127.0.0.1';
    
    // Convert localhost to 127.0.0.1 to prevent IPv6 (::1) or socket routing issues 
    // which commonly cause "Access denied for user '@'localhost'"
    if (strtolower($config['host']) === 'localhost') {
        $config['host'] = '127.0.0.1';
    }
    if (empty($config['dbname'])) $config['dbname'] = getenv('DB_NAME') ?: 'shop_demo';
    if (empty($config['username'])) $config['username'] = getenv('DB_USER') ?: 'root';
    if (!isset($config['password'])) $config['password'] = getenv('DB_PASSWORD') ?: '';
    
    // Check if configuration is just empty/default
    if (empty($config['host']) || empty($config['dbname'])) {
        return null;
    }

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
        
        // 显式执行一下查询测试，防止延迟连接不报错
        $pdo->query("SELECT 1");

        return $pdo;
    } catch (PDOException $e) {
        // Can log error here
        return null;
    }
}

// Function to get table prefix
function get_db_prefix(): string
{
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        return '';
    }
    $config = require $configPath;
    return $config['prefix'] ?? '';
}

// Function to update database configuration
function update_db_config(string $host, int $port, string $dbname, string $username, string $password, string $prefix = ''): bool
{
    $configPath = __DIR__ . '/../config/database.php';
    
    if (strtolower($host) === 'localhost') {
        $host = '127.0.0.1';
    }
    
    $config = [
        'driver' => 'mysql',
        'host' => $host,
        'port' => $port,
        'dbname' => $dbname,
        'charset' => 'utf8mb4',
        'username' => $username,
        'password' => $password,
        'prefix' => $prefix,
    ];
    
    $content = "<?php\n";
    $content .= "declare(strict_types=1);\n\n";
    $content .= "return " . var_export($config, true) . ";\n";

    return file_put_contents($configPath, $content) !== false;
}
