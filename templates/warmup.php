<?php $view->extend('layout'); ?>

<?php $this->section('content'); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Cache Warmup</h1>
        <div>
            <a href="<?= $view->url('/') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header fw-bold">
                    <i class="fas fa-fire me-1"></i> Load Entries into APCu
                </div>
                <div class="card-body">
                    <form id="warmup-form">
                        <?= $view->csrfField() ?>
                        
                        <div class="mb-3">
                            <label for="warmup-json" class="form-label">Key-Value Pairs (JSON)</label>
                            <textarea id="warmup-json" class="form-control font-monospace" rows="12" placeholder='{
    "my_cache_key": "my_value",
    "config_option": {"nested": true, "count": 42},
    "session_data": {"user_id": 1, "role": "admin"}
}'></textarea>
                            <div class="form-text">
                                Paste a JSON object where each key is a cache key and each value is its cached value.
                                Values can be strings, numbers, booleans, arrays, or objects.
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="warmup-ttl" class="form-label">TTL (seconds)</label>
                                <input type="number" id="warmup-ttl" class="form-control" value="300" min="0">
                                <div class="form-text">0 = never expires</div>
                            </div>
                            <div class="col-md-4">
                                <label for="warmup-format" class="form-label">Value Format</label>
                                <select id="warmup-format" class="form-select">
                                    <option value="auto">Auto-detect</option>
                                    <option value="json_encoded">JSON-encoded strings</option>
                                </select>
                                <div class="form-text">Choose how string values are interpreted</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="warmup-btn">
                            <i class="fas fa-upload me-1"></i> Store to Cache
                        </button>
                    </form>

                    <div id="warmup-result" class="mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header fw-bold">
                    <i class="fas fa-info-circle me-1"></i> Tips
                </div>
                <div class="card-body">
                    <ul class="mb-0 small">
                        <li class="mb-2">Use this to pre-populate the cache after a flush or deployment.</li>
                        <li class="mb-2">Export existing entries as CSV first, then convert to JSON format.</li>
                        <li class="mb-2">Set a reasonable TTL — too long may serve stale data, too short defeats caching.</li>
                        <li class="mb-2">JSON-encoded strings will be decoded into their original PHP types if you select the appropriate format.</li>
                        <li class="mb-2">You can also use the API directly: <code>POST /warmup</code> with <code>{"entries": {...}, "ttl": 300}</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('warmup-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('warmup-btn');
    const result = document.getElementById('warmup-result');

    let entries;
    try {
        entries = JSON.parse(document.getElementById('warmup-json').value);
        if (typeof entries !== 'object' || Array.isArray(entries) || entries === null) {
            throw new Error('Root must be a JSON object');
        }
    } catch (err) {
        result.style.display = 'block';
        result.innerHTML = '<div class="alert alert-danger">Invalid JSON: ' + err.message + '</div>';
        return;
    }

    const ttl = parseInt(document.getElementById('warmup-ttl').value) || 300;
    const format = document.getElementById('warmup-format').value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Storing...';
    result.style.display = 'none';

    fetch('<?= $view->url('/warmup') ?>', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({ entries: entries, ttl: ttl, format: format })
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        if (data.success) {
            result.innerHTML = '<div class="alert alert-success mb-0"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
        } else {
            result.innerHTML = '<div class="alert alert-warning mb-0">' + (data.message || 'Failed') + '</div>';
            if (data.errors && data.errors.length) {
                result.innerHTML += '<ul class="mt-2 mb-0 small text-danger"><li>' + data.errors.join('</li><li>') + '</li></ul>';
            }
        }
    })
    .catch(err => {
        result.style.display = 'block';
        result.innerHTML = '<div class="alert alert-danger mb-0">Request failed: ' + err.message + '</div>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload me-1"></i> Store to Cache';
    });
});
</script>

<?php $this->endSection(); ?>
