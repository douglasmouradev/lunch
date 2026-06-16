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
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validateCsrf($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }

    requireMarkingAccess();

    $employeeId = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
    $hadLunch = filter_input(INPUT_POST, 'had_lunch', FILTER_VALIDATE_INT);
    $date = $_POST['date'] ?? date('Y-m-d');
    $date = is_string($date) ? trim($date) : date('Y-m-d');

    if (!$employeeId || $hadLunch === null || !in_array($hadLunch, [0, 1], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Data inválida']);
        exit;
    }

    $employeePin = isset($_POST['employee_pin']) ? (string) $_POST['employee_pin'] : null;

    $result = LunchController::toggle($employeeId, $hadLunch, $date, $employeePin);
    http_response_code($result['success'] ? 200 : 422);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    Logger::error('toggle-lunch falhou', ['message' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno. Tente novamente.']);
}
