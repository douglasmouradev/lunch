<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeTab */
/** @var string $contentView */
/** @var array $extraData */
$extraData = $extraData ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Titanium Lunch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
</head>
<body class="app-body" data-page="<?= e($activeTab) ?>">
    <div id="toast-root" class="toast-root" aria-live="polite" aria-atomic="true"></div>

    <div id="confirm-modal" class="modal" hidden role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
        <div class="modal-backdrop" data-dismiss="modal"></div>
        <div class="modal-panel">
            <h2 id="confirm-modal-title" class="modal-title">Confirmar</h2>
            <p id="confirm-modal-message" class="modal-message"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" id="confirm-modal-cancel">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirm-modal-ok">Confirmar</button>
            </div>
        </div>
    </div>

    <header class="site-header">
        <div class="header-inner">
            <div class="brand">
                <span class="brand-company">Titanium Telecom</span>
                <span class="brand-system">Titanium Lunch</span>
            </div>
            <nav class="main-nav" aria-label="Principal">
                <a href="<?= e(base_url('index.php?page=home')) ?>" class="nav-link<?= $activeTab === 'home' ? ' is-active' : '' ?>">Marcação</a>
                <a href="<?= e(base_url('kiosk.php')) ?>" class="nav-link<?= $activeTab === 'kiosk' ? ' is-active' : '' ?>">Quiosque</a>
                <a href="<?= e(base_url('index.php?page=report')) ?>" class="nav-link<?= $activeTab === 'report' ? ' is-active' : '' ?>">Relatório</a>
                <a href="<?= e(base_url('admin/index.php')) ?>" class="nav-link nav-admin">Admin</a>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <?php require $contentView; ?>
    </main>

    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> Titanium Telecom — Controle de Almoço</p>
    </footer>

    <script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
</body>
</html>
