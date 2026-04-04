<?php declare(strict_types=1); ?>
<section class="admin-updates-shell">

    <div class="section-head">
        <div>
            <h2 class="section-title">更新中心</h2>
            <p class="section-note">检查新版本、管理备份与回滚。当前版本 <strong>v<?php echo shop_e($updateInfo['current_version']); ?></strong></p>
        </div>
    </div>

    <?php if ($incompleteUpdate !== null): ?>
    <div class="flash error admin-flash">
        检测到未完成的更新操作（步骤: <?php echo shop_e($incompleteUpdate['step'] ?? '未知'); ?>）。
        建议从下方备份列表进行回滚。
    </div>
    <?php endif; ?>

    <!-- ── 版本状态 + 检查 ── -->
    <div class="admin-settings-grid" style="margin-bottom:var(--space-xl,1.5rem);">
        <div class="admin-settings-card" style="padding:var(--space-lg,1.25rem);">
            <h3 style="margin:0 0 var(--space-md,.75rem) 0;font-size:1rem;">版本状态</h3>
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <tr><td style="padding:6px 0;color:var(--color-on-surface-variant);">当前版本</td><td style="padding:6px 0;"><strong>v<?php echo shop_e($updateInfo['current_version']); ?></strong></td></tr>
                <tr><td style="padding:6px 0;color:var(--color-on-surface-variant);">上次检查</td><td style="padding:6px 0;"><?php echo $updateInfo['last_checked'] !== '' ? shop_e($updateInfo['last_checked']) : '<em>尚未检查</em>'; ?></td></tr>
                <?php if ($updateInfo['latest_version'] !== ''): ?>
                <tr><td style="padding:6px 0;color:var(--color-on-surface-variant);">最新版本</td><td style="padding:6px 0;"><strong>v<?php echo shop_e($updateInfo['latest_version']); ?></strong><?php echo $updateInfo['has_update'] ? ' <span style="color:var(--color-primary);font-weight:600;">可更新</span>' : ' <span style="color:var(--color-success,#22c55e);">已是最新</span>'; ?></td></tr>
                <?php endif; ?>
            </table>
            <form method="post" style="margin-top:var(--space-md,.75rem);">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="check_update">
                <button class="btn btn-primary btn-sm" type="submit">检查更新</button>
            </form>
        </div>
    </div>

    <?php if ($updateInfo['has_update']): ?>
    <!-- ── 更新操作 ── -->
    <div class="admin-settings-grid" style="margin-bottom:var(--space-xl,1.5rem);">
        <div class="admin-settings-card" style="padding:var(--space-lg,1.25rem);border-left:3px solid var(--color-primary);">
            <h3 style="margin:0 0 var(--space-md,.75rem) 0;font-size:1rem;">
                <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.25rem;color:var(--color-primary);" aria-hidden="true">system_update_alt</span>
                新版本 v<?php echo shop_e($updateInfo['latest_version']); ?>
            </h3>
            <?php if ($updateInfo['published_at'] !== ''): ?>
            <p style="font-size:.8rem;color:var(--color-on-surface-variant);margin:0 0 var(--space-sm,.5rem) 0;">
                发布于 <?php echo shop_e(substr($updateInfo['published_at'], 0, 10)); ?>
                <?php if ($updateInfo['release_url'] !== ''): ?>
                &middot; <a href="<?php echo shop_e($updateInfo['release_url']); ?>" target="_blank" rel="noopener">在 GitHub 查看</a>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            <?php if ($updateInfo['release_notes'] !== ''): ?>
            <div style="background:var(--color-surface-container,#f8fafc);border-radius:var(--radius-sm,6px);padding:var(--space-md,.75rem);font-size:.8rem;line-height:1.6;max-height:300px;overflow-y:auto;margin-bottom:var(--space-md,.75rem);white-space:pre-line;">
