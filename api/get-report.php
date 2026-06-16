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
    $filters = [
        'date_start' => isset($_GET['date_start']) ? trim((string) $_GET['date_start']) : date('Y-m-d'),
        'date_end' => isset($_GET['date_end']) ? trim((string) $_GET['date_end']) : date('Y-m-d'),
        'department_id' => filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT),
        'status' => isset($_GET['status']) ? trim((string) $_GET['status']) : 'all',
        'sort_by' => isset($_GET['sort_by']) ? trim((string) $_GET['sort_by']) : 'lunch_date',
        'sort_dir' => isset($_GET['sort_dir']) ? trim((string) $_GET['sort_dir']) : 'DESC',
        'page' => filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1,
    ];

    foreach (['date_start', 'date_end'] as $key) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Data inválida']);
            exit;
        }
    }

    $result = ReportController::getReport($filters);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    Logger::error('get-report falhou', ['message' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao gerar relatório.']);
}
