<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

try {
    $result = EmployeeImporter::syncFromJson();
    if (PHP_SAPI === 'cli') {
        echo "Sincronização concluída:" . PHP_EOL;
        foreach ($result as $k => $v) {
            echo "  {$k}: {$v}" . PHP_EOL;
        }
        exit(0);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true] + $result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
