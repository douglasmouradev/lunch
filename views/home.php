<?php
declare(strict_types=1);
/** @var string $date */
/** @var array $grouped */
/** @var array $totals */
/** @var bool $locked */
$dateFormatted = (new DateTime($date))->format('d/m/Y');
$weekdays = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$weekday = $weekdays[(int) (new DateTime($date))->format('w')];
$total = (int) ($totals['total_active'] ?? 0);
$marked = (int) $totals['total_yes'] + (int) $totals['total_no'];
$progress = $total > 0 ? (int) round(($marked / $total) * 100) : 0;
?>
<section class="page-home">
    <header class="page-toolbar">
        <div class="page-toolbar-text">
            <p class="hero-label">Hoje</p>
            <h1 class="page-toolbar-title">
                <time datetime="<?= e($date) ?>"><?= e($dateFormatted) ?></time>
                <span class="page-toolbar-weekday"><?= e($weekday) ?></span>
            </h1>
        </div>
        <a href="<?= e(base_url('kiosk.php')) ?>" class="btn btn-secondary btn-kiosk-link">Abrir quiosque</a>
    </header>

    <?php if (!empty($marking_mode) && $marking_mode === 'kiosk_only'): ?>
        <p class="hero-notice hero-notice--info">
            A marcação do dia é feita apenas no <a href="<?= e(base_url('kiosk.php')) ?>">modo quiosque</a>.
        </p>
    <?php elseif ($locked): ?>
        <p class="hero-notice hero-notice--warn">Marcações encerradas para este dia.</p>
    <?php endif; ?>

    <div class="day-progress" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="day-progress-meta">
            <span id="progress-label"><?= $marked ?> de <?= $total ?> registrados</span>
            <span id="progress-pct"><?= $progress ?>%</span>
        </div>
        <div class="day-progress-track">
            <div class="day-progress-fill" id="progress-fill" style="width: <?= $progress ?>%"></div>
        </div>
    </div>

    <div class="stats-grid" id="stats-grid"
         data-yes="<?= (int) $totals['total_yes'] ?>"
         data-no="<?= (int) $totals['total_no'] ?>"
         data-pending="<?= (int) $totals['total_pending'] ?>"
         data-total="<?= $total ?>">
        <article class="stat-card stat-card--pending">
            <div class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-number" id="stat-pending"><?= (int) $totals['total_pending'] ?></span>
                <span class="stat-label">Pendentes</span>
            </div>
        </article>
        <article class="stat-card stat-card--yes">
            <div class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-number" id="stat-yes"><?= (int) $totals['total_yes'] ?></span>
                <span class="stat-label">Almoçaram</span>
            </div>
        </article>
        <article class="stat-card stat-card--no">
            <div class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-number" id="stat-no"><?= (int) $totals['total_no'] ?></span>
                <span class="stat-label">Não almoçaram</span>
            </div>
        </article>
    </div>

    <div class="departments-list" id="lunch-list" data-date="<?= e($date) ?>" data-locked="<?= $locked ? '1' : '0' ?>">
        <?php if (empty($grouped)): ?>
            <p class="empty-state">Nenhum colaborador ativo cadastrado.</p>
        <?php else: ?>
            <?php foreach ($grouped as $departmentName => $employees): ?>
                <section class="dept-block">
                    <h2 class="dept-title"><?= e($departmentName) ?></h2>
                    <ul class="employee-list">
                        <?php foreach ($employees as $emp):
                            $hadLunch = $emp['had_lunch'];
                            $isPending = $hadLunch === null;
                            $yesActive = !$isPending && (int) $hadLunch === 1;
                            $noActive = !$isPending && (int) $hadLunch === 0;
                            $displayName = formatName($emp['name']);
                        ?>
                        <li class="employee-row<?= $isPending ? ' is-pending' : '' ?><?= $yesActive ? ' is-marked-yes' : '' ?><?= $noActive ? ' is-marked-no' : '' ?>"
                            data-employee-id="<?= (int) $emp['id'] ?>">
                            <div class="employee-row-main">
                                <span class="employee-name"><?= e($displayName) ?></span>
                                <?php if ($isPending): ?>
                                    <span class="status-pill status-pill--pending">Pendente</span>
                                <?php elseif ($yesActive): ?>
                                    <span class="status-pill status-pill--yes">Almoçou</span>
                                <?php else: ?>
                                    <span class="status-pill status-pill--no">Não almoçou</span>
                                <?php endif; ?>
                            </div>
                            <div class="lunch-actions">
                                <button type="button"
                                        class="btn-lunch btn-yes<?= $yesActive ? ' active' : '' ?>"
                                        data-had-lunch="1"
                                        <?= $locked ? ' disabled' : '' ?>
                                        aria-pressed="<?= $yesActive ? 'true' : 'false' ?>">
                                    Almoçou
                                </button>
                                <button type="button"
                                        class="btn-lunch btn-no<?= $noActive ? ' active' : '' ?>"
                                        data-had-lunch="0"
                                        <?= $locked ? ' disabled' : '' ?>
                                        aria-pressed="<?= $noActive ? 'true' : 'false' ?>">
                                    Não almoçou
                                </button>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
