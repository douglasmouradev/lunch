<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

$pdo = getDB();
foreach ([6, 9, 10] as $id) {
    $s = $pdo->prepare('SELECT id, name FROM employees WHERE id = ?');
    $s->execute([$id]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    echo $id . ': ' . ($r ? $r['name'] : '(não existe)') . PHP_EOL;
}
echo 'Total employees: ' . $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn() . PHP_EOL;
