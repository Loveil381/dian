<?php
declare(strict_types=1);

/**
 * 更新中心 action handlers。
 *
 * 薄编排层：调用 includes/updater.php 工具函数，处理返回值，
 * 按照项目约定返回 [$message, $type]。
 *
 * 依赖：
 *   includes/updater.php        — 核心工具库
 *   admin/includes/helpers.php  — shop_admin_post_string()
 */

require_once dirname(__DIR__, 2) . '/includes/updater.php';

/**
 * 检查 GitHub 是否有新版本。
 */
function handle_check_update(): array
{
    $apiUrl = 'https://api.github.com/repos/' . SHOP_GITHUB_REPO . '/releases/latest';
    $response = shop_update_http_get($apiUrl);

    if ($response === false) {
        return ['无法连接到 GitHub，请检查服务器网络或稍后重试。', 'error'];
    }

    $data = @json_decode($response, true);
    if (!is_array($data) || !isset($data['tag_name'])) {
        // 可能是 API 限流
        $message = $data['message'] ?? '响应格式异常';
        if (str_contains($message, 'rate limit')) {
            return ['GitHub API 请求次数已达上限，请稍后再试。', 'error'];
        }
        return ['GitHub 响应格式异常: ' . mb_substr($message, 0, 100), 'error'];
    }

    // 缓存到 settings 表（只保留必要字段）
    $cached = [
        'tag_name'     => (string) $data['tag_name'],
        'body'         => mb_substr((string) ($data['body'] ?? ''), 0, 5000),
        'html_url'     => (string) ($data['html_url'] ?? ''),
        'published_at' => (string) ($data['published_at'] ?? ''),
        'zipball_url'  => (string) ($data['zipball_url'] ?? ''),
    ];

    shop_set_setting('update_cached_release', json_encode($cached, JSON_UNESCAPED_UNICODE));
    shop_set_setting('update_last_check', date('Y-m-d H:i:s'));

    // 比较版本
    $remoteVersion = ltrim($cached['tag_name'], 'vV');
    $localVersion = shop_app_version();

    if (version_compare($remoteVersion, $localVersion, '>')) {
        return ["发现新版本 v{$remoteVersion}（当前 v{$localVersion}）。", 'success'];
    }

    return ["当前已是最新版本 v{$localVersion}。", 'success'];
}

/**
 * 手动创建备份。
 */
function handle_create_backup(): array
{
    if (!shop_update_acquire_lock()) {
        return ['另一个操作正在进行中，请稍后重试。', 'error'];
    }

    try {
        $backupDir = shop_update_storage_path('backups');
        $result = shop_update_create_backup($backupDir);

        if ($result === false) {
            return ['备份创建失败，请检查服务器日志。', 'error'];
        }

        $sizeMB = sprintf('%.1f', filesize($result) / 1024 / 1024);
        return ["备份成功：" . basename($result) . "（{$sizeMB} MB）", 'success'];
    } finally {
        shop_update_release_lock();
    }
}

/**
 * 一键更新（核心流程）。
 *
 * 流程：预检 → 备份 → 维护模式 → 下载 → 验证 → 部署 → 迁移 → 健康检查 → 完成
 * 任何步骤失败 → 自动回滚 → 关闭维护模式 → 释放锁
 */
