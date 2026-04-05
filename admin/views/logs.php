<?php declare(strict_types=1); ?>
<?php
/**
 * 操作日志视图
 * 展示管理员写操作记录，支持按操作类型和管理员筛选，附分页。
 */

/**
 * 根据 action 字符串返回对应的 status-pill 修饰类名。
 */
$admin_log_action_class = static function (string $action): string {
    static $map = [
        'save_product'         => 'info',
        'save_category'        => 'info',
        'save_page'            => 'info',
        'batch_product_action' => 'info',
        'delete_order'         => 'danger',
        'delete_product'       => 'danger',
        'delete_category'      => 'danger',
        'update_order_status'  => 'success',
        'update_order'         => 'success',
        'toggle_user_status'   => 'warning',
        'save_payment'         => 'primary',
        'change_password'      => 'primary',
        'save_consult'         => 'primary',
        'save_notification'    => 'primary',
    ];
    return $map[$action] ?? 'muted';
};

/**
 * 将 action 字符串转换为可读中文标签，未定义则返回原值。
 */
$admin_log_action_label = static function (string $action): string {
    static $labels = [
        'save_product'         => '保存商品',
        'save_category'        => '保存分类',
        'save_page'            => '保存页面',
        'batch_product_action' => '批量商品',
        'delete_order'         => '删除订单',
        'delete_product'       => '删除商品',
        'delete_category'      => '删除分类',
        'update_order_status'  => '更新状态',
        'update_order'         => '更新订单',
        'toggle_user_status'   => '切换用户',
        'save_payment'         => '支付设置',
        'change_password'      => '改密码',
        'save_consult'         => '在线咨询',
        'save_notification'    => '通知设置',
    ];
    return $labels[$action] ?? $action;
};
?>

<section class="admin-log-shell">

    <!-- 页头：标题 + 记录总数徽章 -->
    <div class="section-head">
        <div>
            <h2 class="section-title admin-log-title-row">
                <span class="material-symbols-outlined" aria-hidden="true">policy</span>
                操作日志
            </h2>
            <p class="section-note">记录所有管理员的写操作，便于审计与行为溯源。</p>
        </div>
        <div class="section-actions">
            <span class="badge"><?php echo (int) ($logPagination['total'] ?? 0); ?> 条记录</span>
        </div>
    </div>

    <!-- 过滤栏 -->
    <div class="admin-log-card">
        <form method="get" class="admin-log-filter-bar">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="tab" value="logs">

            <!-- 操作类型筛选 -->
            <label class="field admin-filter-wide">
                <span class="label">操作类型</span>
                <select name="log_action">
                    <option value="">全部操作</option>
                    <?php foreach ($logActionOptions as $opt): ?>
                        <option
                            value="<?php echo shop_e((string) $opt); ?>"
                            <?php echo $logActionFilter === (string) $opt ? 'selected' : ''; ?>
                        >
                            <?php echo shop_e($admin_log_action_label((string) $opt)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- 管理员筛选 -->
            <label class="field admin-filter-medium">
                <span class="label">管理员</span>
                <select name="log_admin">
                    <option value="">全部管理员</option>
                    <?php foreach ($logAdminOptions as $opt): ?>
                        <option
                            value="<?php echo shop_e((string) $opt); ?>"
                            <?php echo $logAdminFilter === (string) $opt ? 'selected' : ''; ?>
                        >
                            <?php echo shop_e((string) $opt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button class="btn btn-secondary btn-sm" type="submit">筛选日志</button>

            <?php if ($logActionFilter !== '' || $logAdminFilter !== ''): ?>
                <a class="btn btn-soft btn-sm" href="<?php echo shop_e($adminUrl); ?>&tab=logs">清除筛选</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 日志表格 -->
    <div class="admin-log-card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 14%;">时间</th>
                        <th style="width: 16%;">管理员</th>
                        <th style="width: 14%;">操作类型</th>
                        <th style="width: 16%;">目标</th>
                        <th style="width: 28%;">详情</th>
                        <th style="width: 12%;">IP 地址</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logRows)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="admin-log-empty">
                                    <span class="material-symbols-outlined" aria-hidden="true">manage_search</span>
                                    <p>暂无操作日志记录</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logRows as $row): ?>
                            <?php
                            // 操作类型
                            $action      = (string) ($row['action'] ?? '');
                            $pill_class  = $admin_log_action_class($action);
                            $action_label = $admin_log_action_label($action);

                            // 管理员首字母头像
                            $admin_name  = (string) ($row['admin_name'] ?? '');
                            $avatar_char = mb_strtoupper(mb_substr($admin_name !== '' ? $admin_name : '?', 0, 1, 'UTF-8'), 'UTF-8');

                            // 目标信息
                            $target_type = (string) ($row['target_type'] ?? '');
                            $target_id   = (int) ($row['target_id'] ?? 0);

                            // 详情（用于截断显示 + title 完整内容）
                            $detail      = (string) ($row['detail'] ?? '');

                            // IP
                            $ip          = (string) ($row['ip'] ?? '');
                            ?>
                            <tr>
                                <!-- 时间列 -->
                                <td>
                                    <div class="name" style="font-size: var(--text-caption); font-weight: 600;">
                                        <?php echo shop_short_datetime((string) ($row['created_at'] ?? '')); ?>
                                    </div>
                                    <div class="meta">#<?php echo (int) ($row['id'] ?? 0); ?></div>
                                </td>

                                <!-- 管理员列：圆形头像 + 姓名 -->
                                <td>
                                    <div class="admin-log-avatar">
                                        <span class="admin-log-avatar-circle" aria-hidden="true">
                                            <?php echo shop_e($avatar_char); ?>
                                        </span>
                                        <span class="name" style="font-size: var(--text-caption);">
                                            <?php echo $admin_name !== '' ? shop_e($admin_name) : '<span class="meta">系统</span>'; ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- 操作类型列 -->
                                <td>
                                    <span class="status-pill <?php echo shop_e($pill_class); ?>">
                                        <?php echo shop_e($action_label); ?>
                                    </span>
                                </td>

                                <!-- 目标列 -->
                                <td>
                                    <?php if ($target_type !== ''): ?>
                                        <div class="name" style="font-size: var(--text-caption); font-weight: 600;">
                                            <?php echo shop_e($target_type); ?>
                                        </div>
                                        <?php if ($target_id > 0): ?>
                                            <div class="meta">#<?php echo $target_id; ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="meta">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- 详情列：截断 + title 显示完整内容 -->
                                <td>
                                    <?php if ($detail !== ''): ?>
                                        <span
                                            class="admin-log-detail"
                                            title="<?php echo shop_e($detail); ?>"
                                        ><?php echo shop_e($detail); ?></span>
                                    <?php else: ?>
                                        <span class="meta">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- IP 列 -->
                                <td>
                                    <?php if ($ip !== ''): ?>
                                        <span class="admin-log-ip"><?php echo shop_e($ip); ?></span>
                                    <?php else: ?>
                                        <span class="meta">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <?php echo shop_render_pagination($logPagination, $logPaginationUrl); ?>
    </div>

</section>
