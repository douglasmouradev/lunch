<?php
declare(strict_types=1);
/** @var array $employees */
/** @var array $departments */
/** @var array $records */
/** @var string|null $flashMessage */
/** @var string|null $flashType */
/** @var bool $pinExportReady */
/** @var array $importLogs */
?>
<section class="admin-panel">
    <div class="admin-header">
        <div class="admin-header-row">
            <div>
                <h1 class="page-title">Administração</h1>
                <p class="page-subtitle">Logado como <?= e($_SESSION['admin_username'] ?? 'admin') ?></p>
            </div>
            <div class="admin-header-actions">
                <a href="<?= e(base_url('kiosk.php')) ?>" class="btn btn-secondary" target="_blank">Quiosque</a>
                <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form upload-excel-form" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="entity" value="upload_excel">
                    <label class="btn btn-secondary btn-file">
                        Importar Excel
                        <input type="file" name="excel_file" accept=".xlsx" class="sr-only">
                    </label>
                </form>
                <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="entity" value="sync_list">
                    <button type="submit" class="btn btn-ghost">Sync JSON</button>
                </form>
                <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form"
                      onsubmit="return confirm('Gerar PIN de 4 dígitos para todos os colaboradores ativos sem PIN?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="entity" value="generate_pins">
                    <button type="submit" class="btn btn-secondary">Gerar PINs</button>
                </form>
                <a href="<?= e(base_url('admin/index.php?action=change-password')) ?>" class="btn btn-ghost">Senha</a>
                <a href="<?= e(base_url('index.php?page=report')) ?>" class="btn btn-secondary">Relatório</a>
                <a href="<?= e(base_url('admin/index.php?action=logout')) ?>" class="btn btn-ghost">Sair</a>
            </div>
        </div>
    </div>

    <?php if (!empty($flashMessage)): ?>
        <p class="flash flash--<?= e($flashType ?? 'ok') ?>" role="status">
            <?= e($flashMessage) ?>
            <?php if (!empty($pinExportReady)): ?>
                <a href="<?= e(base_url('admin/index.php?action=download-pins')) ?>" class="flash-link">Baixar CSV de PINs</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <div class="admin-tabs">
        <button type="button" class="admin-tab is-active" data-tab="employees">Funcionários</button>
        <button type="button" class="admin-tab" data-tab="departments">Departamentos</button>
        <button type="button" class="admin-tab" data-tab="records">Registros</button>
        <button type="button" class="admin-tab" data-tab="imports">Importações</button>
    </div>

    <div class="admin-tab-panel is-active" id="tab-employees">
        <div class="admin-grid">
            <div class="admin-card">
                <h2>Novo / Editar Funcionário</h2>
                <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="admin-form" id="form-employee">
                    <?= csrfField() ?>
                    <input type="hidden" name="entity" value="employee">
                    <input type="hidden" name="employee_id" id="employee_id" value="">
                    <div class="form-field">
                        <label for="emp_name">Nome</label>
                        <input type="text" id="emp_name" name="name" required maxlength="150">
                    </div>
                    <div class="form-field">
                        <label for="emp_department">Departamento</label>
                        <select id="emp_department" name="department_id" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= (int) $dept['id'] ?>"><?= e($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="emp_pin">PIN do quiosque (4 dígitos)</label>
                        <input type="password" id="emp_pin" name="employee_pin" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="Opcional" autocomplete="new-password">
                        <p class="form-hint">Usado pelo colaborador para confirmar a marcação no quiosque.</p>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" name="employee_action" value="save">Salvar</button>
                        <button type="button" class="btn btn-ghost" id="btn-clear-employee">Limpar</button>
                    </div>
                </form>
            </div>
            <div class="admin-card admin-card--wide">
                <h2>Funcionários Cadastrados</h2>
                <div class="table-wrap">
                    <table class="data-table data-table--compact">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Departamento</th>
                                <th>Status</th>
                                <th>PIN</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?= e($emp['name']) ?></td>
                                <td><?= e($emp['department_name']) ?></td>
                                <td>
                                    <span class="badge <?= (int) $emp['active'] ? 'badge--ok' : 'badge--muted' ?>">
                                        <?= (int) $emp['active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($emp['has_pin'])): ?>
                                        <span class="badge badge--ok">Cadastrado</span>
                                    <?php else: ?>
                                        <span class="badge badge--muted">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-link btn-edit-emp"
                                            data-id="<?= (int) $emp['id'] ?>"
                                            data-name="<?= e($emp['name']) ?>"
                                            data-dept="<?= (int) $emp['department_id'] ?>">Editar</button>
                                    <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="entity" value="employee">
                                        <input type="hidden" name="employee_id" value="<?= (int) $emp['id'] ?>">
                                        <button type="submit" class="btn-link" name="employee_action" value="toggle">
                                            <?= (int) $emp['active'] ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form pin-inline-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="entity" value="employee">
                                        <input type="hidden" name="employee_id" value="<?= (int) $emp['id'] ?>">
                                        <input type="password" name="employee_pin" class="pin-inline-input" inputmode="numeric" maxlength="4" pattern="\d{4}" placeholder="PIN" title="Novo PIN de 4 dígitos" required>
                                        <button type="submit" class="btn-link" name="employee_action" value="set_pin">PIN</button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form"
                                          onsubmit="return confirm('Excluir <?= e($emp['name']) ?>? Os registros de almoço desta pessoa também serão removidos.');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="entity" value="employee">
                                        <input type="hidden" name="employee_id" value="<?= (int) $emp['id'] ?>">
                                        <button type="submit" class="btn-link btn-link--danger" name="employee_action" value="delete">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-tab-panel" id="tab-departments">
        <div class="admin-grid">
            <div class="admin-card">
                <h2>Novo / Editar Departamento</h2>
                <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="admin-form" id="form-department">
                    <?= csrfField() ?>
                    <input type="hidden" name="entity" value="department">
                    <input type="hidden" name="department_edit_id" id="department_edit_id" value="">
                    <div class="form-field">
                        <label for="dept_name">Nome</label>
                        <input type="text" id="dept_name" name="name" required maxlength="100">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" name="department_action" value="save">Salvar</button>
                        <button type="button" class="btn btn-ghost" id="btn-clear-dept">Limpar</button>
                    </div>
                </form>
            </div>
            <div class="admin-card admin-card--wide">
                <h2>Departamentos</h2>
                <ul class="simple-list">
                    <?php foreach ($departments as $dept): ?>
                    <li class="simple-list-item">
                        <span><?= e($dept['name']) ?></span>
                        <span class="simple-list-actions">
                            <button type="button" class="btn-link btn-edit-dept"
                                    data-id="<?= (int) $dept['id'] ?>"
                                    data-name="<?= e($dept['name']) ?>">Editar</button>
                            <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form"
                                  onsubmit="return confirm('Excluir este departamento?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="entity" value="department">
                                <input type="hidden" name="department_edit_id" value="<?= (int) $dept['id'] ?>">
                                <button type="submit" class="btn-link btn-link--danger" name="department_action" value="delete">Excluir</button>
                            </form>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="admin-tab-panel" id="tab-imports">
        <div class="admin-card admin-card--wide">
            <h2>Histórico de importações</h2>
            <?php if (empty($importLogs)): ?>
                <p class="table-empty">Nenhuma importação registrada. Execute uma sync ou upload Excel.</p>
            <?php else: ?>
                <ul class="import-log-list">
                    <?php foreach ($importLogs as $log):
                        $when = (new DateTime($log['created_at']))->format('d/m/Y H:i');
                    ?>
                    <li class="import-log-item">
                        <span class="import-log-type badge"><?= e($log['source_type']) ?></span>
                        <span class="import-log-summary"><?= e($log['summary']) ?></span>
                        <span class="import-log-meta mono"><?= e($when) ?><?= !empty($log['admin_username']) ? ' · ' . e($log['admin_username']) : '' ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-tab-panel" id="tab-records">
        <div class="admin-card admin-card--wide">
            <h2>Registros Recentes (edição manual)</h2>
            <div class="table-wrap">
                <table class="data-table data-table--compact">
                    <thead>
                        <tr>
                            <th>Funcionário</th>
                            <th>Departamento</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Horário</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="6" class="table-empty">Nenhum registro encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($records as $rec):
                                $d = (new DateTime($rec['lunch_date']))->format('d/m/Y');
                                $t = (new DateTime($rec['marked_at']))->format('H:i');
                            ?>
                            <tr>
                                <td><?= e($rec['employee_name']) ?></td>
                                <td><?= e($rec['department_name']) ?></td>
                                <td class="mono"><?= e($d) ?></td>
                                <td><?= (int) $rec['had_lunch'] ? 'Almoçou' : 'Não almoçou' ?></td>
                                <td class="mono"><?= e($t) ?></td>
                                <td>
                                    <form method="post" action="<?= e(base_url('admin/index.php')) ?>" class="inline-form record-toggle-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="entity" value="record">
                                        <input type="hidden" name="record_id" value="<?= (int) $rec['id'] ?>">
                                        <input type="hidden" name="had_lunch" value="<?= (int) $rec['had_lunch'] === 1 ? '0' : '1' ?>">
                                        <button type="submit" class="btn-link" name="record_action" value="toggle">
                                            Marcar como <?= (int) $rec['had_lunch'] ? 'não almoçou' : 'almoçou' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
