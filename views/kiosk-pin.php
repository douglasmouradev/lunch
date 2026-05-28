<?php
declare(strict_types=1);
/** @var string|null $pinError */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso — Titanium Lunch</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>
<body class="kiosk-pin-page">
    <main class="kiosk-pin-card">
        <div class="kiosk-brand">
            <span class="kiosk-brand-company">Titanium Telecom</span>
            <span class="kiosk-brand-title">Marcação de almoço</span>
        </div>
        <p class="kiosk-pin-lead">Informe o PIN para acessar o quiosque.</p>
        <?php if (!empty($pinError)): ?>
            <p class="form-error" role="alert"><?= e($pinError) ?></p>
        <?php endif; ?>
        <form method="post" action="<?= e(base_url('kiosk.php')) ?>" class="kiosk-pin-form">
            <label for="kiosk_pin" class="sr-only">PIN</label>
            <input type="password"
                   id="kiosk_pin"
                   name="kiosk_pin"
                   class="kiosk-pin-input"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   maxlength="8"
                   autocomplete="off"
                   autofocus
                   required>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
    </main>
</body>
</html>
