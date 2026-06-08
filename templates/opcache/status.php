<?php $view->extend('layout'); ?>

<?php $this->section('content'); ?>

<div class="container-fluid">
    <h1>OPcache Status</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Memory Usage</div>
                <div class="card-body">
                    <canvas id="memoryChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Key Statistics</div>
                <div class="card-body">
                    <canvas id="keysChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            General Information
            <div>
                <form action="<?= $view->url('/opcache/reset') ?>" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to reset OPcache? All cached scripts will be cleared.')">
                    <?= $view->csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-danger me-1">
                        <i class="fas fa-sync-alt me-1"></i> Reset OPcache
                    </button>
                </form>
                <a href="<?= $view->url('/opcache/scripts') ?>" class="btn btn-sm btn-primary">View Cached Scripts</a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <tbody>
                    <tr>
                        <th>OPcache enabled</th>
                        <td><?= $status['opcache_enabled'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                    </tr>
                    <tr>
                        <th>Cache full</th>
                        <td><?= $status['cache_full'] ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-success">No</span>' ?></td>
                    </tr>
                    <tr>
                        <th>Restart pending</th>
                        <td><?= $status['restart_pending'] ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-info">No</span>' ?></td>
                    </tr>
                    <tr>
                        <th>Restart in progress</th>
                        <td><?= $status['restart_in_progress'] ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-info">No</span>' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">JIT Information</div>
        <div class="card-body">
            <table class="table table-striped">
                <tbody>
                    <tr>
                        <th>JIT enabled</th>
                        <td><?= $status['jit']['enabled'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                    </tr>
                    <tr>
                        <th>On</th>
                        <td><?= $status['jit']['on'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                    </tr>
                    <tr>
                        <th>Kind</th>
                        <td><?= $status['jit']['kind'] ?></td>
                    </tr>
                    <tr>
                        <th>Opt Level</th>
                        <td><?= $status['jit']['opt_level'] ?></td>
                    </tr>
                    <tr>
                        <th>Opt Flags</th>
                        <td><?= $status['jit']['opt_flags'] ?></td>
                    </tr>
                    <tr>
                        <th>Buffer Size</th>
                        <td><?= $service->getHumanReadableMemorySize($status['jit']['buffer_size']) ?></td>
                    </tr>
                    <tr>
                        <th>Buffer Free</th>
                        <td><?= $service->getHumanReadableMemorySize($status['jit']['buffer_free']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const memoryData = {
            labels: ['Used Memory', 'Free Memory', 'Wasted Memory'],
            datasets: [{
                data: [
                    <?= $status['memory_usage']['used_memory'] ?>,
                    <?= $status['memory_usage']['free_memory'] ?>,
                    <?= $status['memory_usage']['wasted_memory'] ?>
                ],
                backgroundColor: ['#0d6efd', '#198754', '#dc3545']
            }]
        };

        const keysData = {
            labels: ['Cached Keys', 'Pending Deletion', 'Max Keys'],
            datasets: [{
                data: [
                    <?= $status['opcache_statistics']['num_cached_keys'] ?>,
                    <?= $status['opcache_statistics']['num_pending_deletion'] ?>,
                    <?= $status['opcache_statistics']['max_cached_keys'] ?>
                ],
                backgroundColor: ['#0d6efd', '#ffc107', '#6c757d']
            }]
        };

        new Chart(document.getElementById('memoryChart'), {
            type: 'doughnut',
            data: memoryData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        new Chart(document.getElementById('keysChart'), {
            type: 'doughnut',
            data: keysData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
</script>
<?php $this->endSection(); ?>
