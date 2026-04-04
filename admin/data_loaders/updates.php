<?php
declare(strict_types=1);

/**
 * Updates tab 数据加载。
 *
 * 依赖父作用域：$pdo, $prefix
 * 设置变量：$updateInfo, $backupList, $updateHistory, $incompleteUpdate
 *
 * 注意：此文件不调用 GitHub API，只读本地缓存和文件系统。
 */

require_once dirname(__DIR__, 2) . '/includes/updater.php';

// ── 版本与缓存信息 ──

$currentVersion = shop_app_version();
$settings = shop_get_settings(['update_cached_release', 'update_last_check']);
$lastChecked = $settings['update_last_check'] ?? '';
$cachedRelease = @json_decode($settings['update_cached_release'] ?? '', true);

$updateInfo = [
    'current_version' => $currentVersion,
    'latest_version'  => '',
    'has_update'      => false,
    'release_notes'   => '',
    'release_url'     => '',
    'published_at'    => '',
    'last_checked'    => $lastChecked,
    'check_error'     => '',
];

if (is_array($cachedRelease) && !empty($cachedRelease['tag_name'])) {
    $remoteVersion = ltrim($cachedRelease['tag_name'], 'vV');
    $updateInfo['latest_version'] = $remoteVersion;
    $updateInfo['has_update'] = version_compare($remoteVersion, $currentVersion, '>');
    $updateInfo['release_notes'] = (string) ($cachedRelease['body'] ?? '');
    $updateInfo['release_url'] = (string) ($cachedRelease['html_url'] ?? '');
    $updateInfo['published_at'] = (string) ($cachedRelease['published_at'] ?? '');
}

// ── 备份列表 ──

$backupList = shop_update_list_backups();

// ── 更新历史 ──

$updateHistory = shop_update_read_history();

// ── 崩溃恢复检测 ──

$incompleteUpdate = shop_update_read_state();
