<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'APCu Cache Viewer' ?></title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= $view->csrfToken() ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= $view->asset('img/favicon.ico') ?>" type="image/x-icon">
    
    <meta name="turbo-cache-control" content="no-cache">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $view->asset('/css/app.css') ?>">
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js"></script>
    <script>
        Turbo.start();
    </script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!--    --><?php //= $view->baseUrl() ?>
    <!-- Output any head content from child templates -->
    <?php if (isset($this->sections['head'])): ?>
        <?= $this->yield('head') ?>
    <?php endif; ?>
    
    <style>
        body {
            padding-top: 1rem;
            background-color: #f8f9fa;
        }
        .card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            font-weight: 600;
        }
        .progress {
            height: 0.5rem;
        }
        .sortable {
            cursor: pointer;
        }
        .sortable:hover {
            background-color: #f8f9fa;
        }
        .sort-asc::after {
            content: ' ↑';
        }
        .sort-desc::after {
            content: ' ↓';
        }
        .cache-value {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .pagination {
            margin-bottom: 0;
        }
        .search-form {
            max-width: 400px;
        }

        /* Dark mode overrides */
        [data-bs-theme="dark"] .table-light {
            background-color: #2c3034;
            color: #dee2e6;
        }
        [data-bs-theme="dark"] .table-light th {
            background-color: #2c3034;
            color: #dee2e6;
        }
        [data-bs-theme="dark"] .cache-value {
            background-color: #2c3034;
            color: #dee2e6;
        }
        [data-bs-theme="dark"] .sortable:hover {
            background-color: #2c3034;
        }
        [data-bs-theme="dark"] .bg-light {
            background-color: #2c3034 !important;
        }
        [data-bs-theme="dark"] .modal-content {
            background-color: #2c3034;
            color: #dee2e6;
        }
        [data-bs-theme="dark"] .btn-outline-secondary {
            color: #adb5bd;
            border-color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash'])): ?>
        <?php include __DIR__ . '/_flash_messages.php'; ?>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?= $view->url('/') ?>">APCu Cache Viewer</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $view->url('/') ?>">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $view->url('/opcache') ?>">OPcache</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $view->url('/audit') ?>">Audit</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $view->url('/warmup') ?>">Warmup</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $view->url('/info') ?>">Server</a>
                        </li>
                    </ul>
                    
                    <form class="d-flex" action="<?= $view->url('/') ?>">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button class="btn btn-outline-light" type="submit">Search</button>
                    </form>
                    <div class="d-flex align-items-center ms-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="darkModeToggle" onchange="toggleDarkMode(this.checked)">
                            <label class="form-check-label text-light small" for="darkModeToggle">Dark</label>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <main>
            <?php if (isset($this->sections['content'])): ?>
                <?= $this->yield('content') ?>
            <?php else: ?>
                <div class="alert alert-warning">No content to display</div>
            <?php endif; ?>
        </main>
        
        <!-- Footer -->
        <footer class="mt-5 py-3 text-muted text-center">
            <div class="container">
                <p class="mb-0">APCu Cache Viewer &copy; <?= date('Y') ?></p>
            </div>
        </footer>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    

    <script>
        // Escape HTML to prevent XSS in value display
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Base URL for API calls
        const BASE_URL = '<?= $view->url('/') ?>';

        // Show cache value in modal
        function showCacheValue(key, baseUrl) {
            // Encode the key for URL
            const encodedKey = encodeURIComponent(btoa(key));
            document.getElementById('modal-cache-key').value = key;
            document.getElementById('cacheValueModalLabel').textContent = `Cache Value - ${key}`;
            
            // Show loading state
            const valueContent = document.getElementById('modal-cache-value');
            valueContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Get the modal instance
            const cacheValueModalElement = document.getElementById('cacheValueModal');
            const modal = bootstrap.Modal.getInstance(cacheValueModalElement) || new bootstrap.Modal(cacheValueModalElement);
            modal.show();

            // Ensure backdrop and body class are removed on hide
            cacheValueModalElement.addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('modal-open');
                const backdrops = document.getElementsByClassName('modal-backdrop');
                while(backdrops[0]) {
                    backdrops[0].parentNode.removeChild(backdrops[0]);
                }
            }, { once: true });
            
            // Fetch the value via AJAX
            fetch(`${baseUrl}${encodedKey}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Add a badge for the value type
                        const badgeEl = document.getElementById('value-type-badge');
                        if (badgeEl) {
                            badgeEl.textContent = data.value_type || 'plain';
                            badgeEl.className = 'badge ms-2';
                            switch (data.value_type) {
                                case 'json':       badgeEl.classList.add('bg-success'); break;
                                case 'structured': badgeEl.classList.add('bg-info'); break;
                                case 'numeric':    badgeEl.classList.add('bg-primary'); break;
                                case 'bool':       badgeEl.classList.add('bg-warning', 'text-dark'); break;
                                case 'null':       badgeEl.classList.add('bg-secondary'); break;
                                case 'serialized': badgeEl.classList.add('bg-danger'); break;
                                default:           badgeEl.classList.add('bg-light', 'text-dark'); break;
                            }
                        }

                        // Format the value based on detected type
                        if (data.value_type === 'json') {
                            try {
                                const parsed = JSON.parse(data.value);
                                valueContent.innerHTML = `<pre><code class="language-json">${JSON.stringify(parsed, null, 2)}</code></pre>`;
                            } catch (e) {
                                valueContent.innerHTML = `<pre><code>${escapeHtml(data.value)}</code></pre>`;
                            }
                        } else if (data.value_type === 'serialized') {
                            valueContent.innerHTML = `<pre><code>${escapeHtml(data.value)}</code></pre>`;
                            valueContent.innerHTML += '<div class="alert alert-danger mt-2 mb-0 py-1 px-2 small"><i class="fas fa-exclamation-triangle"></i> Serialized PHP — handle with care</div>';
                        } else if (data.value_type === 'structured') {
                            valueContent.innerHTML = `<pre><code class="language-json">${JSON.stringify(data.value, null, 2)}</code></pre>`;
                        } else if (data.value_type === 'numeric') {
                            valueContent.innerHTML = `<pre class="cache-value mb-0"><span class="text-primary fw-bold">${escapeHtml(String(data.value))}</span></pre>`;
                        } else if (data.value_type === 'bool') {
                            valueContent.innerHTML = `<pre class="cache-value mb-0"><span class="text-${data.value ? 'success' : 'danger'} fw-bold">${data.value ? 'true' : 'false'}</span></pre>`;
                        } else if (data.value_type === 'null') {
                            valueContent.innerHTML = `<pre class="cache-value mb-0"><span class="text-muted fst-italic">null</span></pre>`;
                        } else {
                            // Plain text — escape for safe display
                            valueContent.innerHTML = `<pre><code>${escapeHtml(data.value)}</code></pre>`;
                        }
                        // Apply syntax highlighting if Prism is available
                        if (window.Prism) {
                            const codeEl = valueContent.querySelector('code');
                            if (codeEl) Prism.highlightElement(codeEl);
                        }

                        // Populate TTL field
                        const ttlInput = document.getElementById('modal-ttl');
                        if (ttlInput && data.ttl !== undefined) {
                            ttlInput.value = data.ttl;
                        }
                        const ttlFeedback = document.getElementById('ttl-update-feedback');
                        if (ttlFeedback) {
                            ttlFeedback.textContent = data.ttl > 0
                                ? 'Expires: ' + new Date(data.expires_at * 1000).toLocaleString()
                                : 'No expiration';
                        }
                    } else {
                        valueContent.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to load value'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    valueContent.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
                });
        }

        // Update TTL for the currently viewed cache key
        function updateKeyTtl() {
            const keyInput = document.getElementById('modal-cache-key');
            const ttlInput = document.getElementById('modal-ttl');
            const feedback = document.getElementById('ttl-update-feedback');
            const btn = document.getElementById('update-ttl-btn');
            if (!keyInput || !ttlInput || !feedback) return;

            const key = keyInput.value;
            const newTtl = parseInt(ttlInput.value);
            if (isNaN(newTtl) || newTtl < 0) {
                feedback.innerHTML = '<span class="text-danger">Invalid TTL</span>';
                return;
            }

            const encodedKey = btoa(key);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch(BASE_URL + 'key/' + encodeURIComponent(encodedKey), {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ key: encodedKey, ttl: newTtl })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    feedback.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> ' + data.message + '</span>';
                    showToast(data.message, 'success');
                } else {
                    feedback.innerHTML = '<span class="text-danger">' + (data.message || 'Update failed') + '</span>';
                    showToast(data.message || 'Update failed', 'danger');
                }
            })
            .catch(err => {
                feedback.innerHTML = '<span class="text-danger">Request failed</span>';
                showToast('Update request failed', 'danger');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Update TTL';
            });
        }

        // Handle clear cache form
        const clearCacheForm = document.getElementById('clear-cache-form');
        if (clearCacheForm) {
            clearCacheForm.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to clear the entire cache?')) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Clearing...';
                
                // Submit the form via AJAX
                e.preventDefault();
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({})
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showToast('Cache cleared successfully', 'success');
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Failed to clear cache');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(error.message || 'An error occurred while clearing the cache', 'danger');
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        }



        // Toggle all cache values
        function toggleAllDetails() {
            const toggles = document.querySelectorAll('.toggle-details');
            const firstState = toggles.length > 0 ? toggles[0].getAttribute('aria-expanded') === 'true' : false;
            
            toggles.forEach(toggle => {
                const target = document.querySelector(toggle.getAttribute('data-bs-target'));
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
                
                if (firstState) {
                    bsCollapse.hide();
                } else {
                    bsCollapse.show();
                }
            });
        }
        
        // Close all cache values
        function closeAllDetails() {
            document.querySelectorAll('.collapse').forEach(el => {
                const bsCollapse = bootstrap.Collapse.getInstance(el);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            });
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Enable debug logging
    

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Copy button for modal
            document.querySelectorAll('.copy-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        let textToCopy = '';
                        if (targetElement.tagName === 'PRE') {
                            textToCopy = targetElement.textContent;
                        } else {
                            textToCopy = targetElement.value;
                        }
                        navigator.clipboard.writeText(textToCopy).then(() => {
                            showToast('Copied to clipboard!', 'success');
                        }).catch(err => {
                            console.error('Failed to copy text: ', err);
                            showToast('Failed to copy text', 'danger');
                        });
                    }
                });
            });

            // Handle delete key forms (event delegation — survives Turbo frame swaps)
            document.addEventListener('submit', function(e) {
                const form = e.target.closest('.delete-key-form');
                if (!form) return;
                e.preventDefault();
                const keyToDelete = form.getAttribute('data-key');
                if (!keyToDelete) return;
                if (!confirm(`Are you sure you want to delete the key: ${atob(keyToDelete)}?`)) {
                    return;
                }

                const button = form.querySelector('button[type="submit"]');
                const originalIcon = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ key: keyToDelete })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        // Remove the row from the table
                        const row = form.closest('tr');
                        if (row) row.remove();
                    } else {
                        showToast(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(error.message || 'An error occurred while deleting the key', 'danger');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalIcon;
                });
            });
        });
    </script>

    <script>
        // Dark mode toggle
        function toggleDarkMode(enabled) {
            const html = document.documentElement;
            html.setAttribute('data-bs-theme', enabled ? 'dark' : 'light');
            localStorage.setItem('theme', enabled ? 'dark' : 'light');
        }

        // Restore saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                document.getElementById('darkModeToggle').checked = true;
            }
        });

        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;
            
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 5000 });
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }
        
        window.showToast = showToast;
    </script>
</body>
</html>
