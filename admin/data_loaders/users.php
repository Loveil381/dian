<?php
declare(strict_types=1);

/**
 * Users tab 数据加载。
 * 依赖父作用域：$pdo, $prefix, $perPage, $adminUrl, $editingUser
 * 设置变量：$userRows, $userPagination, $userPaginationUrl, $selectedUserForm
 */

if ($editingUser !== null) {
    $selectedUserForm = $editingUser;
}

$usersPage = max(1, (int) ($_GET['users_page'] ?? 1));
$userPaginationUrl = $adminUrl . '&tab=users&users_page=';
$userStats = ['total' => 0, 'active' => 0, 'banned' => 0];

if ($pdo) {
    try {
        $userTotal = (int) $pdo->query("SELECT COUNT(*) FROM `{$prefix}users`")->fetchColumn();
        $userStats['total'] = $userTotal;
        $statsStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM `{$prefix}users` GROUP BY status");
        if ($statsStmt) {
            while ($sRow = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
                $sKey = trim((string) ($sRow['status'] ?? ''));
                if ($sKey === 'active') {
                    $userStats['active'] = (int) $sRow['cnt'];
                } elseif ($sKey === 'banned') {
                    $userStats['banned'] = (int) $sRow['cnt'];
                }
            }
        }
        $userPagination = shop_paginate($userTotal, $perPage, $usersPage);
        $userStmt = $pdo->prepare("SELECT * FROM `{$prefix}users` ORDER BY id ASC LIMIT ? OFFSET ?");
        $userStmt->bindValue(1, (int) $userPagination['limit'], PDO::PARAM_INT);
        $userStmt->bindValue(2, (int) $userPagination['offset'], PDO::PARAM_INT);
        $userStmt->execute();
        $userRows = array_map(static function (array $row): array {
            $normalized = shop_normalize_user($row, (int) ($row['id'] ?? 0));
            $normalized['created_at'] = (string) ($row['created_at'] ?? '');
            return $normalized;
        }, $userStmt->fetchAll());
    } catch (PDOException $e) {
        shop_log_exception('用户分页查询失败', $e);
        $allUsers = shop_get_users();
        $userPagination = shop_paginate(count($allUsers), $perPage, $usersPage);
        $userRows = array_slice($allUsers, (int) $userPagination['offset'], (int) $userPagination['limit']);
    }
} else {
    $allUsers = shop_get_users();
    $userPagination = shop_paginate(count($allUsers), $perPage, $usersPage);
    $userRows = array_slice($allUsers, (int) $userPagination['offset'], (int) $userPagination['limit']);
}
