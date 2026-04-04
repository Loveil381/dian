<?php declare(strict_types=1); ?>
<section class="admin-users-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">用户管理</h2>
            <p class="section-note">查看用户账号、邮箱、角色与当前状态，支持直接封禁或恢复。</p>
        </div>
        <div class="section-actions">
            <span class="badge"><?php echo (int) ($userPagination['total'] ?? count($userRows)); ?> 位用户</span>
        </div>
    </div>

    <div class="admin-users-stats">
        <div class="admin-users-stat-card">
            <span class="material-symbols-outlined admin-users-stat-icon" aria-hidden="true">group</span>
            <strong class="admin-users-stat-value"><?php echo (int) ($userStats['total'] ?? 0); ?></strong>
            <span class="admin-users-stat-label">注册用户</span>
        </div>
        <div class="admin-users-stat-card admin-users-stat-card--active">
            <span class="material-symbols-outlined admin-users-stat-icon" aria-hidden="true">check_circle</span>
            <strong class="admin-users-stat-value"><?php echo (int) ($userStats['active'] ?? 0); ?></strong>
            <span class="admin-users-stat-label">正常账号</span>
        </div>
        <div class="admin-users-stat-card admin-users-stat-card--banned">
            <span class="material-symbols-outlined admin-users-stat-icon" aria-hidden="true">block</span>
            <strong class="admin-users-stat-value"><?php echo (int) ($userStats['banned'] ?? 0); ?></strong>
            <span class="admin-users-stat-label">已封禁</span>
        </div>
    </div>

    <div class="admin-users-grid">
        <div class="admin-users-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">用户列表</h3>
                    <p class="section-note">保留原有分页和状态切换逻辑，方便继续做后台运营处理。</p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">UID / 注册时间</th>
                            <th style="width: 30%;">用户名 / 邮箱</th>
                            <th style="width: 25%;">角色</th>
                            <th style="width: 25%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($userRows)): ?>
                            <tr>
                                <td colspan="4" class="meta" style="padding: 20px 10px;">暂无用户数据。</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($userRows as $user): ?>
                                <tr>
                                    <td>
                                        <div class="name">#<?php echo (int) ($user['id'] ?? 0); ?></div>
                                        <div class="meta"><?php echo shop_short_datetime((string) ($user['created_at'] ?? '')); ?></div>
                                    </td>
                                    <td>
                                        <div class="name"><?php echo shop_e((string) ($user['username'] ?? '未命名用户')); ?></div>
                                        <div class="meta"><?php echo shop_e((string) ($user['email'] ?? '未填写邮箱')); ?></div>
                                    </td>
                                    <td>
                                        <?php if ((string) ($user['role'] ?? 'user') === 'admin'): ?>
                                            <span class="admin-user-role admin-user-role-admin">管理员</span>
                                        <?php else: ?>
                                            <span class="admin-user-role admin-user-role-user">普通用户</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" class="admin-users-action">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="admin_action" value="toggle_user_status">
                                            <input type="hidden" name="id" value="<?php echo (int) ($user['id'] ?? 0); ?>">
                                            <input type="hidden" name="status" value="<?php echo (string) ($user['status'] ?? 'active') === 'active' ? 'banned' : 'active'; ?>">
                                            <?php if ((string) ($user['status'] ?? 'active') === 'active'): ?>
                                                <button class="btn btn-danger btn-sm" type="submit" data-confirm-click="确定要封禁这个用户吗？">封禁用户</button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" type="submit" data-confirm-click="确定要恢复这个用户吗？">恢复用户</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php echo shop_render_pagination($userPagination, $userPaginationUrl); ?>
        </div>
    </div>
</section>
