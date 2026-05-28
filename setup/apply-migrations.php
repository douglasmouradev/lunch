<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

$dir = __DIR__ . '/migrations';
$files = glob($dir . '/*.sql');
sort($files);

$pdo = getDB();

foreach ($files as $file) {
    $name = basename($file);
    echo "Aplicando {$name}..." . PHP_EOL;
    $sql = file_get_contents($file);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate key name')
                || str_contains($e->getMessage(), 'Duplicate column')
                || str_contains($e->getMessage(), 'already exists')) {
                continue;
            }
            throw $e;
        }
    }
}

echo 'Migrations concluídas.' . PHP_EOL;
