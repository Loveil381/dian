<section class="admin-settings-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">系统设置</h2>
            <p class="section-note">管理前台访客权限、新买家审核方式以及后台登录凭据。</p>
        </div>
        <div class="section-actions">
            <span class="badge">System</span>
        </div>
    </div>

    <div class="admin-settings-grid">
        <form method="post" class="admin-settings-card">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_role">

            <div class="section-head">
                <div>
                    <h3 class="section-title">权限策略</h3>
                    <p class="section-note">设置访客是否可以直接浏览，并决定新买家是否需要人工审核。</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="label">前台访客查阅权限</span>
                    <select name="guest_access">
                        <option value="all">任意浏览与购买</option>
                        <option value="login_only">强制注册后可见所有界面</option>
                    </select>
                </label>

                <label class="field">
                    <span class="label">新买家审核</span>
                    <select name="user_verify">
                        <option value="auto">自动通过</option>
                        <option value="manual">需管理员手动通过</option>
                    </select>
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary btn-sm" type="submit">保存权限</button>
                <span class="help">建议在公开销售前先确认访客策略与审核流程。</span>
            </div>
        </form>

        <form method="post" class="admin-settings-card">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="change_password">

            <div class="section-head">
                <div>
                    <h3 class="section-title">登录凭据</h3>
                    <p class="section-note">可在这里更新后台用户名和密码，适合交接或安全加固时使用。</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field field-full">
                    <span class="label">新的管理员用户名</span>
                    <input type="text" name="new_username" placeholder="留空则不修改用户名" autocomplete="new-password">
                </label>

                <label class="field field-full">
                    <span class="label">新密码</span>
                    <input type="password" name="new_password" required placeholder="建议长度大于 12 位的强组合密钥" autocomplete="new-password">
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit" data-confirm-click="确定要更新管理员登录信息吗？">更新凭据</button>
            </div>
        </form>
    </div>
</section>
