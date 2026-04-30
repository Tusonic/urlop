        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h2 mb-1"><?= $isAdmin ? 'Panel administratora' : 'Panel pracownika' ?></h1>
                <div class="text-secondary">
                    <?= e($employee['first_name'] . ' ' . $employee['last_name']) ?>
                    <span class="badge text-bg-light ms-2"><?= $isAdmin ? 'Administrator' : 'Pracownik' ?></span>
                </div>
            </div>
            <a class="btn btn-outline-secondary" href="index.php?action=logout">Wyloguj</a>
        </div>

        <?php foreach ($flashMessages as $message): ?>
            <div class="alert alert-<?= e($message['type']) ?>" role="alert"><?= e($message['message']) ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endforeach; ?>
