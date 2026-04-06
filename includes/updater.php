<?php
declare(strict_types=1);

/**
 * 更新中心核心工具库。
 *
 * 提供版本检查、备份、部署、迁移、回滚等纯工具函数。
 * 不含 HTTP 路由或表单处理——那些由 admin/controllers/update_actions.php 负责。
 *
 * 依赖：
 *   includes/db.php          — get_db_connection(), get_db_prefix(), shop_get_setting(), shop_set_setting()
 *   includes/version.php     — shop_app_version()
 *   includes/logger.php      — shop_log()
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/logger.php';

/** 更新时跳过的目录（备份和部署共用）。 */
const SHOP_UPDATE_SKIP_DIRS = [
    'config',
    'assets/uploads',
    'logs',
    'storage',
    '.git',
    '.claude',
    'vendor',
    'node_modules',
];

/** GitHub 仓库坐标。 */
const SHOP_GITHUB_REPO = 'monudexiaodian/dian';

/* ═══════════════════════════════════════════════
 * 锁与状态
 * ═══════════════════════════════════════════════ */

/**
 * 获取排他更新锁。
 *
 * 使用文件锁防止并发更新。锁文件超过 30 分钟视为过期可覆盖。
 */
function shop_update_acquire_lock(): bool
{
    $lockFile = shop_update_storage_path('update.lock');
    shop_update_ensure_dir(dirname($lockFile));

    // 检查过期锁
    if (file_exists($lockFile)) {
        $age = time() - (int) @filemtime($lockFile);
        if ($age < 1800) {
            return false; // 锁未过期
        }
        @unlink($lockFile); // 过期锁，清理
    }

    $fp = @fopen($lockFile, 'x');
    if ($fp === false) {
        return false;
    }
    fwrite($fp, json_encode([
        'pid'  => getmypid(),
        'time' => date('c'),
    ]));
    fclose($fp);
    return true;
}

/**
 * 释放更新锁。始终在 finally 块中调用。
 */
function shop_update_release_lock(): void
{
    $lockFile = shop_update_storage_path('update.lock');
    if (file_exists($lockFile)) {
        @unlink($lockFile);
    }
}

/**
 * 开启或关闭维护模式。
 *
 * 维护模式下前台显示 503 页面，后台不受影响。
 */
function shop_update_set_maintenance(bool $on): void
{
    $flag = shop_update_storage_path('maintenance.flag');
    shop_update_ensure_dir(dirname($flag));
    if ($on) {
        @file_put_contents($flag, json_encode([
            'since' => date('c'),
            'reason' => 'system_update',
        ]));
    } else {
        if (file_exists($flag)) {
            @unlink($flag);
        }
    }
}

/**
 * 写入更新进度状态（用于崩溃恢复检测）。
 */
function shop_update_write_state(array $state): void
{
    $file = shop_update_storage_path('update_state.json');
    shop_update_ensure_dir(dirname($file));
    $state['updated_at'] = date('c');
    @file_put_contents($file, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * 读取未完成的更新状态。
 */
function shop_update_read_state(): ?array
{
    $file = shop_update_storage_path('update_state.json');
    if (!file_exists($file)) {
        return null;
    }
    $data = @json_decode((string) @file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

/**
 * 清除更新状态文件。
 */
function shop_update_clear_state(): void
{
    $file = shop_update_storage_path('update_state.json');
    if (file_exists($file)) {
        @unlink($file);
    }
}

/* ═══════════════════════════════════════════════
 * 预检
 * ═══════════════════════════════════════════════ */

/**
 * 执行更新前预检。返回问题列表（空数组 = 全部通过）。
 */
function shop_update_preflight(): array
{
    $issues = [];
    $root = shop_update_root_path();

    // ZipArchive 扩展
    if (!class_exists('ZipArchive')) {
        $issues[] = '服务器缺少 ZipArchive 扩展，无法解压更新包。';
    }

    // pdo_mysql 扩展
    if (!extension_loaded('pdo_mysql')) {
        $issues[] = '服务器缺少 pdo_mysql 扩展。';
    }

    // 磁盘空间 ≥ 50MB
    $free = @disk_free_space($root);
    if ($free !== false && $free < 50 * 1024 * 1024) {
        $issues[] = sprintf('磁盘剩余空间不足（%.1f MB），至少需要 50 MB。', $free / 1024 / 1024);
    }

    // storage/ 目录可写
    $storageDir = shop_update_storage_path('');
    shop_update_ensure_dir($storageDir);
    if (!is_writable($storageDir)) {
        $issues[] = 'storage/ 目录不可写，请检查目录权限。';
    }

    // 根目录可写（需要覆盖文件）
    if (!is_writable($root)) {
        $issues[] = '项目根目录不可写，请检查目录权限。';
    }

    // 无并发锁
    $lockFile = shop_update_storage_path('update.lock');
    if (file_exists($lockFile)) {
        $age = time() - (int) @filemtime($lockFile);
        if ($age < 1800) {
            $issues[] = '另一个更新操作正在进行中，请稍后重试。';
        }
    }

    return $issues;
}

/* ═══════════════════════════════════════════════
 * HTTP 请求
 * ═══════════════════════════════════════════════ */

/**
 * HTTP GET 请求。先尝试 file_get_contents，失败 fallback cURL。
 *
 * GitHub API 强制要求 User-Agent，否则 403。
 */
function shop_update_http_get(string $url): string|false
{
    $userAgent = 'DianShop/' . shop_app_version();
    $timeout = 15;

    // 方式一：file_get_contents
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "User-Agent: {$userAgent}\r\nAccept: application/vnd.github+json\r\n",
                'timeout' => $timeout,
                'follow_location' => 1,
                'max_redirects'   => 5,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response !== false) {
            return $response;
        }
    }

    // 方式二：cURL
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_HTTPHEADER     => ['Accept: application/vnd.github+json'],
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode >= 200 && $httpCode < 400) {
            return (string) $response;
        }
    }

    return false;
}

