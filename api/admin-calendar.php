<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

requireAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

try {
    $date = isset($_GET['date']) ? trim((string) $_GET['date']) : '';
    $month = isset($_GET['month']) ? trim((string) $_GET['month']) : '';

    if ($date !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Data inválida']);
            exit;
        }

        $detail = LunchRecord::adminDayDetail($date);
        echo json_encode([
            'success' => true,
            'mode' => 'day',
            ...$detail,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($month !== '' && preg_match('/^(\d{4})-(\d{2})$/', $month, $m)) {
        $year = (int) $m[1];
        $mon = (int) $m[2];
        if ($mon < 1 || $mon > 12) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Mês inválido']);
            exit;
        }

        $summary = LunchRecord::monthSummary($year, $mon);
        echo json_encode([
            'success' => true,
            'mode' => 'month',
            ...$summary,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Informe ?month=YYYY-MM ou ?date=YYYY-MM-DD']);
} catch (Throwable $e) {
    Logger::error('admin-calendar falhou', ['message' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao carregar calendário.']);
}