function handle_apply_update(): array
{
    // 读取缓存的 release 信息
    $cachedJson = shop_get_setting('update_cached_release');
    $cached = @json_decode($cachedJson, true);
    if (!is_array($cached) || empty($cached['tag_name']) || empty($cached['zipball_url'])) {
        return ['请先点击"检查更新"获取版本信息。', 'error'];
    }

    $remoteVersion = ltrim($cached['tag_name'], 'vV');
    $localVersion = shop_app_version();
    if (!version_compare($remoteVersion, $localVersion, '>')) {
        return ["当前已是最新版本 v{$localVersion}，无需更新。", 'success'];
    }

    // 预检
    $issues = shop_update_preflight();
    if (!empty($issues)) {
        return ['更新预检未通过：' . implode(' ', $issues), 'error'];
    }

    // 获取锁
    if (!shop_update_acquire_lock()) {
        return ['另一个更新操作正在进行中，请稍后重试。', 'error'];
    }

    @set_time_limit(300);
    $backupPath = null;
    $rootDir = shop_update_root_path();

    try {
        // 步骤 1：创建备份
        shop_update_write_state(['step' => 'backup', 'target_version' => $remoteVersion]);
        $backupDir = shop_update_storage_path('backups');
        $backupPath = shop_update_create_backup($backupDir);
        if ($backupPath === false) {
            return ['备份创建失败，更新已中止。请检查磁盘空间和目录权限。', 'error'];
        }

        // 步骤 2：开启维护模式
        shop_update_set_maintenance(true);

        // 步骤 3：下载
        shop_update_write_state(['step' => 'download', 'target_version' => $remoteVersion]);
        $zipPath = shop_update_storage_path('tmp/update_' . date('Ymd_His') . '.zip');
        if (!shop_update_download_file($cached['zipball_url'], $zipPath)) {
            return ['下载更新包失败，请检查服务器网络配置。', 'error'];
        }

        // 步骤 4：验证下载
        if (!shop_update_verify_zip($zipPath)) {
            @unlink($zipPath);
            return ['更新包验证失败（文件可能损坏），请重试。', 'error'];
        }

        // 步骤 5：解压
        shop_update_write_state(['step' => 'extract', 'target_version' => $remoteVersion]);
        $extractDir = shop_update_storage_path('tmp/extract_' . date('Ymd_His'));
        shop_update_ensure_dir($extractDir);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            @unlink($zipPath);
            return ['解压更新包失败。', 'error'];
        }
        $zip->extractTo($extractDir);
        $zip->close();
        @unlink($zipPath); // 释放磁盘空间

        // 找到源码根目录
        $sourceDir = shop_update_find_source_dir($extractDir);
        if ($sourceDir === false) {
            shop_update_rmdir_recursive($extractDir);
            return ['更新包结构异常：找不到 index.php。', 'error'];
        }

        // 步骤 6：两阶段部署
        shop_update_write_state(['step' => 'deploy', 'target_version' => $remoteVersion]);
        if (!shop_update_staged_deploy($sourceDir, $rootDir, SHOP_UPDATE_SKIP_DIRS)) {
            // 部署失败，从备份回滚
            shop_update_rollback_from_backup($backupPath, $rootDir);
            shop_update_rmdir_recursive($extractDir);
            return ['文件部署失败，已自动回滚到更新前版本。', 'error'];
        }
        shop_update_rmdir_recursive($extractDir);

        // 步骤 7：执行迁移
        shop_update_write_state(['step' => 'migrate', 'target_version' => $remoteVersion]);
        shop_update_ensure_migrations_table();
        $migrationError = shop_update_run_migrations();
        if ($migrationError !== '') {
            // 迁移失败，从备份回滚文件
            shop_update_rollback_from_backup($backupPath, $rootDir);
            return ["数据库迁移失败（{$migrationError}），已自动回滚文件。请联系开发者。", 'error'];
        }

        // 步骤 8：健康检查
        $healthIssues = shop_update_health_check();
        if (!empty($healthIssues)) {
            shop_update_rollback_from_backup($backupPath, $rootDir);
            return ['更新后健康检查失败：' . implode(' ', $healthIssues) . '。已自动回滚。', 'error'];
        }

        // 步骤 9：更新版本记录
        shop_set_setting('app_version', $remoteVersion);
        shop_update_log_history([
            'action'       => 'update',
            'from_version' => $localVersion,
            'to_version'   => $remoteVersion,
            'status'       => 'success',
        ]);

        return ["更新成功！v{$localVersion} → v{$remoteVersion}", 'success'];

    } catch (\Throwable $e) {
        shop_log('error', '更新过程异常', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

        // 尝试从备份回滚
        if ($backupPath !== null && file_exists($backupPath)) {
            shop_update_rollback_from_backup($backupPath, $rootDir);
        }

        shop_update_log_history([
            'action'       => 'update',
            'from_version' => $localVersion,
            'to_version'   => $remoteVersion,
            'status'       => 'failed',
            'error'        => $e->getMessage(),
        ]);

        return ['更新过程出现异常，已尝试自动回滚。错误: ' . mb_substr($e->getMessage(), 0, 200), 'error'];

    } finally {
        shop_update_set_maintenance(false);
        shop_update_clear_state();
        shop_update_release_lock();

        // 清理可能残留的临时目录
        $tmpDir = shop_update_storage_path('tmp');
        if (is_dir($tmpDir)) {
            shop_update_rmdir_recursive($tmpDir);
        }
    }
}

