<section class="grid">
    <div class="panel section" id="admin-users">
        <div class="section-head">
            <div>
                <h2 class="section-title">注册用户群</h2>
                <p class="section-note">查看前台商城注册的买家账户。</p>
            </div>
            <div class="section-actions">
                <span class="badge"><?php echo (int) ($userPagination['total'] ?? count($userRows)); ?> 名用户</span>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 20%;">UID / 注册时间</th>
                        <th style="width: 30%;">用户名与邮箱</th>
                        <th style="width: 25%;">用户身份</th>
                        <th style="width: 25%;">封禁与操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userRows)): ?>
                        <tr><td colspan="4" class="meta" style="padding: 20px 10px;">暂无用户注册。</td></tr>
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
                                    <div class="meta">
                                        <?php echo (string) ($user['role'] ?? 'user') === 'admin' ? '<strong style="color:#0369a1;">管理员</strong>' : '普通买家'; ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="post" style="display: flex; gap: 8px;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                        <input type="hidden" name="admin_action" value="toggle_user_status">
                                        <input type="hidden" name="id" value="<?php echo (int) ($user['id'] ?? 0); ?>">
                                        <input type="hidden" name="status" value="<?php echo (string) ($user['status'] ?? 'active') === 'active' ? 'banned' : 'active'; ?>">
                                        <?php if ((string) ($user['status'] ?? 'active') === 'active'): ?>
                                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('确定要封禁该用户？');" style="padding: 4px 10px; font-size: 12px; border-radius: 4px;">封禁买家</button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" type="submit" onclick="return confirm('确定解封该用户？');" style="padding: 4px 10px; font-size: 12px; border-radius: 4px; border: 1px solid #10b981; color: #10b981; background: #d1fae5;">解封买家</button>
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
</section>
