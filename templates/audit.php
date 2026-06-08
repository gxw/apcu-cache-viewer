<?php $view->extend('layout'); ?>

<?php $this->section('content'); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Audit Log</h1>
        <div>
            <a href="<?= $view->url('/') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="fw-bold">Recent Activity</span>
            <span class="text-muted ms-2 small">(last 100 actions)</span>
        </div>
        <div class="card-body p-0">
            <?php if (count($logs) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Key</th>
                            <th>Details</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap" title="<?= date('Y-m-d H:i:s', $log['time']) ?>">
                                <?= $this->formatDate($log['time'], 'H:i:s') ?>
                            </td>
                            <td>
                                <?php
                                    $badgeClass = match ($log['action']) {
                                        'clear_cache' => 'bg-danger',
                                        'delete_key', 'delete_multiple' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td class="text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($log['key']) ?>">
                                <?= htmlspecialchars($log['key'] ?: '-') ?>
                            </td>
                            <td><?= htmlspecialchars($log['details'] ?: '-') ?></td>
                            <td><code><?= htmlspecialchars($log['ip']) ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <div class="text-muted mb-2"><i class="fas fa-history fa-3x"></i></div>
                <p class="text-muted mb-0">No audit entries yet. Actions will appear here as you clear cache or delete keys.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
