<?php
declare(strict_types=1);

class AdminController
{
    public static function dispatch(): void
    {
        $action = $_GET['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'change-password') {
                self::changePasswordForm();
                return;
            }
            self::handlePost();
            return;
        }

        match ($action) {
            'logout' => self::logout(),
            'download-pins' => self::downloadPins(),
            'change-password' => self::changePasswordForm(),
            default => self::dashboard(),
        };
    }

    private static function logout(): void
    {
        adminLogout();
        header('Location: ' . base_url('admin/index.php'));
        exit;
    }

    private static function downloadPins(): void
    {
        requireAdmin();
        $list = $_SESSION['pin_export'] ?? [];
        unset($_SESSION['pin_export']);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pins-colaboradores.csv"');
        echo "\xEF\xBB\xBF";
        echo "nome;pin\n";
        foreach ($list as $row) {
            echo '"' . str_replace('"', '""', $row['name']) . '";' . $row['pin'] . "\n";
        }
        exit;
    }

    private static function changePasswordForm(): void
    {
        if (!isAdminLoggedIn()) {
            header('Location: ' . base_url('admin/index.php'));
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::requireValidCsrf();
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if ($new !== $confirm) {
                $error = 'A confirmação da nova senha não confere.';
            } else {
                $result = changeAdminPassword((int) $_SESSION['admin_id'], $current, $new);
                if ($result['success']) {
                    Flash::set('Senha alterada com sucesso.');
                    header('Location: ' . base_url('admin/index.php'));
                    exit;
                }
                $error = $result['error'];
            }
        }

        require APP_ROOT . '/views/admin/change-password.php';
        exit;
    }

    private static function handlePost(): void
    {
        self::requireValidCsrf();

        if (!isAdminLoggedIn()) {
            self::handleLoginPost();
            return;
        }

        requireAdmin(true);

        $entity = $_POST['entity'] ?? '';

        match ($entity) {
            'employee' => self::handleEmployee(),
            'department' => self::handleDepartment(),
            'record' => self::handleRecord(),
            'sync_list' => self::handleSyncList(),
            'upload_excel' => self::handleUploadExcel(),
            'generate_pins' => self::handleGeneratePins(),
            default => null,
        };

        header('Location: ' . base_url('admin/index.php'));
        exit;
    }

    private static function handleLoginPost(): void
    {
        $login = attemptAdminLogin(
            trim((string) ($_POST['username'] ?? '')),
            (string) ($_POST['password'] ?? '')
        );

        if ($login['success']) {
            if (adminMustChangePassword()) {
                header('Location: ' . base_url('admin/index.php?action=change-password'));
                exit;
            }
            header('Location: ' . base_url('admin/index.php'));
            exit;
        }

        $error = $login['error'] ?? 'Usuário ou senha incorretos.';
        require APP_ROOT . '/views/admin/login.php';
        exit;
    }

    private static function handleEmployee(): void
    {
        $empAction = $_POST['employee_action'] ?? '';
        $empId = (int) ($_POST['employee_id'] ?? 0);

        if ($empAction === 'toggle' && $empId > 0) {
            $emp = Employee::find($empId);
            if ($emp) {
                Employee::setActive($empId, !(int) $emp['active']);
                Flash::set('Status do funcionário atualizado.');
            }
            return;
        }

        if ($empAction === 'delete' && $empId > 0) {
            $result = Employee::delete($empId);
            Flash::set(
                $result['success'] ? 'Funcionário excluído.' : ($result['error'] ?? 'Não foi possível excluir.'),
                $result['success'] ? 'ok' : 'error'
            );
            return;
        }

        if ($empAction === 'set_pin' && $empId > 0) {
            $result = EmployeePin::setForEmployee($empId, trim((string) ($_POST['employee_pin'] ?? '')));
            Flash::set(
                $result['success'] ? 'PIN atualizado.' : ($result['error'] ?? 'Erro ao salvar PIN.'),
                $result['success'] ? 'ok' : 'error'
            );
            return;
        }

        if ($empAction !== 'save') {
            return;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $deptId = (int) ($_POST['department_id'] ?? 0);
        $pin = trim((string) ($_POST['employee_pin'] ?? ''));

        if ($name === '' || $deptId <= 0) {
            return;
        }

        if ($empId > 0) {
            Employee::update($empId, $name, $deptId);
            $savedId = $empId;
            Flash::set('Funcionário atualizado.');
        } else {
            $result = Employee::create($name, $deptId);
            $savedId = $result['id'];
            if ($result['created']) {
                Flash::set('Funcionário cadastrado.');
            } elseif ($result['reactivated']) {
                Flash::set('Já existia como inativo e foi reativado. Agora aparece na marcação.');
            } else {
                Flash::set('Já existia na base; nome e departamento foram atualizados.');
            }
        }

        if ($pin !== '') {
            $pinResult = EmployeePin::setForEmployee($savedId, $pin);
            if (!$pinResult['success']) {
                Flash::set(
                    ($_SESSION['flash'] ?? '') . ' ' . ($pinResult['error'] ?? ''),
                    'error'
                );
            }
        }
    }

    private static function handleDepartment(): void
    {
        $deptAction = $_POST['department_action'] ?? '';
        $deptId = (int) ($_POST['department_edit_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($deptAction === 'save' && $name !== '') {
            if ($deptId > 0) {
                Department::update($deptId, $name);
                Flash::set('Departamento atualizado.');
            } else {
                Department::create($name);
                Flash::set('Departamento criado.');
            }
            return;
        }

        if ($deptAction === 'delete' && $deptId > 0) {
            if (Department::delete($deptId)) {
                Flash::set('Departamento excluído.');
            } else {
                Flash::set('Não é possível excluir: há funcionários vinculados.', 'error');
            }
        }
    }

    private static function handleRecord(): void
    {
        $recordAction = $_POST['record_action'] ?? '';
        $recordId = (int) ($_POST['record_id'] ?? 0);
        $hadLunch = (int) ($_POST['had_lunch'] ?? 0);

        if ($recordAction === 'toggle' && $recordId > 0) {
            LunchRecord::adminUpdate($recordId, $hadLunch ? 1 : 0);
            Flash::set('Registro atualizado manualmente.');
        }
    }

    private static function handleSyncList(): void
    {
        try {
            $r = EmployeeImporter::syncFromJson();
            Flash::set(sprintf(
                'Lista sincronizada: %d colaboradores (%d novos, %d reativados, %d desativados).',
                $r['total_list'],
                $r['imported'],
                $r['reactivated'],
                $r['deactivated']
            ));
        } catch (Throwable $ex) {
            Flash::set('Erro ao sincronizar: ' . $ex->getMessage(), 'error');
        }
    }

    private static function handleUploadExcel(): void
    {
        try {
            if (empty($_FILES['excel_file']['tmp_name']) || !is_uploaded_file($_FILES['excel_file']['tmp_name'])) {
                throw new RuntimeException('Selecione um arquivo .xlsx válido.');
            }
            $fileName = $_FILES['excel_file']['name'] ?? '';
            if (!str_ends_with(strtolower($fileName), '.xlsx')) {
                throw new RuntimeException('Apenas arquivos .xlsx são aceitos.');
            }
            $names = XlsxReader::extractNames($_FILES['excel_file']['tmp_name']);
            $r = EmployeeImporter::syncFromNames($names, 'excel');
            Flash::set(sprintf(
                'Planilha importada: %d nomes (%d novos, %d reativados, %d desativados).',
                $r['total_list'],
                $r['imported'],
                $r['reactivated'],
                $r['deactivated']
            ));
        } catch (Throwable $ex) {
            Flash::set('Erro no upload: ' . $ex->getMessage(), 'error');
        }
    }

    private static function handleGeneratePins(): void
    {
        $list = EmployeePin::generateMissingPins();
        $_SESSION['pin_export'] = $list;
        $count = count($list);

        if ($count > 0) {
            Flash::set("{$count} PIN(s) gerado(s). Baixe o arquivo CSV agora (link abaixo).");
            $_SESSION['pin_export_ready'] = true;
        } else {
            Flash::set('Todos os colaboradores ativos já possuem PIN.');
        }
    }

    private static function dashboard(): void
    {
        if (!isAdminLoggedIn()) {
            $error = null;
            require APP_ROOT . '/views/admin/login.php';
            exit;
        }

        if (adminMustChangePassword()) {
            header('Location: ' . base_url('admin/index.php?action=change-password'));
            exit;
        }

        $flash = Flash::pull();
        $employees = Employee::allForAdmin();
        $departments = Department::all();
        $records = LunchRecord::adminAll(150);
        $importLogs = ImportLog::recent(30);
        $employeeStats = Employee::adminStats();
        $systemHealth = systemHealthInfo();

        $pageTitle = 'Administração';
        $activeTab = 'admin';
        $contentView = APP_ROOT . '/views/admin/employees.php';
        $flashMessage = $flash['message'];
        $flashType = $flash['type'];
        $pinExportReady = $flash['pin_export_ready'];
        $extraData = compact(
            'employees',
            'departments',
            'records',
            'importLogs',
            'employeeStats',
            'systemHealth',
            'flashMessage',
            'flashType',
            'pinExportReady'
        );

        require APP_ROOT . '/views/layout.php';
    }

    private static function requireValidCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCsrf($token)) {
            http_response_code(403);
            die('Token CSRF inválido.');
        }
    }
}
