<?php declare(strict_types=1); ?>
<section class="admin-settings-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">系统设置</h2>
            <p class="section-note">这里保留当前权限入口和管理员账号修改能力，方便后续继续扩展。</p>
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
                    <h3 class="section-title">权限预设</h3>
                    <p class="section-note">当前保持原有占位逻辑，页面层先展示后续计划中的权限管理入口。</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="label">游客访问范围</span>
                    <select name="guest_access">
                        <option value="all">允许浏览全部前台</option>
                        <option value="login_only">仅登录后可访问部分页面</option>
                    </select>
                </label>

                <label class="field">
                    <span class="label">新用户审核方式</span>
                    <select name="user_verify">
                        <option value="auto">自动通过</option>
                        <option value="manual">管理员审核后通过</option>
                    </select>
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary btn-sm" type="submit">保存预设</button>
                <span class="help">当前提交后仍会走现有“开发中”提示逻辑。</span>
            </div>
        </form>

        <form method="post" class="admin-settings-card">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="change_password">

            <div class="section-head">
                <div>
                    <h3 class="section-title">管理员账号</h3>
                    <p class="section-note">支持修改管理员用户名与密码，提交成功后会要求重新登录。</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field field-full">
                    <span class="label">新管理员用户名</span>
                    <input type="text" name="new_username" placeholder="留空则仅修改密码" autocomplete="off">
                </label>

                <label class="field field-full">
                    <span class="label">新密码</span>
                    <input type="password" name="new_password" required placeholder="至少 8 位，建议使用更强密码" autocomplete="new-password">
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit" data-confirm-click="确定要更新管理员账号信息吗？提交后需要重新登录。">更新账号信息</button>
            </div>
        </form>
    </div>
</section>
