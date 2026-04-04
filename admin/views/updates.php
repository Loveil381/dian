<?php declare(strict_types=1); ?>
<div class="updates-container">
    <?php if ($incompleteUpdate !== null): ?>
    <div class="updates-warning">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">warning</span>
        <p>检测到未完成的更新操作（步骤: <?php echo shop_e($incompleteUpdate['step'] ?? '未知'); ?>）。建议从下方备份列表进行回滚。</p>
    </div>
    <?php endif; ?>

    <!-- 1. Page Title & Version Stats -->
    <div class="updates-header">
        <div class="updates-header-top">
            <div class="updates-title-group">
                <div class="updates-title-row">
                    <h1 class="updates-title">更新中心</h1>
                    <span class="updates-version-badge">v<?php echo shop_e($updateInfo['current_version']); ?></span>
                </div>
                <p class="updates-subtitle">管理您的系统版本、安全补丁与功能扩展</p>
            </div>
            <form method="post" style="margin: 0;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="check_update">
                <button type="submit" class="updates-check-btn">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem;">refresh</span>
                    检查更新
                </button>
            </form>
        </div>
        
        <div class="updates-stats-grid">
            <div class="updates-stat-card">
                <div class="updates-stat-icon primary">
                    <span class="material-symbols-outlined">verified</span>
                </div>
                <div class="updates-stat-info">
                    <p class="updates-stat-label">当前版本</p>
                    <p class="updates-stat-value">v<?php echo shop_e($updateInfo['current_version']); ?></p>
                </div>
            </div>
            <div class="updates-stat-card">
                <div class="updates-stat-icon neutral">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
                <div class="updates-stat-info">
                    <p class="updates-stat-label">上次检查</p>
                    <p class="updates-stat-value"><?php echo $updateInfo['last_checked'] !== '' ? shop_e(substr($updateInfo['last_checked'], 0, 10)) : '尚未检查'; ?></p>
                </div>
            </div>
            <?php if ($updateInfo['latest_version'] !== ''): ?>
            <div class="updates-stat-card">
                <?php if ($updateInfo['has_update']): ?>
                    <div class="updates-stat-indicator"></div>
                <?php endif; ?>
                <div class="updates-stat-icon success">
                    <span class="material-symbols-outlined">upgrade</span>
                </div>
                <div class="updates-stat-info">
                    <p class="updates-stat-label">最新版本</p>
                    <p class="updates-stat-value">v<?php echo shop_e($updateInfo['latest_version']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($updateInfo['has_update']): ?>
    <!-- 2. New Version Available -->
    <section class="updates-section updates-new-version">
        <div class="updates-new-header">
            <div class="updates-new-title-area">
                <div class="updates-new-icon">
                    <span class="material-symbols-outlined">system_update_alt</span>
                </div>
                <div>
                    <h3 class="updates-new-title">新版本就绪: v<?php echo shop_e($updateInfo['latest_version']); ?></h3>
                    <?php if ($updateInfo['published_at'] !== '' || $updateInfo['release_url'] !== ''): ?>
                    <div class="updates-new-meta">
                        <?php if ($updateInfo['published_at'] !== ''): ?>
                        <span class="updates-new-date">发布日期: <?php echo shop_e(substr($updateInfo['published_at'], 0, 10)); ?></span>
                        <?php endif; ?>
                        <?php if ($updateInfo['release_url'] !== ''): ?>
                        <a href="<?php echo shop_e($updateInfo['release_url']); ?>" class="updates-new-link" target="_blank" rel="noopener">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">link</span>
                            查看 GitHub 发布说明
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($updateInfo['release_notes'] !== ''): ?>
        <div class="updates-changelog"><?php echo nl2br(shop_e($updateInfo['release_notes'])); ?></div>
        <?php endif; ?>
        
        <div class="updates-new-footer">
            <div class="updates-security-notice">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; color: var(--color-primary); font-size: 1.25rem;">shield_with_heart</span>
                安全提示：更新前将自动创建备份。如遇问题可在下方回滚。
            </div>
            <form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='更新中，请勿关闭页面...';">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="apply_update">
                <button type="submit" class="updates-update-btn" onclick="return confirm('确定要更新到 v<?php echo shop_e($updateInfo['latest_version']); ?>？更新前会自动备份。');">
                    立即一键升级
                </button>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <!-- 3. Backup Management -->
    <section class="updates-section">
        <div class="updates-section-header">
            <h3 class="updates-section-title">
                <span class="material-symbols-outlined" style="color: var(--color-secondary);">cloud_download</span>
                备份管理
            </h3>
            <form method="post" style="margin: 0;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="create_backup">
                <button type="submit" class="updates-backup-btn">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem;">add_circle</span>
                    创建手动备份
                </button>
            </form>
        </div>
        
        <?php if (empty($backupList)): ?>
        <div class="updates-empty-state">
            暂无备份。
        </div>
        <?php else: ?>
        <div class="updates-table-wrapper">
            <table class="updates-table">
                <thead>
                    <tr>
                        <th>文件名称</th>
                        <th>版本</th>
                        <th>备份日期</th>
                        <th>大小</th>
                        <th style="text-align: right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($backupList as $backup): ?>
                    <tr>
                        <td class="updates-filename"><?php echo shop_e($backup['filename']); ?></td>
                        <td><span class="updates-table-version">v<?php echo shop_e($backup['version']); ?></span></td>
                        <td class="updates-table-text"><?php echo shop_e($backup['timestamp']); ?></td>
                        <td class="updates-table-text"><?php echo sprintf('%.1f MB', $backup['size'] / 1024 / 1024); ?></td>
                        <td class="updates-table-actions">
                            <form method="post" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                                <input type="hidden" name="admin_action" value="rollback_update">
                                <input type="hidden" name="backup_file" value="<?php echo shop_e($backup['filename']); ?>">
                                <button type="submit" class="updates-action-btn primary" onclick="return confirm('确定要回滚到此备份？当前文件将被覆盖。');">回滚</button>
                            </form>
                            <form method="post" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                                <input type="hidden" name="admin_action" value="delete_backup">
                                <input type="hidden" name="backup_file" value="<?php echo shop_e($backup['filename']); ?>">
                                <button type="submit" class="updates-action-btn error" onclick="return confirm('确定删除此备份？');">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- 4. Update History -->
    <?php if (!empty($updateHistory)): ?>
    <section class="updates-section">
        <div class="updates-section-header" style="border: none; padding-bottom: 0;">
            <h3 class="updates-section-title">
                <span class="material-symbols-outlined" style="color: var(--color-primary-fixed-dim);">history</span>
                更新历史
            </h3>
        </div>
        <div class="updates-history-section">
            <div class="updates-timeline">
            <?php foreach (array_slice($updateHistory, 0, 20) as $entry): ?>
                <?php $statusColorClass = (($entry['status'] ?? '') === 'success') ? 'success' : 'error'; ?>
                <div class="updates-timeline-item">
                    <div class="updates-timeline-icon <?php echo $statusColorClass; ?>">
                        <span class="material-symbols-outlined"><?php echo (($entry['status'] ?? '') === 'success') ? 'check' : 'close'; ?></span>
                    </div>
                    <div class="updates-timeline-content">
                        <div>
                            <h4 class="updates-timeline-title">
                                <?php echo shop_e($entry['action'] === 'rollback' ? '版本回滚' : '系统升级'); ?>: 
                                v<?php echo shop_e($entry['from_version'] ?? '?'); ?> → v<?php echo shop_e($entry['to_version'] ?? '?'); ?>
                            </h4>
                            <?php if (($entry['status'] ?? '') !== 'success'): ?>
                                <p class="updates-timeline-desc error-text">状态: 失败</p>
                            <?php else: ?>
                                <p class="updates-timeline-desc">状态: 成功</p>
                            <?php endif; ?>
                        </div>
                        <span class="updates-timeline-date"><?php echo shop_e(substr($entry['timestamp'] ?? '', 0, 10)); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>