/**
 * 流式下载文件到磁盘。不占内存。
 *
 * GitHub zipball_url 会 302 重定向，必须 follow。
 */
function shop_update_download_file(string $url, string $destPath): bool
{
    shop_update_ensure_dir(dirname($destPath));
    $userAgent = 'DianShop/' . shop_app_version();

    // 方式一：cURL（首选，流式写入）
    if (function_exists('curl_init')) {
        $fp = @fopen($destPath, 'wb');
        if ($fp === false) {
            return false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_FILE           => $fp,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($ok !== false && $httpCode >= 200 && $httpCode < 400 && filesize($destPath) > 0) {
            return true;
        }
        @unlink($destPath);
        // fallthrough to file_get_contents
    }

    // 方式二：file_get_contents
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "User-Agent: {$userAgent}\r\n",
                'timeout' => 300,
                'follow_location' => 1,
                'max_redirects'   => 10,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $data = @file_get_contents($url, false, $context);
        if ($data !== false && strlen($data) > 0) {
            if (@file_put_contents($destPath, $data) !== false) {
                return true;
            }
        }
    }

    @unlink($destPath);
    return false;
}

/* ═══════════════════════════════════════════════
 * 备份
 * ═══════════════════════════════════════════════ */

/**
 * 创建完整备份（应用文件 + 数据库导出）。
 *
 * @return string|false 成功返回备份文件路径，失败返回 false
 */
function shop_update_create_backup(string $backupDir): string|false
{
    if (!class_exists('ZipArchive')) {
        return false;
    }

    @set_time_limit(300);
    shop_update_ensure_dir($backupDir);

    $version = shop_app_version();
    $filename = sprintf('backup_v%s_%s.zip', $version, date('Ymd_His'));
    $zipPath = rtrim($backupDir, '/\\') . '/' . $filename;

    $zip = new ZipArchive();
    $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($result !== true) {
        shop_log('error', '创建备份 zip 失败', ['path' => $zipPath, 'code' => $result]);
        return false;
    }

    $rootDir = shop_update_root_path();

    try {
        // 添加应用文件
        shop_update_add_dir_to_zip($zip, $rootDir, $rootDir, SHOP_UPDATE_SKIP_DIRS);

        // 添加数据库导出
        $dbDump = shop_update_dump_database();
        if ($dbDump !== false) {
            $zip->addFromString('_db_backup/database_dump.sql', $dbDump);
        }

        $zip->close();
    } catch (\Throwable $e) {
        @$zip->close();
        @unlink($zipPath);
        shop_log('error', '备份过程异常', ['message' => $e->getMessage()]);
        return false;
    }

    // 验证备份完整性
    if (!shop_update_verify_backup($zipPath)) {
        @unlink($zipPath);
        return false;
    }

    // 清理旧备份
    shop_update_prune_backups($backupDir, 5);

    shop_log('info', '备份创建成功', ['file' => $filename, 'size' => filesize($zipPath)]);
    return $zipPath;
}

