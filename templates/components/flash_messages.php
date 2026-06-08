<?php if (isset($_SESSION['flash_messages'])): ?>
    <div class="flash-messages">
        <?php foreach ($_SESSION['flash_messages'] as $message): ?>
            <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                <?= $this->e($message['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['flash_messages']); ?>
<?php endif; ?>
