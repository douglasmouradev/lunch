<?php
declare(strict_types=1);
/** @var string $date */
/** @var array $employees */
/** @var array $totals */
/** @var bool $locked */
$dateFormatted = (new DateTime($date))->format('d/m/Y');
$weekdays = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
$weekday = $weekdays[(int) (new DateTime($date))->format('w')];
$total = (int) ($totals['total_active'] ?? count($employees));
$marked = (int) $totals['total_yes'] + (int) $totals['total_no'];
$progress = $total > 0 ? (int) round(($marked / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f2744">
    <title>Marcação — Titanium Lunch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
    <link rel="manifest" href="<?= e(base_url('manifest.json')) ?>">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
    <meta name="kiosk-idle-minutes" content="<?= (int) ($idle_minutes ?? 15) ?>">
    <meta name="require-employee-pin" content="<?= !empty($require_employee_pin) ? '1' : '0' ?>">
</head>
<body class="kiosk-body" data-page="kiosk" data-require-employee-pin="<?= !empty($require_employee_pin) ? '1' : '0' ?>">
    <div id="toast-root" class="toast-root" aria-live="polite" aria-atomic="true"></div>

    <div id="pin-modal" class="modal modal--pin" hidden role="dialog" aria-modal="true" aria-labelledby="pin-modal-title">
        <div class="modal-backdrop" data-dismiss="pin-modal"></div>
        <div class="modal-panel modal-panel--pin">
            <h2 id="pin-modal-title" class="modal-title">Digite seu PIN</h2>
            <p id="pin-modal-subtitle" class="pin-modal-subtitle"></p>
            <div class="pin-display" id="pin-display" aria-live="polite">••••</div>
            <input type="password" id="pin-input" class="pin-input sr-only" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="off" aria-label="PIN de 4 dígitos">
            <div class="pin-pad" id="pin-pad">
                <?php foreach (['1','2','3','4','5','6','7','8','9','','0','⌫'] as $key): ?>
                    <?php if ($key === ''): ?>
                        <span class="pin-pad-spacer" aria-hidden="true"></span>
                    <?php else: ?>
                        <button type="button" class="pin-pad-key" data-key="<?= e($key) ?>"><?= e($key) ?></button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" id="pin-modal-cancel">Cancelar</button>
                <button type="button" class="btn btn-primary" id="pin-modal-ok" disabled>Confirmar</button>
            </div>
        </div>
    </div>

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

    <header class="kiosk-top">
        <div class="kiosk-top-row">
            <div class="kiosk-brand">
                <span class="kiosk-brand-company">Titanium Telecom</span>
                <span class="kiosk-brand-title">Controle de almoço</span>
            </div>
            <div class="kiosk-datetime">
                <span class="kiosk-weekday"><?= e($weekday) ?></span>
                <time class="kiosk-date" datetime="<?= e($date) ?>"><?= e($dateFormatted) ?></time>
            </div>
            <a href="<?= e(base_url('kiosk.php?action=lock')) ?>" class="btn btn-ghost btn-kiosk-lock" title="Bloquear quiosque">Bloquear</a>
        </div>

        <div class="kiosk-progress" role="progressbar"
             aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"
             aria-label="Progresso das marcações">
            <div class="kiosk-progress-meta">
                <span id="progress-label"><?= $marked ?> de <?= $total ?> registrados</span>
                <span id="progress-pct" class="kiosk-progress-pct"><?= $progress ?>%</span>
            </div>
            <div class="kiosk-progress-track">
                <div class="kiosk-progress-fill" id="progress-fill" style="width: <?= $progress ?>%"></div>
            </div>
        </div>

        <div class="kiosk-stats" id="stats-grid"
             data-yes="<?= (int) $totals['total_yes'] ?>"
             data-no="<?= (int) $totals['total_no'] ?>"
             data-pending="<?= (int) $totals['total_pending'] ?>"
             data-total="<?= $total ?>">
            <div class="kiosk-stat kiosk-stat--pending kiosk-stat--highlight">
                <span class="kiosk-stat-num" id="stat-pending"><?= (int) $totals['total_pending'] ?></span>
                <span class="kiosk-stat-label">Pendentes</span>
            </div>
            <div class="kiosk-stat kiosk-stat--yes">
                <span class="kiosk-stat-num" id="stat-yes"><?= (int) $totals['total_yes'] ?></span>
                <span class="kiosk-stat-label">Almoçaram</span>
            </div>
            <div class="kiosk-stat kiosk-stat--no">
                <span class="kiosk-stat-num" id="stat-no"><?= (int) $totals['total_no'] ?></span>
                <span class="kiosk-stat-label">Não almoçaram</span>
            </div>
        </div>
    </header>

    <?php if ($locked): ?>
        <p class="kiosk-locked">Marcações encerradas para hoje.</p>
    <?php endif; ?>

    <div class="kiosk-toolbar">
        <div class="kiosk-search-box">
            <svg class="kiosk-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search"
                   id="kiosk-search"
                   class="kiosk-search"
                   placeholder="Buscar colaborador…"
                   autocomplete="off"
                   autofocus>
        </div>
        <div class="filter-chips" id="kiosk-filters" role="tablist" aria-label="Filtrar lista">
            <button type="button" class="filter-chip is-active" data-filter="all" role="tab" aria-selected="true">Todos</button>
            <button type="button" class="filter-chip" data-filter="pending" role="tab">Pendentes</button>
            <button type="button" class="filter-chip" data-filter="yes" role="tab">Almoçaram</button>
            <button type="button" class="filter-chip" data-filter="no" role="tab">Não almoçaram</button>
        </div>
        <p class="kiosk-list-meta" id="kiosk-list-meta"><?= count($employees) ?> colaboradores</p>
    </div>

    <main class="kiosk-main" id="lunch-list" data-date="<?= e($date) ?>" data-locked="<?= $locked ? '1' : '0' ?>">
        <p class="kiosk-no-results is-hidden" id="kiosk-no-results">Nenhum colaborador encontrado.</p>
        <?php if (empty($employees)): ?>
            <p class="kiosk-empty">Lista vazia. Sincronize os colaboradores no painel admin.</p>
        <?php else: ?>
            <ul class="kiosk-grid">
                <?php foreach ($employees as $emp):
                    $hadLunch = $emp['had_lunch'];
                    $isPending = $hadLunch === null;
                    $yesActive = !$isPending && (int) $hadLunch === 1;
                    $noActive = !$isPending && (int) $hadLunch === 0;
                    $displayName = formatName($emp['name']);
                    $status = $isPending ? 'pending' : ($yesActive ? 'yes' : 'no');
                ?>
                <li class="kiosk-card employee-row<?= $isPending ? ' is-pending' : ' is-marked' ?><?= $yesActive ? ' is-marked-yes' : '' ?><?= $noActive ? ' is-marked-no' : '' ?>"
                    data-employee-id="<?= (int) $emp['id'] ?>"
                    data-name="<?= e(strtolower($displayName)) ?>"
                    data-status="<?= $status ?>">
                    <div class="kiosk-card-head">
                        <span class="kiosk-name"><?= e($displayName) ?></span>
                        <?php if (!$isPending): ?>
                            <span class="status-pill status-pill--<?= $yesActive ? 'yes' : 'no' ?>">
                                <?= $yesActive ? 'Almoçou' : 'Não almoçou' ?>
                            </span>
                        <?php else: ?>
                            <span class="status-pill status-pill--pending">Pendente</span>
                        <?php endif; ?>
                    </div>
                    <div class="kiosk-actions lunch-actions">
                        <button type="button"
                                class="btn-lunch btn-yes kiosk-btn<?= $yesActive ? ' active' : '' ?>"
                                data-had-lunch="1"
                                <?= $locked ? ' disabled' : '' ?>
                                aria-pressed="<?= $yesActive ? 'true' : 'false' ?>">
                            Sim, almoçou
                        </button>
                        <button type="button"
                                class="btn-lunch btn-no kiosk-btn<?= $noActive ? ' active' : '' ?>"
                                data-had-lunch="0"
                                <?= $locked ? ' disabled' : '' ?>
                                aria-pressed="<?= $noActive ? 'true' : 'false' ?>">
                            Não almoçou
                        </button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>

    <script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
</body>
</html>