/**
 * 递归添加目录到 ZipArchive，跳过保护目录。
 */
function shop_update_add_dir_to_zip(ZipArchive $zip, string $dir, string $rootDir, array $skipDirs): void
{
    $rootDir = rtrim(str_replace('\\', '/', $rootDir), '/') . '/';
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $realPath = str_replace('\\', '/', $file->getRealPath());
        $relativePath = str_replace($rootDir, '', $realPath);

        // 跳过保护目录
        $skip = false;
        foreach ($skipDirs as $skipDir) {
            if (str_starts_with($relativePath, $skipDir . '/') || $relativePath === $skipDir) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            continue;
        }

        $zip->addFile($file->getRealPath(), $relativePath);
    }
}

/**
 * 纯 PHP 数据库导出。
 *
 * 导出所有带前缀的表：CREATE TABLE + INSERT INTO。
 * 正确处理 NULL、二进制数据、字符集。
 */
function shop_update_dump_database(): string|false
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return false;
    }

    try {
        $sql = "-- 魔女的小店数据库备份\n";
        $sql .= "-- 时间: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- 版本: " . shop_app_version() . "\n";
        $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        // 获取所有带前缀的表
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($prefix . '%'));
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // CREATE TABLE
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch();
            $createSql = $createRow['Create Table'] ?? '';
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSql . ";\n\n";

            // INSERT DATA
            $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                continue;
            }

            $columns = array_keys($rows[0]);
            $colList = '`' . implode('`, `', $columns) . '`';

            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = $pdo->quote((string) $val);
                    }
                }
                $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $sql;
    } catch (PDOException $e) {
        shop_log('error', '数据库导出失败', ['message' => $e->getMessage()]);
        return false;
    }
}

/**
 * 验证备份 zip 完整性。
 */
function shop_update_verify_backup(string $zipPath): bool
{
    if (!file_exists($zipPath)) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
        shop_log('error', '备份验证失败：无法打开', ['path' => $zipPath]);
        return false;
    }

    $count = $zip->numFiles;
    $hasIndex = ($zip->locateName('index.php') !== false);
    $hasDbDump = ($zip->locateName('_db_backup/database_dump.sql') !== false);
    $zip->close();

    if ($count < 20) {
        shop_log('error', '备份验证失败：文件数量异常', ['count' => $count]);
        return false;
    }

    if (!$hasIndex) {
        shop_log('error', '备份验证失败：缺少 index.php');
        return false;
    }

    // database_dump.sql 是可选的（DB 导出可能失败），不做硬性要求
    return true;
}

/**
 * 保留最近 N 个备份，删除旧的。
 */
function shop_update_prune_backups(string $dir, int $keep = 5): void
{
    $files = glob(rtrim($dir, '/\\') . '/backup_v*.zip');
    if (!is_array($files) || count($files) <= $keep) {
        return;
    }

    // 按修改时间降序
    usort($files, function (string $a, string $b): int {
        return (int) filemtime($b) - (int) filemtime($a);
    });

    // 删除超额的
    $toDelete = array_slice($files, $keep);
    foreach ($toDelete as $file) {
        @unlink($file);
    }
}

/* ═══════════════════════════════════════════════
 * 部署
 * ═══════════════════════════════════════════════ */

/**
 * 验证下载的 zip 文件完整性。
 */
function shop_update_verify_zip(string $zipPath): bool
{
    if (!file_exists($zipPath)) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
        return false;
    }

    $count = $zip->numFiles;
    $zip->close();

    return $count >= 10;
}

/**
 * 找到 GitHub zipball 解压后的实际源码根目录。
 *
 * GitHub 的 zipball 会包一层目录（如 monudexiaodian-dian-abc1234/）。
 */
function shop_update_find_source_dir(string $extractDir): string|false
{
    $extractDir = rtrim($extractDir, '/\\');
    $entries = @scandir($extractDir);
    if (!is_array($entries)) {
        return false;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $extractDir . '/' . $entry;
        if (is_dir($path) && file_exists($path . '/index.php')) {
            return $path;
        }
    }

    // 可能没有包装层，直接检查当前目录
    if (file_exists($extractDir . '/index.php')) {
        return $extractDir;
    }

    return false;
}

/**
 * 两阶段部署：先全量复制到 staging → 验证 → 再覆盖到根目录。
 *
 * 缩小危险窗口：从"逐文件覆盖活目录"变为"先准备好再快速覆盖"。
 */
