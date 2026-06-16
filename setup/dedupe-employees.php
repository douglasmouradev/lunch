<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

$pdo = getDB();

function nameKey(string $name): string
{
    $name = trim($name);
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($name, 'UTF-8');
    }

    return strtoupper($name);
}

$rows = $pdo->query('SELECT id, name, department_id, active FROM employees ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$groups = [];

foreach ($rows as $row) {
    $groups[nameKey($row['name'])][] = $row;
}

$removed = 0;
$mergedRecords = 0;

foreach ($groups as $key => $list) {
    if (count($list) < 2) {
        continue;
    }

    usort($list, static function (array $a, array $b) use ($pdo): int {
        $aActive = (int) $a['active'];
        $bActive = (int) $b['active'];
        if ($aActive !== $bActive) {
            return $bActive <=> $aActive;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM lunch_records WHERE employee_id = ?');
        $stmt->execute([$a['id']]);
        $aCount = (int) $stmt->fetchColumn();
        $stmt->execute([$b['id']]);
        $bCount = (int) $stmt->fetchColumn();
        if ($aCount !== $bCount) {
            return $bCount <=> $aCount;
        }

        return (int) $a['id'] <=> (int) $b['id'];
    });

    $keep = $list[0];
    $keepId = (int) $keep['id'];

    for ($i = 1, $c = count($list); $i < $c; $i++) {
        $dupId = (int) $list[$i]['id'];
        echo "Removendo duplicata #{$dupId} ({$list[$i]['name']}) — mantendo #{$keepId}" . PHP_EOL;

        $move = $pdo->prepare(
            'UPDATE lunch_records SET employee_id = ? WHERE employee_id = ?
             AND lunch_date NOT IN (
                SELECT lunch_date FROM (
                    SELECT lunch_date FROM lunch_records WHERE employee_id = ?
                ) x
             )'
        );
        try {
            $move->execute([$keepId, $dupId, $keepId]);
            $mergedRecords += $move->rowCount();
        } catch (PDOException) {
            // ignora conflitos de data única
        }

        $pdo->prepare('DELETE FROM lunch_records WHERE employee_id = ?')->execute([$dupId]);
        $pdo->prepare('DELETE FROM employees WHERE id = ?')->execute([$dupId]);
        $removed++;
    }
}

echo PHP_EOL . "Concluído: {$removed} duplicata(s) removida(s). Registros realocados: {$mergedRecords}." . PHP_EOL;
