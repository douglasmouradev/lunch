<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

requireAdmin();

$dateStart = filter_input(INPUT_GET, 'date_start', FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-d');
$dateEnd = filter_input(INPUT_GET, 'date_end', FILTER_SANITIZE_SPECIAL_CHARS) ?? $dateStart;
$departmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
    http_response_code(400);
    die('Datas inválidas.');
}

if ($dateStart > $dateEnd) {
    [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
}

$deptParam = ($departmentId && $departmentId > 0) ? $departmentId : null;

if ($status === 'pending_today') {
    $pending = Employee::pendingOnDate($dateStart, $deptParam);
    $records = array_map(static fn ($r) => [
        'employee_name' => $r['employee_name'],
        'department_name' => $r['department_name'],
        'lunch_date' => $dateStart,
        'had_lunch' => null,
        'marked_at' => null,
    ], $pending);
} else {
    $statusParam = in_array($status, ['yes', 'no'], true) ? $status : null;
    $records = LunchRecord::exportAll($dateStart, $dateEnd, $deptParam, $statusParam);
}

$filename = 'relatorio-almoco-' . $dateStart . '-' . $dateEnd . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, ['Nome', 'Departamento', 'Data', 'Status', 'Horário do Registro'], ';');

foreach ($records as $row) {
    $dateBr = (new DateTime($row['lunch_date']))->format('d/m/Y');
    if ($row['marked_at'] === null) {
        $statusLabel = 'Pendente';
        $timeBr = '—';
    } else {
        $timeBr = (new DateTime($row['marked_at']))->format('d/m/Y H:i:s');
        $statusLabel = (int) $row['had_lunch'] === 1 ? 'Almoçou' : 'Não almoçou';
    }
    fputcsv($out, [
        $row['employee_name'],
        $row['department_name'],
        $dateBr,
        $statusLabel,
        $timeBr,
    ], ';');
}

fclose($out);