function shop_update_staged_deploy(string $srcDir, string $rootDir, array $skipDirs): bool
{
    $stagingDir = shop_update_storage_path('update_staging');

    // 清理旧 staging
    if (is_dir($stagingDir)) {
        shop_update_rmdir_recursive($stagingDir);
    }

    // 阶段一：复制到 staging
    shop_update_copy_dir($srcDir, $stagingDir, $skipDirs);

    // 验证 staging 完整性
    if (!file_exists($stagingDir . '/index.php')) {
        shop_log('error', 'Staging 验证失败：缺少 index.php');
        shop_update_rmdir_recursive($stagingDir);
        return false;
    }

    // 阶段二：从 staging 覆盖到根目录
    shop_update_copy_dir($stagingDir, $rootDir, $skipDirs);

    // 清理 staging
    shop_update_rmdir_recursive($stagingDir);

    return true;
}

/**
 * 递归复制目录，跳过保护目录。
 */
function shop_update_copy_dir(string $src, string $dst, array $skipDirs): void
{
    $src = rtrim(str_replace('\\', '/', $src), '/') . '/';
    $dst = rtrim(str_replace('\\', '/', $dst), '/') . '/';

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = str_replace($src, '', str_replace('\\', '/', $item->getPathname()));

        // 跳过保护目录
        $skip = false;
        foreach ($skipDirs as $skipDir) {
            if (str_starts_with($relativePath, $skipDir . '/') || $relativePath === $skipDir) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            continue;
        }

        $targetPath = $dst . $relativePath;

        if ($item->isDir()) {
            shop_update_ensure_dir($targetPath);
        } else {
            shop_update_ensure_dir(dirname($targetPath));
            if (!@copy($item->getRealPath(), $targetPath)) {
                throw new \RuntimeException("文件复制失败: {$relativePath}");
            }
        }
    }
}

/**
 * 递归删除目录及所有内容。
 */
function shop_update_rmdir_recursive(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @rmdir($item->getRealPath());
        } else {
            @unlink($item->getRealPath());
        }
    }
    @rmdir($dir);
}

/* ═══════════════════════════════════════════════
 * 迁移
 * ═══════════════════════════════════════════════ */

/**
 * 确保迁移跟踪表存在。
 *
 * 首次运行时：创建表 + 将所有已有迁移文件标记为已应用（避免重复执行）。
 */
