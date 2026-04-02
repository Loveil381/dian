<?php
declare(strict_types=1);

echo "<pre>";
echo "<h2>Database Connection Probe</h2>\n";

$dbUser = 'xi';
$dbPass = '0';
$dbName = 'munvxiaodian';

echo "Testing user: '{$dbUser}'\n";
echo "Testing pass: '{$dbPass}'\n";
echo "Testing db  : '{$dbName}'\n\n";

$targets = [
    'localhost',
    '127.0.0.1',
];

echo "<h3>--- PDO Tests ---</h3>\n";

foreach ($targets as $host) {
    echo "<b>Testing PDO with host: {$host}</b>\n";
    $dsn = "mysql:host={$host};port=3306;charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        echo "[+] Connected to MySQL server via PDO.\n";
        
        try {
            $pdo->exec("USE `{$dbName}`");
            echo "[+] Access to database '{$dbName}' GRANTED.\n";
        } catch (PDOException $e) {
            echo "[-] Access to database '{$dbName}' DENIED: " . $e->getMessage() . "\n";
        }
    } catch (PDOException $e) {
        echo "[-] PDO Connection FAILED: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "<h3>--- MySQLi Tests ---</h3>\n";

foreach ($targets as $host) {
    echo "<b>Testing MySQLi with host: {$host}</b>\n";
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $mysqli = new mysqli($host, $dbUser, $dbPass, "", 3306);
        if ($mysqli->connect_error) {
            echo "[-] MySQLi Connection FAILED: " . $mysqli->connect_error . "\n";
        } else {
            echo "[+] Connected to MySQL server via MySQLi.\n";
            
            if ($mysqli->select_db($dbName)) {
                echo "[+] Access to database '{$dbName}' GRANTED.\n";
            } else {
                echo "[-] Access to database '{$dbName}' DENIED: " . $mysqli->error . "\n";
            }
            $mysqli->close();
        }
    } catch (Exception $e) {
        echo "[-] MySQLi Connection FAILED (Exception): " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "</pre>";
