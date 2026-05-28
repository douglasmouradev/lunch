<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

$list = EmployeePin::generateMissingPins();
$out = dirname(__DIR__) . '/storage/pins-gerados.csv';
$fp = fopen($out, 'w');
fprintf($fp, "\xEF\xBB\xBF");
fputcsv($fp, ['nome', 'pin'], ';');
foreach ($list as $row) {
    fputcsv($fp, [$row['name'], $row['pin']], ';');
}
fclose($fp);

echo count($list) . ' PIN(s) gerado(s). Arquivo: ' . $out . PHP_EOL;