function shop_update_ensure_migrations_table(): void
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return;
    }

    try {
        // 检查表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($prefix . 'migrations'));
        if ($stmt->rowCount() > 0) {
            return; // 已存在
        }

        // 创建表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}migrations` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration`  VARCHAR(255) NOT NULL UNIQUE,
            `applied_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        shop_log('info', '迁移跟踪表已创建');
    } catch (PDOException $e) {
        shop_log('error', '创建迁移跟踪表失败', ['message' => $e->getMessage()]);
    }
}

/**
 * 执行未运行的数据库迁移。
 *
 * @return string 空字符串 = 全部成功；非空 = 错误描述
 */
function shop_update_run_migrations(): string
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return '数据库连接失败，无法执行迁移。';
    }

    $migrationDir = shop_update_root_path() . '/database/migrations';
    $files = glob($migrationDir . '/*.sql');
    if (!is_array($files) || empty($files)) {
        return '';
    }
    sort($files); // 按文件名排序确保顺序

    try {
        // 获取已应用的迁移
        $stmt = $pdo->query("SELECT `migration` FROM `{$prefix}migrations`");
        $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $appliedSet = array_flip($applied);
    } catch (PDOException $e) {
        return '读取迁移记录失败: ' . $e->getMessage();
    }

    $insertStmt = $pdo->prepare("INSERT INTO `{$prefix}migrations` (`migration`) VALUES (?)");

    foreach ($files as $filePath) {
        $name = basename($filePath);
        if (isset($appliedSet[$name])) {
            continue; // 已执行
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return "无法读取迁移文件: {$name}";
        }

        // 替换前缀占位符（兼容大小写）
        $content = str_replace(['{PREFIX}', '{prefix}'], [$prefix, $prefix], $content);

        // 去掉 SQL 注释
        $content = preg_replace('/^--.*$/m', '', $content);

        // 按分号拆分语句
        $statements = array_filter(array_map('trim', explode(';', $content)));

        try {
            foreach ($statements as $statement) {
                if ($statement === '') {
                    continue;
                }
                try {
                    $pdo->exec($statement);
                } catch (PDOException $stmtEx) {
                    // 容错：列/表已存在属于幂等情况，跳过而非中断
                    $code = (int) $stmtEx->errorInfo[1];
                    if ($code === 1060 || $code === 1050 || $code === 1061) {
                        // 1060 = Duplicate column name, 1050 = Table already exists, 1061 = Duplicate key name
                        shop_log('info', '迁移语句跳过（目标已存在）', [
                            'migration' => $name,
                            'code'      => $code,
                        ]);
                        continue;
                    }
                    throw $stmtEx;
                }
            }

            // 记录为已应用
            $insertStmt->execute([$name]);

            shop_log('info', '迁移已执行', ['migration' => $name]);
        } catch (PDOException $e) {
            shop_log('error', '迁移执行失败', [
                'migration' => $name,
                'message'   => $e->getMessage(),
            ]);
            return "迁移 {$name} 执行失败: " . $e->getMessage();
        }
    }

    return '';
}

/* ═══════════════════════════════════════════════
 * 健康检查
 * ═══════════════════════════════════════════════ */

/**
 * 更新后健康检查。返回问题列表（空 = 健康）。
 */
function shop_update_health_check(): array
{
    $issues = [];
    $root = shop_update_root_path();

    // 关键文件存在性
    $criticalFiles = ['index.php', 'includes/db.php', 'includes/version.php', 'includes/csrf.php'];
    foreach ($criticalFiles as $file) {
        if (!file_exists($root . '/' . $file)) {
            $issues[] = "关键文件缺失: {$file}";
        }
    }

    // 数据库连接
    $pdo = get_db_connection();
    if (!$pdo) {
        $issues[] = '数据库连接失败。';
    }

    return $issues;
}

/* ═══════════════════════════════════════════════
 * 审计日志
 * ═══════════════════════════════════════════════ */

/**
 * 追加更新操作记录。
 */
function shop_update_log_history(array $entry): void
{
    $file = shop_update_storage_path('update_history.json');
    shop_update_ensure_dir(dirname($file));

    $entry['timestamp'] = date('c');
    $history = shop_update_read_history();
    array_unshift($history, $entry); // 最新在前

    // 最多保留 50 条
    $history = array_slice($history, 0, 50);

    @file_put_contents($file, json_encode($history, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * 读取更新历史记录。
 */
function shop_update_read_history(): array
{
    $file = shop_update_storage_path('update_history.json');
    if (!file_exists($file)) {
        return [];
    }
    $data = @json_decode((string) @file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/* ═══════════════════════════════════════════════
 * 路径工具
 * ═══════════════════════════════════════════════ */

/**
 * 获取项目根目录。
 */
function shop_update_root_path(): string
{
    return dirname(__DIR__);
}

/**
 * 获取 storage/ 下的路径。
 */
function shop_update_storage_path(string $subPath = ''): string
{
    $base = shop_update_root_path() . '/storage';
    if ($subPath === '') {
        return $base;
    }
    return $base . '/' . ltrim($subPath, '/\\');
}

/**
 * 确保目录存在。
 */
function shop_update_ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

/**
 * 列出备份文件信息。
 *
 * @return list<array{filename: string, filepath: string, version: string, timestamp: string, size: int}>
 */
function shop_update_list_backups(): array
{
    $dir = shop_update_storage_path('backups');
    if (!is_dir($dir)) {
        return [];
    }

    $files = glob($dir . '/backup_v*.zip');
    if (!is_array($files) || empty($files)) {
        return [];
    }

    // 按修改时间降序
    usort($files, function (string $a, string $b): int {
        return (int) filemtime($b) - (int) filemtime($a);
    });

    $list = [];
    foreach ($files as $file) {
        $name = basename($file);
        // 解析 backup_v{version}_{YYYYMMDD_HHMMSS}.zip
        $version = '未知';
        $timestamp = '';
        if (preg_match('/^backup_v(.+?)_(\d{8}_\d{6})\.zip$/', $name, $m)) {
            $version = $m[1];
            $ts = $m[2];
            // 20260405_123456 → 2026-04-05 12:34:56
            $timestamp = substr($ts, 0, 4) . '-' . substr($ts, 4, 2) . '-' . substr($ts, 6, 2)
                . ' ' . substr($ts, 9, 2) . ':' . substr($ts, 11, 2) . ':' . substr($ts, 13, 2);
        }

        $list[] = [
            'filename'  => $name,
            'filepath'  => $file,
            'version'   => $version,
            'timestamp' => $timestamp,
            'size'      => (int) @filesize($file),
        ];
    }

    return $list;
}
