<?php
declare(strict_types=1);

class ReportController
{
    public static function getReport(array $filters): array
    {
        $dateStart = $filters['date_start'] ?? date('Y-m-d');
        $dateEnd = $filters['date_end'] ?? $dateStart;
        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $departmentId = isset($filters['department_id']) && $filters['department_id'] !== ''
            ? (int) $filters['department_id']
            : null;
        if ($departmentId === 0) {
            $departmentId = null;
        }

        $status = $filters['status'] ?? 'all';
        $sortBy = $filters['sort_by'] ?? 'lunch_date';
        $sortDir = $filters['sort_dir'] ?? 'DESC';
        $page = max(1, (int) ($filters['page'] ?? 1));

        if ($status === 'pending_today') {
            return self::pendingTodayReport($dateStart, $departmentId, $page);
        }

        if (!in_array($status, ['all', 'yes', 'no'], true)) {
            $status = 'all';
        }
        $statusParam = $status === 'all' ? null : $status;

        $result = LunchRecord::search(
            $dateStart,
            $dateEnd,
            $departmentId,
            $statusParam,
            $sortBy,
            $sortDir,
            $page,
            20
        );

        $records = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'employee_name' => $row['employee_name'],
                'department_name' => $row['department_name'],
                'lunch_date' => $row['lunch_date'],
                'status' => (int) $row['had_lunch'] === 1 ? 'Almoçou' : 'Não almoçou',
                'had_lunch' => (int) $row['had_lunch'],
                'marked_at' => $row['marked_at'],
                'marked_source' => $row['marked_source'] ?? null,
            ];
        }, $result['records']);

        return [
            'success' => true,
            'mode' => 'records',
            'records' => $records,
            'pagination' => [
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages'],
            ],
            'summary' => [
                'total_yes' => (int) ($result['summary']['total_yes'] ?? 0),
                'total_no' => (int) ($result['summary']['total_no'] ?? 0),
                'total_employees' => (int) ($result['summary']['total_employees'] ?? 0),
            ],
            'sort' => ['by' => $sortBy, 'dir' => $sortDir],
        ];
    }

    private static function pendingTodayReport(string $date, ?int $departmentId, int $page): array
    {
        $all = Employee::pendingOnDate($date, $departmentId);
        $perPage = 20;
        $total = count($all);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($all, $offset, $perPage);

        $records = array_map(static function (array $row) use ($date): array {
            return [
                'id' => 0,
                'employee_name' => $row['employee_name'],
                'department_name' => $row['department_name'],
                'lunch_date' => $date,
                'status' => 'Pendente',
                'had_lunch' => null,
                'marked_at' => null,
            ];
        }, $slice);

        return [
            'success' => true,
            'mode' => 'pending',
            'records' => $records,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ],
            'summary' => [
                'total_yes' => 0,
                'total_no' => 0,
                'total_employees' => $total,
                'total_pending' => $total,
            ],
            'sort' => ['by' => 'employee_name', 'dir' => 'ASC'],
        ];
    }
}
