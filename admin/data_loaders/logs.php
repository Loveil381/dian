<?php
declare(strict_types=1);

/**
 * Logs tab 数据加载。
 * 依赖父作用域：$pdo, $prefix, $perPage, $adminUrl
 * 设置变量：$logRows, $logPagination, $logPaginationUrl, $logActionFilter,
 *           $logAdminFilter, $logActionOptions, $logAdminOptions
 */

$logsPage = max(1, (int) ($_GET['logs_page'] ?? 1));
$logActionFilter = trim((string) ($_GET['log_action'] ?? ''));
$logAdminFilter = trim((string) ($_GET['log_admin'] ?? ''));

// 构建分页 URL（保留筛选参数）
$logPaginationBase = $adminUrl . '&tab=logs';
if ($logActionFilter !== '') {
    $logPaginationBase .= '&log_action=' . urlencode($logActionFilter);
}
if ($logAdminFilter !== '') {
    $logPaginationBase .= '&log_admin=' . urlencode($logAdminFilter);
}
$logPaginationUrl = $logPaginationBase . '&logs_page=';

// 默认值
$logRows = [];
$logPagination = shop_paginate(0, $perPage, 1);
$logActionOptions = [];
$logAdminOptions = [];

if ($pdo) {
    try {
        // 获取筛选下拉选项
        $logActionOptions = $pdo->query("SELECT DISTINCT `action` FROM `{$prefix}admin_logs` ORDER BY `action` ASC")->fetchAll(PDO::FETCH_COLUMN);
        $logAdminOptions = $pdo->query("SELECT DISTINCT `admin_name` FROM `{$prefix}admin_logs` WHERE `admin_name` != '' ORDER BY `admin_name` ASC")->fetchAll(PDO::FETCH_COLUMN);

        // 构建 WHERE 条件
        $logWhere = [];
        $logParams = [];
        if ($logActionFilter !== '') {
            $logWhere[] = '`action` = ?';
            $logParams[] = $logActionFilter;
        }
        if ($logAdminFilter !== '') {
            $logWhere[] = '`admin_name` = ?';
            $logParams[] = $logAdminFilter;
        }
        $logWhereSql = $logWhere === [] ? '' : ' WHERE ' . implode(' AND ', $logWhere);

        // 总数
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}admin_logs`" . $logWhereSql);
        $countStmt->execute($logParams);
        $logTotal = (int) $countStmt->fetchColumn();

        // 分页
        $logPagination = shop_paginate($logTotal, $perPage, $logsPage);

        // 查询数据
        $dataStmt = $pdo->prepare(
            "SELECT * FROM `{$prefix}admin_logs`" . $logWhereSql . " ORDER BY `id` DESC LIMIT ? OFFSET ?"
        );
        $bindIndex = 1;
        foreach ($logParams as $param) {
            $dataStmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
        }
        $dataStmt->bindValue($bindIndex, (int) $logPagination['limit'], PDO::PARAM_INT);
        $dataStmt->bindValue($bindIndex + 1, (int) $logPagination['offset'], PDO::PARAM_INT);
        $dataStmt->execute();
        $logRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        shop_log_exception('操作日志查询失败', $e);
    }
}
