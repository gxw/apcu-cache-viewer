<?php
// Ensure the view is set
if (!isset($view)) {
    throw new \RuntimeException('View instance not available in the template');
}
?>

<turbo-frame id="entries-list">
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="search-form" action="<?= $view->url('/entries') ?>" method="GET" data-turbo-frame="entries-list">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search cache keys..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search): ?>
                                <a href="<?= $view->url('/entries') ?>" class="btn btn-outline-secondary" data-turbo-frame="entries-list">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">Sort By</span>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="key" <?= $sortField === 'key' ? 'selected' : '' ?>>Key</option>
                                <option value="size" <?= $sortField === 'size' ? 'selected' : '' ?>>Size</option>
                                <option value="hits" <?= $sortField === 'hits' ? 'selected' : '' ?>>Hits</option>
                                <option value="modified" <?= $sortField === 'modified' ? 'selected' : '' ?>>Last Modified</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" onclick="this.form.elements['order'].value='<?= $sortOrder === 'asc' ? 'desc' : 'asc' ?>'; this.form.submit()">
                                <i class="fas fa-sort-<?= $sortOrder === 'asc' ? 'down' : 'up' ?>"></i>
                            </button>
                            <input type="hidden" name="order" value="<?= $sortOrder ?>">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Top Pagination -->
    <?php if ($pagination['total'] > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted">
            Showing page <?= $pagination['current'] ?> of <?= $pagination['total'] ?>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination mb-0">
                <?php
                    $currentPage = $pagination['current'];
                    $totalPages = $pagination['total'];
                    $range = 2; // Number of pages to show around the current page

                    // Always show first page
                    if ($totalPages > 1) {
                        if ($currentPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=' . ($currentPage - 1) . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">Previous</a></li>';
                        }

                        // First page
                        if ($currentPage - $range > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">1</a></li>';
                            if ($currentPage - $range > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Pages around current
                        for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                            echo '<li class="page-item ' . ($i === $currentPage ? 'active' : '') . '"><a class="page-link" href="' . $view->url('/entries') . '?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">' . $i . '</a></li>';
                        }

                        // Last page
                        if ($currentPage + $range < $totalPages) {
                            if ($currentPage + $range < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=' . $totalPages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">' . $totalPages . '</a></li>';
                        }

                        // Next page
                        if ($currentPage < $totalPages) {
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=' . ($currentPage + 1) . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">Next</a></li>';
                        }
                    }
                ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

    <!-- Cache Entries Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <div>
                <span class="fw-bold">Cache Entries</span>
                <span class="text-muted ms-2 small">(<span id="selected-count">0</span> selected)</span>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="bulk-delete-btn" onclick="bulkDelete()" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes(this.checked)">
                            </th>
                            <th class="sortable <?= $sortField === 'key' ? 'sort-' . $sortOrder : '' ?>" onclick="sortBy('key')">Key</th>
                            <th class="text-end sortable <?= $sortField === 'size' ? 'sort-' . $sortOrder : '' ?>" onclick="sortBy('size')">Size</th>
                            <th class="text-center sortable <?= $sortField === 'hits' ? 'sort-' . $sortOrder : '' ?>" onclick="sortBy('hits')">Hits</th>
                            <th class="text-end sortable <?= $sortField === 'modified' ? 'sort-' . $sortOrder : '' ?>" onclick="sortBy('modified')">Last Modified</th>
                            <th class="text-end sortable <?= $sortField === 'created' ? 'sort-' . $sortOrder : '' ?>" onclick="sortBy('created')">Created</th>
                            <th class="text-end sortable <?= $sortField === 'ttl' ? 'sort-' . $sortOrder : '' ?>" onclick="sortBy('ttl')">TTL</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($entries) > 0): ?>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td class="align-middle">
                                        <input type="checkbox" class="entry-checkbox" value="<?= base64_encode($entry->getKey()) ?>" onchange="updateBulkDeleteButton()">
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-secondary me-2"><?= strtoupper(pathinfo($entry->getKey(), PATHINFO_EXTENSION)) ?: 'TXT' ?></span>
                                            <span class="text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($entry->getKey()) ?>">
                                                <?= htmlspecialchars($entry->getKey()) ?>
                                            </span>
                                            <?php if (in_array($entry->getKey(), $pinnedKeys ?? [])): ?>
                                                <i class="fas fa-thumbtack ms-1 text-danger" title="Pinned"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end align-middle">
                                        <?= $this->formatBytes($entry->getSize()) ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?= number_format($entry->getHits()) ?>
                                    </td>
                                    <td class="text-end align-middle" title="<?= date('Y-m-d H:i:s', $entry->getModified()) ?>">
                                        <?= $this->formatDate($entry->getModified(), 'Y-m-d H:i') ?>
                                    </td>
                                    <td class="text-end align-middle" title="<?= date('Y-m-d H:i:s', $entry->getCreated()) ?>">
                                        <?= $this->formatDate($entry->getCreated(), 'Y-m-d H:i') ?>
                                    </td>
                                    <td class="text-end align-middle" title="<?= $entry->getTtl() > 0 ? date('Y-m-d H:i:s', $entry->getExpires()) : 'Never' ?>">
                                        <?php if ($entry->getTtl() > 0): ?>
                                            <?= $entry->isExpired() ? '<span class="badge bg-danger">Expired</span>' : $this->formatDuration($entry->getTtl()) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end align-middle">
                                        <div class="btn-group btn-group-sm">
                                            <a href="javascript:;" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#cacheValueModal" 
                                               onclick="event.preventDefault(); showCacheValue('<?= $this->e($entry->getKey()) ?>', '<?= $view->url('/get-cache/') ?>')">
                                                <button type="button" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </a>

                                            <button type="button" class="btn btn-sm <?= in_array($entry->getKey(), $pinnedKeys ?? []) ? 'btn-danger' : 'btn-outline-secondary' ?> pin-btn"
                                                    data-key="<?= base64_encode($this->e($entry->getKey())) ?>"
                                                    onclick="togglePin(this)">
                                                <i class="fas fa-thumbtack"></i>
                                            </button>

                                            <form action="<?= $view->url('/delete-key') ?>" method="POST" class="d-inline delete-key-form" data-key="<?= base64_encode($this->e($entry->getKey())) ?>" data-turbo="false">
                                                    <?= $this->csrfField() ?>
                                                    <input type="hidden" name="key" value="<?= base64_encode($this->e($entry->getKey())) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= in_array($entry->getKey(), $pinnedKeys ?? []) ? 'disabled title="Unpin before deleting"' : '' ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">No cache entries found</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total'] > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Showing page <?= $pagination['current'] ?> of <?= $pagination['total'] ?>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination mb-0">
                            <?php
                    $currentPage = $pagination['current'];
                    $totalPages = $pagination['total'];
                    $range = 2; // Number of pages to show around the current page

                    // Always show first page
                    if ($totalPages > 1) {
                        if ($currentPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=' . ($currentPage - 1) . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">Previous</a></li>';
                        }

                        // First page
                        if ($currentPage - $range > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">1</a></li>';
                            if ($currentPage - $range > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Pages around current
                        for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                            echo '<li class="page-item ' . ($i === $currentPage ? 'active' : '') . '"><a class="page-link" href="' . $view->url('/entries') . '?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">' . $i . '</a></li>';
                        }

                        // Last page
                        if ($currentPage + $range < $totalPages) {
                            if ($currentPage + $range < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=' . $totalPages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">' . $totalPages . '</a></li>';
                        }

                        // Next page
                        if ($currentPage < $totalPages) {
                            echo '<li class="page-item"><a class="page-link" href="' . $view->url('/entries') . '?page=' . ($currentPage + 1) . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($sort) ? '&sort=' . $sort : '') . '" data-turbo-action="advance">Next</a></li>';
                        }
                    }
                ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function togglePin(btn) {
        const key = btn.getAttribute('data-key');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch('<?= $view->url('/pin-key') ?>', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ key: key })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast(data.message, 'success');
                // Reload the entries list via Turbo
                const frame = document.querySelector('turbo-frame#entries-list');
                if (frame) {
                    frame.reload();
                } else {
                    window.location.reload();
                }
            } else {
                window.showToast(data.message || 'Failed', 'danger');
            }
        })
        .catch(err => {
            console.error('Pin error:', err);
            window.showToast('Request failed', 'danger');
        });
    }

    function sortBy(field) {
        const form = document.getElementById('search-form');
        if (!form) return;
        const sortInput = form.querySelector('select[name="sort"]');
        const orderInput = form.querySelector('input[name="order"]');
        if (!sortInput || !orderInput) return;
        // Toggle direction if clicking the same field
        if (sortInput.value === field) {
            orderInput.value = orderInput.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortInput.value = field;
            orderInput.value = 'asc';
        }
        form.submit();
    }

    function toggleAllCheckboxes(checked) {
        document.querySelectorAll('.entry-checkbox').forEach(cb => cb.checked = checked);
        updateBulkDeleteButton();
    }

    function updateBulkDeleteButton() {
        const selected = document.querySelectorAll('.entry-checkbox:checked').length;
        const btn = document.getElementById('bulk-delete-btn');
        document.getElementById('selected-count').textContent = selected;
        btn.disabled = selected === 0;
    }

    function bulkDelete() {
        const selected = document.querySelectorAll('.entry-checkbox:checked');
        if (selected.length === 0) return;
        if (!confirm(`Delete ${selected.length} selected cache entries?`)) return;

        const keys = Array.from(selected).map(cb => cb.value);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch('<?= $view->url('/delete-multiple') ?>', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ keys })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                window.showToast(data.message || 'Delete failed', 'danger');
            }
        })
        .catch(err => {
            console.error('Bulk delete error:', err);
            window.showToast('Delete request failed', 'danger');
        });
    }
    </script>
</turbo-frame>