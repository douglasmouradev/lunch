<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

try {
    MarkingContext::set(MarkingContext::KIOSK);
    if (isKioskIdleExpired()) {
        kioskLock();
    }
    if (!isKioskUnlocked()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Quiosque bloqueado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    touchKioskActivity();
    echo json_encode(LunchController::kioskSnapshot(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    Logger::error('kiosk-data falhou', ['message' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno.']);
}
