<?php
declare(strict_types=1);
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Titanium Lunch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>
<body class="app-body admin-login-page">
    <main class="login-container">
        <div class="login-card">
            <div class="brand brand--center">
                <span class="brand-company">Titanium Telecom</span>
                <span class="brand-system">Área Administrativa</span>
            </div>
            <?php if (!empty($error)): ?>
                <p class="form-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="login-form">
                <?= csrfField() ?>
                <div class="form-field">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                <div class="form-field">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </form>
            <p class="login-back"><a href="<?= e(base_url('index.php')) ?>">← Voltar ao sistema</a></p>
        </div>
    </main>
</body>
</html>
