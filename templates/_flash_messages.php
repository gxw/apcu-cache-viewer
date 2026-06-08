<?php if (isset($_SESSION['flash'])): ?>
    <?php foreach ((array)$_SESSION['flash'] as $message): ?>
        <div class="alert alert-<?= htmlspecialchars($message['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Container for Turbo Stream flash messages -->
<div id="flash-messages"></div>
