<?php $view->extend('layout'); ?>

<?php $this->section('content'); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Server Information</h1>
        <div>
            <a href="<?= $view->url('/') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- PHP Info -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">PHP &amp; Server</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <?php foreach ($info as $label => $value): ?>
                            <tr>
                                <th style="width: 200px;"><?= ucwords(str_replace('_', ' ', $label)) ?></th>
                                <td><code><?= htmlspecialchars((string) $value) ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Extensions -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">PHP Extensions</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <?php foreach ($extensions as $name => $version): ?>
                            <tr>
                                <th style="width: 200px;"><?= htmlspecialchars($name) ?></th>
                                <td>
                                    <?php if ($version !== 'Not loaded'): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($version) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not loaded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- APCu ini -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">APCu Configuration</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <?php foreach ($apcu_ini as $key => $value): ?>
                            <tr>
                                <th style="width: 200px;"><?= htmlspecialchars($key) ?></th>
                                <td><code><?= htmlspecialchars((string) ($value === false ? 'Not set' : $value)) ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- OPcache ini -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-bold">OPcache Configuration</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <?php foreach ($opcache_ini as $key => $value): ?>
                            <tr>
                                <th style="width: 200px;"><?= htmlspecialchars($key) ?></th>
                                <td><code><?= htmlspecialchars((string) ($value === false ? 'Not set' : $value)) ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
