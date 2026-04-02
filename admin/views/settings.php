<section class="grid">
    <div class="panel section" id="admin-roles">
        <div class="section-head">
            <div>
                <h2 class="section-title">权限组管理</h2>
                <p class="section-note">配置员工或合伙人的功能权限限制。</p>
            </div>
            <div class="section-actions">
                <span class="badge">设置页</span>
            </div>
        </div>

        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_role">
            
            <div class="form-grid">
                <label class="field"><span class="label">前台访客查阅权限</span><select name="guest_access"><option value="all">任意浏览与购买</option><option value="login_only">强制注册后可见所有界面</option></select></label>
                <label class="field"><span class="label">新买家审核</span><select name="user_verify"><option value="auto">自动通过</option><option value="manual">需管理员手动通过</option></select></label>
            </div>

            <div class="actions">
                <button class="btn btn-primary btn-sm" type="submit">更新规则</button>
                <span class="help">开发预留：未来版本将接入完善的 RBAC。</span>
            </div>
        </form>
    </div>

    <div class="section panel" id="admin-settings">
        <div class="section-head">
            <div>
                <h2 class="section-title">安全账户凭证更换</h2>
                <p class="section-note">保障资产安全，强烈建议每 90 天对系统的最高主管理单元进行密匙替换。</p>
            </div>
        </div>

        <form method="post" style="margin-top: 16px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="change_password">
            <div class="form-grid">
                <label class="field field-full">
                    <span class="label">更新最高管理账号</span>
                    <input type="text" name="new_username" placeholder="留空则不修改用户名" autocomplete="new-password">
                </label>
                <label class="field field-full">
                    <span class="label">签发新密匙串 (登入密码)</span>
                    <input type="password" name="new_password" required placeholder="建议长度大于 12 位的强组合密匙" autocomplete="new-password">
                </label>
            </div>

            <div class="actions mt-3">
                <button class="btn btn-primary" type="submit" data-confirm-click="警告：密匙修改后将立即强制下线所有已登录设备（包含正在操作的本机），需要用新账户参数重新登入，确认执行？">发放新根密匙与下发设备退库</button>
            </div>
        </form>
    </div>
</section>
