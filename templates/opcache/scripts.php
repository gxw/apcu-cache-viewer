<?php $view->extend('layout'); ?>

<?php $this->section('content'); ?>

<div class="container-fluid">
    <h1>OPcache Cached Scripts</h1>

    <div class="card mt-4">
        <div class="card-header">Cached Scripts</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Script</th>
                        <th>Hits</th>
                        <th>Memory</th>
                        <th>Last Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scripts as $script): ?>
                        <tr>
                            <td><?= htmlspecialchars($script['full_path']) ?></td>
                            <td><?= $script['hits'] ?></td>
                            <td><?= $service->getHumanReadableMemorySize($script['memory_consumption']) ?></td>
                            <td><?= date('Y-m-d H:i:s', $script['last_used_timestamp']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
