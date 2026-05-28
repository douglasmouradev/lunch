<?php
declare(strict_types=1);
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar senha — Titanium Lunch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>
<body class="app-body admin-login-page">
    <main class="login-container">
        <div class="login-card">
            <div class="brand brand--center">
                <span class="brand-company">Titanium Telecom</span>
                <span class="brand-system">Alterar senha</span>
            </div>
            <p class="login-hint">Por segurança, defina uma nova senha antes de continuar.</p>
            <?php if (!empty($error)): ?>
                <p class="form-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <form method="post" action="<?= e(base_url('admin/index.php?action=change-password')) ?>" class="login-form">
                <?= csrfField() ?>
                <div class="form-field">
                    <label for="current_password">Senha atual</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="form-field">
                    <label for="new_password">Nova senha (mín. 8 caracteres)</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                </div>
                <div class="form-field">
                    <label for="confirm_password">Confirmar nova senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Salvar nova senha</button>
            </form>
        </div>
    </main>
</body>
</html>
