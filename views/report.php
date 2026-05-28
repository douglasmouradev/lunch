<?php
declare(strict_types=1);
/** @var array $departments */
$today = date('Y-m-d');
?>
<section class="page-report">
    <div class="page-header">
        <h1 class="page-title">Relatório de Almoço</h1>
        <p class="page-subtitle">Consulte e exporte registros por período, departamento e status.</p>
    </div>

    <form class="report-filters" id="report-filters" novalidate>
        <div class="report-filters-head">
            <h2 class="report-filters-title">Filtros</h2>
        <div class="filter-actions filter-actions--top">
            <button type="button" class="btn btn-secondary" id="btn-pending-today">Pendentes hoje</button>
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="#" class="btn btn-secondary" id="btn-export-csv">Exportar CSV</a>
            <button type="button" class="btn btn-ghost" id="btn-print">Imprimir</button>
        </div>
        </div>
        <fieldset class="filter-group">
            <legend>Tipo de consulta</legend>
            <label class="radio-chip">
                <input type="radio" name="range_type" value="single" checked>
                <span>Data única</span>
            </label>
            <label class="radio-chip">
                <input type="radio" name="range_type" value="period">
                <span>Período</span>
            </label>
        </fieldset>

        <div class="filter-row">
            <div class="filter-field" id="field-single-date">
                <label for="filter-date">Data</label>
                <input type="date" id="filter-date" name="filter_date" value="<?= e($today) ?>">
            </div>
            <div class="filter-field filter-field--hidden filter-field--period" id="field-period">
                <label for="filter-date-start">Data início</label>
                <input type="date" id="filter-date-start" name="date_start" value="<?= e($today) ?>">
                <label for="filter-date-end">Data fim</label>
                <input type="date" id="filter-date-end" name="date_end" value="<?= e($today) ?>">
            </div>
            <div class="filter-field">
                <label for="filter-department">Departamento</label>
                <select id="filter-department" name="department_id">
                    <option value="">Todos</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int) $dept['id'] ?>"><?= e($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="filter-status">Status</label>
                <select id="filter-status" name="status">
                    <option value="all">Todos</option>
                    <option value="pending_today">Pendentes (sem registro)</option>
                    <option value="yes">Almoçaram</option>
                    <option value="no">Não almoçaram</option>
                </select>
            </div>
        </div>

    </form>

    <div class="report-results" id="report-results">
        <div class="report-cards" id="report-cards" aria-live="polite"></div>
        <div class="table-wrap report-table-wrap">
            <table class="data-table" id="report-table">
                <thead>
                    <tr>
                        <th data-sort="employee_name">Nome <span class="sort-indicator"></span></th>
                        <th data-sort="department_name">Departamento <span class="sort-indicator"></span></th>
                        <th data-sort="lunch_date">Data <span class="sort-indicator"></span></th>
                        <th data-sort="had_lunch">Status <span class="sort-indicator"></span></th>
                        <th data-sort="marked_at">Horário do Registro <span class="sort-indicator"></span></th>
                        <th>Origem</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <tr><td colspan="6" class="table-empty">Use os filtros e clique em Buscar.</td></tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="report-summary" id="report-summary"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <nav class="pagination" id="report-pagination" aria-label="Paginação"></nav>
    </div>

    <div class="print-only" id="print-header">
        <h1>Titanium Lunch — Relatório</h1>
        <p id="print-meta"></p>
    </div>
</section>
