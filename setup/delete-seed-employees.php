<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

$names = [
    'Ana Silva',
    'Bruno Costa',
    'Carla Mendes',
    'Diego Alves',
    'Elena Rocha',
    'Felipe Nunes',
    'Gabriela Dias',
    'Henrique Lima',
    'Isabela Ferreira',
    'João Pedro Santos',
];

$pdo = getDB();
$deleted = 0;

foreach ($names as $name) {
    $key = Employee::nameKey($name);
    $stmt = $pdo->prepare('SELECT id, name FROM employees WHERE name_key = ?');
    $stmt->execute([$key]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows === []) {
        $stmt = $pdo->prepare('SELECT id, name FROM employees');
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (Employee::nameKey($row['name']) === $key) {
                $rows[] = $row;
            }
        }
    }

    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $pdo->prepare('DELETE FROM lunch_records WHERE employee_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
        echo "Excluído #{$id}: {$row['name']}" . PHP_EOL;
        $deleted++;
    }
}

echo PHP_EOL . "Total excluído: {$deleted}" . PHP_EOL;
