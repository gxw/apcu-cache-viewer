<?php 
// Ensure the view is set
if (!isset($view)) {
    throw new \RuntimeException('View instance not available in the template');
}

// Set the layout
echo $view->extend('layout'); 
?>

<?php $this->section('content'); ?>
<turbo-frame id="main-content" data-turbo-action="advance">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">APCu Cache Viewer</h1>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center">
                    <label for="auto-refresh" class="form-label small text-muted mb-0 me-1">
                        <i class="fas fa-sync-alt"></i>
                    </label>
                    <select id="auto-refresh" class="form-select form-select-sm" style="width: auto;" onchange="setAutoRefresh(this.value)">
                        <option value="0">Off</option>
                        <option value="15">15s</option>
                        <option value="30">30s</option>
                        <option value="60">60s</option>
                    </select>
                </div>
                <form id="clear-cache-form" action="<?= $view->url('/clear-cache') ?>" method="POST" class="d-inline">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to clear the entire cache?')">
                        <i class="fas fa-trash-alt me-1"></i> Clear Cache
                    </button>
                </form>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportEntries()">
                    <i class="fas fa-download me-1"></i> Export
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <!-- Memory Usage -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-primary border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Memory Usage</h6>
                                <h4 class="mb-0"><?= $this->formatBytes($stats['memory']['used']) ?></h4>
                                <small class="text-muted">of <?= $this->formatBytes($stats['memory']['total']) ?></small>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-memory fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: <?= round($stats['memory']['usage_percent'], 1) ?>%" 
                                 role="progressbar" aria-valuenow="<?= round($stats['memory']['usage_percent'], 1) ?>" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted"><?= round($stats['memory']['usage_percent'], 1) ?>% used</small>
                            <small class="text-muted"><?= $this->formatBytes($stats['memory']['free']) ?> free</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cache Entries -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-success border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Cache Entries</h6>
                                <h4 class="mb-0"><?= number_format($stats['cache']['entries']) ?></h4>
                                <small class="text-muted"><?= $this->formatBytes($stats['cache']['memory_size']) ?> total</small>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-database fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Hits</small>
                                <small class="fw-bold"><?= number_format($stats['cache']['hits']) ?></small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Misses</small>
                                <small class="fw-bold"><?= number_format($stats['cache']['misses']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Memory Usage Trend -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-info border-4 h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-3">Memory Trend</h6>
                        <div class="chart-container" style="position: relative; height:200px">
                            <canvas id="memoryTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hit Rate Trend -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-primary border-4 h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-3">Hit Rate Trend</h6>
                        <div class="chart-container" style="position: relative; height:200px">
                            <canvas id="hitRateTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cache Uptime -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-warning border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Uptime</h6>
                                <h4 class="mb-0"><?= $this->formatUptime($stats['cache']['uptime'] ?? 0) ?></h4>
                                <small class="text-muted">Since <?= date('Y-m-d H:i', $stats['cache']['start_time'] ?? time()) ?></small>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">PHP Version</small>
                                <small class="fw-bold"><?= phpversion() ?></small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">APCu Version</small>
                                <small class="fw-bold"><?= phpversion('apcu') ?: 'Not Active' ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fragmentation -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-danger border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Fragmentation</h6>
                                <h4 class="mb-0"><?= round($stats['fragmentation']['fragment_percent'], 2) ?>%</h4>
                                <small class="text-muted"><?= $this->formatBytes($stats['fragmentation']['fragment_size']) ?> fragmented</small>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="fas fa-puzzle-piece fa-2x text-danger"></i>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-danger" style="width: <?= round($stats['fragmentation']['fragment_percent'], 2) ?>%" 
                                 role="progressbar" aria-valuenow="<?= round($stats['fragmentation']['fragment_percent'], 2) ?>" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted"><?= number_format($stats['fragmentation']['fragments']) ?> fragments</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Info -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-secondary border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">General</h6>
                                <h4 class="mb-0"><?= $stats['general']['app_version'] ?></h4>
                                <small class="text-muted">App Version</small>
                            </div>
                            <div class="bg-secondary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-info-circle fa-2x text-secondary"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Host</small>
                                <small class="fw-bold"><?= $stats['general']['host'] ?></small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Server</small>
                                <small class="fw-bold"><?= $stats['general']['server_software'] ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slots -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-dark border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Slots</h6>
                                <h4 class="mb-0"><?= number_format($stats['slots']['num_slots']) ?></h4>
                                <small class="text-muted">Total Slots</small>
                            </div>
                            <div class="bg-dark bg-opacity-10 p-3 rounded">
                                <i class="fas fa-th-large fa-2x text-dark"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Slot Size</small>
                                <small class="fw-bold"><?= $this->formatBytes($stats['slots']['slot_size']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Breakdown -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-info border-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Key Breakdown</h6>
                                <h4 class="mb-0"><?= number_format($stats['key_breakdown']['total']) ?></h4>
                                <small class="text-muted">Total Keys</small>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-tag fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Active</small>
                                <small class="fw-bold text-success"><?= number_format($stats['key_breakdown']['active']) ?></small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Expired</small>
                                <small class="fw-bold text-danger"><?= number_format($stats['key_breakdown']['expired']) ?></small>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">No TTL</small>
                                <small class="fw-bold"><?= number_format($stats['key_breakdown']['no_ttl']) ?></small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">&le;1m TTL</small>
                                <small class="fw-bold"><?= number_format($stats['key_breakdown']['short_ttl']) ?></small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">&le;1h TTL</small>
                                <small class="fw-bold"><?= number_format($stats['key_breakdown']['medium_ttl']) ?></small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">&gt;1h TTL</small>
                                <small class="fw-bold"><?= number_format($stats['key_breakdown']['long_ttl']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- "See Entries" Button -->
        <div class="text-center mb-4">
            <a href="<?= $view->url('/entries') ?>" class="btn btn-primary btn-lg" data-turbo-frame="entries-list">
                <i class="fas fa-list me-2"></i>See Entries
            </a>
        </div>

        <!-- Container for loaded entries -->
        <div id="entries-container">
            <turbo-frame id="entries-list">
                <?php if (isset($entries)): ?>
                    <?= $this->include('_entries') ?>
                <?php endif; ?>
            </turbo-frame>
        </div>

        <!-- Debug info - only show in development -->
        <?php if (getenv('APP_ENV') === 'development'): ?>
        <?php /*
        <div class="alert alert-info">
            <h5>Debug Info</h5>
            <pre><?= htmlspecialchars(print_r([
                'stats' => $stats ?? null
            ], true)) ?></pre>
        </div>
        */ ?>
        <?php endif; ?>

        
    </div>

    <!-- Cache Value Modal -->
    <div class="modal fade" id="cacheValueModal" tabindex="-1" aria-labelledby="cacheValueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cacheValueModalLabel">
                        Cache Value
                        <span id="value-type-badge" class="badge bg-light text-dark ms-2">plain</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Key</label>
                        <div class="input-group">
                            <input type="text" id="modal-cache-key" class="form-control" readonly>
                            <button class="btn btn-outline-secondary copy-btn" type="button" data-target="modal-cache-key">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <pre id="modal-cache-value" class="bg-light p-3 rounded" style="max-height: 50vh; overflow: auto;"></pre>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label for="modal-ttl" class="form-label small">TTL (seconds, 0 = never)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="modal-ttl" class="form-control" min="0" value="0" style="width: 120px;">
                                <button type="button" class="btn btn-outline-primary" id="update-ttl-btn" onclick="updateKeyTtl()">
                                    <i class="fas fa-save"></i> Update TTL
                                </button>
                            </div>
                        </div>
                        <div class="col">
                            <div id="ttl-update-feedback" class="small text-muted"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary copy-btn" data-target="modal-cache-value">
                        <i class="fas fa-copy me-1"></i> Copy to Clipboard
                    </button>
                </div>
            </div>
        </div>
    </div>

</turbo-frame>

<script>
// Trend Charts
<?php if (!empty($stats['trends']) && count($stats['trends']) > 1): ?>
document.addEventListener('DOMContentLoaded', function() {
    var trendData = <?= json_encode($stats['trends']) ?>;
    var labels = trendData.map(function(s) { return new Date(s.time * 1000).toLocaleTimeString(); });

    // Hit Rate Trend
    var hitCtx = document.getElementById('hitRateTrendChart');
    if (hitCtx) {
        new Chart(hitCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Hit Rate %',
                    data: trendData.map(function(s) { return s.hit_rate; }),
                    borderColor: 'rgba(13, 110, 253, 0.8)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: function(v) { return v + '%'; } } },
                    x: { display: false }
                }
            }
        });
    }

    // Memory Trend
    var memCtx = document.getElementById('memoryTrendChart');
    if (memCtx && trendData[0].memory_total > 0) {
        new Chart(memCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Used',
                    data: trendData.map(function(s) { return s.memory_used; }),
                    borderColor: 'rgba(13, 202, 240, 0.8)',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                    yAxisID: 'y',
                }, {
                    label: 'Total',
                    data: trendData.map(function(s) { return s.memory_total; }),
                    borderColor: 'rgba(220, 53, 69, 0.5)',
                    borderDash: [4, 4],
                    fill: false,
                    pointRadius: 0,
                    yAxisID: 'y',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v) { return (v / 1048576).toFixed(1) + ' MB'; } } },
                    x: { display: false }
                }
            }
        });
    }
});
<?php endif; ?>

// Auto-refresh dashboard
let refreshInterval = null;

function setAutoRefresh(seconds) {
    localStorage.setItem('autoRefresh', seconds);
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    if (parseInt(seconds) > 0) {
        refreshInterval = setInterval(function() {
            // Use Turbo visit for SPA-style refresh if Turbo is loaded
            if (window.Turbo) {
                Turbo.visit(window.location.href, { action: 'replace' });
            } else {
                window.location.reload();
            }
        }, parseInt(seconds) * 1000);
    }
}

// Restore saved auto-refresh preference on page load
document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('autoRefresh');
    if (saved !== null) {
        const select = document.getElementById('auto-refresh');
        if (select) {
            select.value = saved;
        }
        setAutoRefresh(saved);
    }
});
</script>

<?php $this->endSection(); ?>
