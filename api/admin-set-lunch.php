<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

try {
    requireAdmin();

    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validateCsrf($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }

    $employeeId = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
    $date = isset($_POST['date']) ? trim((string) $_POST['date']) : '';
    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : 'set';

    if (!$employeeId || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
        exit;
    }

    $employee = Employee::find($employeeId);
    if (!$employee || !(int) $employee['active']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Funcionário não encontrado ou inativo']);
        exit;
    }

    $hadLunch = null;
    if ($action === 'clear') {
        $hadLunch = null;
    } elseif ($action === 'set') {
        $val = filter_input(INPUT_POST, 'had_lunch', FILTER_VALIDATE_INT);
        if ($val === null || !in_array($val, [0, 1], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Status inválido']);
            exit;
        }
        $hadLunch = $val;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        exit;
    }

    LunchRecord::adminSetEmployee($employeeId, $date, $hadLunch);
    $detail = LunchRecord::adminDayDetail($date);

    Logger::info('Admin alterou marcação', [
        'employee_id' => $employeeId,
        'date' => $date,
        'had_lunch' => $hadLunch,
        'admin' => $_SESSION['admin_username'] ?? 'admin',
    ]);

    echo json_encode([
        'success' => true,
        'employee_id' => $employeeId,
        'employee_name' => formatName($employee['name']),
        'date' => $date,
        'had_lunch' => $hadLunch,
        'totals' => $detail['totals'],
        'employees' => $detail['employees'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    Logger::error('admin-set-lunch falhou', ['message' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar marcação.']);
}