/**
 * 从备份回滚。
 */
function handle_rollback_update(): array
{
    $backupFile = shop_admin_post_string('backup_file');
    if ($backupFile === '') {
        return ['未指定备份文件。', 'error'];
    }

    // 路径安全校验
    $backupFile = basename($backupFile); // 防路径穿越
    $backupDir = shop_update_storage_path('backups');
    $fullPath = $backupDir . '/' . $backupFile;

    if (!file_exists($fullPath) || !str_ends_with($backupFile, '.zip')) {
        return ['备份文件不存在或格式不正确。', 'error'];
    }

    // realpath 前缀校验
    $realBackupDir = realpath($backupDir);
    $realPath = realpath($fullPath);
    if ($realBackupDir === false || $realPath === false || !str_starts_with($realPath, $realBackupDir)) {
        return ['备份文件路径校验失败。', 'error'];
    }

    if (!shop_update_acquire_lock()) {
        return ['另一个操作正在进行中，请稍后重试。', 'error'];
    }

    @set_time_limit(300);
    $oldVersion = shop_app_version();

    try {
        shop_update_set_maintenance(true);

        $rootDir = shop_update_root_path();
        shop_update_rollback_from_backup($fullPath, $rootDir);

        // 从文件名解析版本
        $restoredVersion = $oldVersion;
        if (preg_match('/^backup_v(.+?)_\d{8}_\d{6}\.zip$/', $backupFile, $m)) {
            $restoredVersion = $m[1];
        }

        shop_set_setting('app_version', $restoredVersion);
        shop_update_log_history([
            'action'       => 'rollback',
            'from_version' => $oldVersion,
            'to_version'   => $restoredVersion,
            'status'       => 'success',
            'backup_file'  => $backupFile,
        ]);

        return ["已回滚到 v{$restoredVersion}（来自备份 {$backupFile}）。", 'success'];

    } catch (\Throwable $e) {
        shop_log('error', '回滚失败', ['message' => $e->getMessage()]);
        return ['回滚过程出现错误: ' . mb_substr($e->getMessage(), 0, 200), 'error'];
    } finally {
        shop_update_set_maintenance(false);
        shop_update_release_lock();
    }
}

/**
 * 删除备份文件。
 */
function handle_delete_backup(): array
{
    $backupFile = shop_admin_post_string('backup_file');
    if ($backupFile === '') {
        return ['未指定备份文件。', 'error'];
    }

    $backupFile = basename($backupFile);
    $backupDir = shop_update_storage_path('backups');
    $fullPath = $backupDir . '/' . $backupFile;

    if (!file_exists($fullPath) || !str_ends_with($backupFile, '.zip')) {
        return ['备份文件不存在。', 'error'];
    }

    $realBackupDir = realpath($backupDir);
    $realPath = realpath($fullPath);
    if ($realBackupDir === false || $realPath === false || !str_starts_with($realPath, $realBackupDir)) {
        return ['路径校验失败。', 'error'];
    }

    if (@unlink($fullPath)) {
        return ["已删除备份 {$backupFile}。", 'success'];
    }
    return ['删除失败，请检查文件权限。', 'error'];
}

/* ─── 内部辅助 ─── */

/**
 * 从备份 zip 恢复文件到根目录。
 */
function shop_update_rollback_from_backup(string $zipPath, string $rootDir): void
{
    $extractDir = shop_update_storage_path('tmp/rollback_' . date('Ymd_His'));
    shop_update_ensure_dir($extractDir);

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new \RuntimeException('无法打开备份文件: ' . basename($zipPath));
    }
    $zip->extractTo($extractDir);
    $zip->close();

    // 备份 zip 没有包装层，直接就是应用文件
    shop_update_sync_dir($extractDir, $rootDir, SHOP_UPDATE_SKIP_DIRS);
    shop_update_rmdir_recursive($extractDir);
}
