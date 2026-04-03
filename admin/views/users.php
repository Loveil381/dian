<section class="admin-users-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">用户管理</h2>
            <p class="section-note">查看注册用户、识别管理员身份，并在这里快速切换用户状态。</p>
        </div>
        <div class="section-actions">
            <span class="badge"><?php echo (int) ($userPagination['total'] ?? count($userRows)); ?> 名用户</span>
        </div>
    </div>

    <div class="admin-users-grid">
        <div class="admin-users-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">用户列表</h3>
                    <p class="section-note">保留原有用户记录和状态切换逻辑，方便继续做风控或禁用处理。</p>
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
                                <td colspan="4" class="meta" style="padding: 20px 10px;">暂无用户注册。</td>
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
                                        <div class="meta"><?php echo shop_e((string) ($user['email'] ?? '无邮箱')); ?></div>
                                    </td>
                                    <td>
                                        <?php if ((string) ($user['role'] ?? 'user') === 'admin'): ?>
                                            <span class="admin-user-role admin-user-role-admin">管理员</span>
                                        <?php else: ?>
                                            <span class="admin-user-role admin-user-role-user">普通买家</span>
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
                                                <button class="btn btn-danger btn-sm" type="submit" data-confirm-click="确定要禁用这个用户吗？">禁用用户</button>
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
