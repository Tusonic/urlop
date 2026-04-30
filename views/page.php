<?php require __DIR__ . '/layout/header.php'; ?>
<main class="container app-shell py-4 py-lg-5">
    <?php if (!$employee): ?>
        <?php require __DIR__ . '/auth/login.php'; ?>
    <?php else: ?>
        <?php require __DIR__ . '/layout/topbar.php'; ?>
        <?php if ($isAdmin): ?>
            <?php require __DIR__ . '/admin/panel.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/employee/panel.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/layout/scripts.php'; ?>
<?php require __DIR__ . '/layout/footer.php'; ?>