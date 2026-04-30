        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-3">Logowanie</h1>
                        <p class="text-secondary mb-4">Wpisz PIN, aby przejść do panelu.</p>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
                        <?php endforeach; ?>
                        <form method="post" novalidate>
                            <input type="hidden" name="action" value="login">
                            <label for="pin" class="form-label">PIN</label>
                            <input type="password" class="form-control form-control-lg mb-3" id="pin" name="pin" inputmode="numeric" autocomplete="current-password" required autofocus>
                            <button type="submit" class="btn btn-primary btn-lg w-100">Zaloguj</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