<?php echo nl2br(shop_e($updateInfo['release_notes'])); ?>
            </div>
            <?php endif; ?>
            <p style="font-size:.8rem;color:var(--color-on-surface-variant);margin:0 0 var(--space-md,.75rem) 0;">
                更新前将自动创建备份。如遇问题可在下方回滚。
            </p>
            <form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='更新中，请勿关闭页面...';">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="apply_update">
                <button class="btn btn-primary btn-sm" type="submit" onclick="return confirm('确定要更新到 v<?php echo shop_e($updateInfo['latest_version']); ?>？更新前会自动备份。');">一键更新</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── 备份管理 ── -->
    <div class="admin-settings-grid" style="margin-bottom:var(--space-xl,1.5rem);">
        <div class="admin-settings-card" style="padding:var(--space-lg,1.25rem);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-md,.75rem);">
                <h3 style="margin:0;font-size:1rem;">备份管理</h3>
                <form method="post" style="margin:0;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                    <input type="hidden" name="admin_action" value="create_backup">
                    <button class="btn btn-sm" type="submit">手动创建备份</button>
                </form>
            </div>
            <?php if (empty($backupList)): ?>
            <p style="color:var(--color-on-surface-variant);font-size:.875rem;">暂无备份。</p>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--color-outline-variant,#e2e8f0);">
                            <th style="text-align:left;padding:8px 6px;">文件名</th>
                            <th style="text-align:left;padding:8px 6px;">版本</th>
                            <th style="text-align:left;padding:8px 6px;">日期</th>
                            <th style="text-align:right;padding:8px 6px;">大小</th>
                            <th style="text-align:right;padding:8px 6px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($backupList as $backup): ?>
                        <tr style="border-bottom:1px solid var(--color-outline-variant,#e2e8f0);">
                            <td style="padding:8px 6px;word-break:break-all;"><?php echo shop_e($backup['filename']); ?></td>
                            <td style="padding:8px 6px;">v<?php echo shop_e($backup['version']); ?></td>
                            <td style="padding:8px 6px;"><?php echo shop_e($backup['timestamp']); ?></td>
                            <td style="padding:8px 6px;text-align:right;"><?php echo sprintf('%.1f MB', $backup['size'] / 1024 / 1024); ?></td>
                            <td style="padding:8px 6px;text-align:right;white-space:nowrap;">
                                <form method="post" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                                    <input type="hidden" name="admin_action" value="rollback_update">
                                    <input type="hidden" name="backup_file" value="<?php echo shop_e($backup['filename']); ?>">
                                    <button class="btn btn-sm" type="submit" onclick="return confirm('确定要回滚到此备份？当前文件将被覆盖。');" style="font-size:.75rem;">回滚</button>
                                </form>
                                <form method="post" style="display:inline;margin-left:4px;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                                    <input type="hidden" name="admin_action" value="delete_backup">
                                    <input type="hidden" name="backup_file" value="<?php echo shop_e($backup['filename']); ?>">
                                    <button class="btn btn-sm" type="submit" onclick="return confirm('确定删除此备份？');" style="font-size:.75rem;color:var(--color-error,#ef4444);">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($updateHistory)): ?>
    <!-- ── 更新历史 ── -->
    <div class="admin-settings-grid">
        <div class="admin-settings-card" style="padding:var(--space-lg,1.25rem);">
            <h3 style="margin:0 0 var(--space-md,.75rem) 0;font-size:1rem;">更新历史</h3>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--color-outline-variant,#e2e8f0);">
                            <th style="text-align:left;padding:8px 6px;">操作</th>
                            <th style="text-align:left;padding:8px 6px;">版本变更</th>
                            <th style="text-align:left;padding:8px 6px;">时间</th>
                            <th style="text-align:left;padding:8px 6px;">状态</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($updateHistory, 0, 20) as $entry): ?>
                        <tr style="border-bottom:1px solid var(--color-outline-variant,#e2e8f0);">
                            <td style="padding:8px 6px;"><?php echo shop_e($entry['action'] === 'rollback' ? '回滚' : '更新'); ?></td>
                            <td style="padding:8px 6px;">v<?php echo shop_e($entry['from_version'] ?? '?'); ?> → v<?php echo shop_e($entry['to_version'] ?? '?'); ?></td>
                            <td style="padding:8px 6px;"><?php echo shop_e(substr($entry['timestamp'] ?? '', 0, 19)); ?></td>
                            <td style="padding:8px 6px;">
                                <?php if (($entry['status'] ?? '') === 'success'): ?>
                                    <span style="color:var(--color-success,#22c55e);">成功</span>
                                <?php else: ?>
                                    <span style="color:var(--color-error,#ef4444);">失败</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</section>
