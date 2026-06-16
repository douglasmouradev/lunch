<?php
declare(strict_types=1);

class LunchRecord
{
    public static function getForEmployeeDate(int $employeeId, string $date): ?array
    {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT id, had_lunch FROM lunch_records WHERE employee_id = ? AND lunch_date = ? LIMIT 1'
        );
        $stmt->execute([$employeeId, $date]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function toggle(int $employeeId, string $date, int $hadLunch, ?string $source = null): bool
    {
        $pdo = getDB();
        $sql = 'INSERT INTO lunch_records (employee_id, lunch_date, had_lunch, marked_source)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE had_lunch = VALUES(had_lunch),
                    marked_source = VALUES(marked_source),
                    updated_at = CURRENT_TIMESTAMP';
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([$employeeId, $date, $hadLunch, $source]);
    }

    public static function revert(int $employeeId, string $date, ?int $previousHadLunch): bool
    {
        $pdo = getDB();
        if ($previousHadLunch === null) {
            $stmt = $pdo->prepare('DELETE FROM lunch_records WHERE employee_id = ? AND lunch_date = ?');
            return $stmt->execute([$employeeId, $date]);
        }
        return self::toggle($employeeId, $date, $previousHadLunch ? 1 : 0);
    }

    public static function dayTotals(string $date): array
    {
        $pdo = getDB();
        $active = Employee::countActive();

        $sql = 'SELECT
                    SUM(CASE WHEN lr.had_lunch = 1 THEN 1 ELSE 0 END) AS total_yes,
                    SUM(CASE WHEN lr.had_lunch = 0 THEN 1 ELSE 0 END) AS total_no
                FROM lunch_records lr
                INNER JOIN employees e ON e.id = lr.employee_id AND e.active = 1
                WHERE lr.lunch_date = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date]);
        $row = $stmt->fetch() ?: ['total_yes' => 0, 'total_no' => 0];

        $yes = (int) ($row['total_yes'] ?? 0);
        $no = (int) ($row['total_no'] ?? 0);
        $marked = $yes + $no;

        return [
            'total_yes' => $yes,
            'total_no' => $no,
            'total_pending' => max(0, $active - $marked),
            'total_active' => $active,
        ];
    }

    public static function search(
        string $dateStart,
        string $dateEnd,
        ?int $departmentId,
        ?string $status,
        string $sortBy = 'lunch_date',
        string $sortDir = 'DESC',
        int $page = 1,
        int $perPage = 20
    ): array {
        $allowedSort = [
            'employee_name' => 'e.name',
            'department_name' => 'd.name',
            'lunch_date' => 'lr.lunch_date',
            'had_lunch' => 'lr.had_lunch',
            'marked_at' => 'lr.marked_at',
        ];
        $col = $allowedSort[$sortBy] ?? 'lr.lunch_date';
        $dir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $where = ['lr.lunch_date BETWEEN ? AND ?'];
        $params = [$dateStart, $dateEnd];

        if ($departmentId !== null && $departmentId > 0) {
            $where[] = 'e.department_id = ?';
            $params[] = $departmentId;
        }

        if ($status === 'yes') {
            $where[] = 'lr.had_lunch = 1';
        } elseif ($status === 'no') {
            $where[] = 'lr.had_lunch = 0';
        }

        $whereSql = implode(' AND ', $where);

        $pdo = getDB();

        $countSql = "SELECT COUNT(*) FROM lunch_records lr
                     INNER JOIN employees e ON e.id = lr.employee_id
                     INNER JOIN departments d ON d.id = e.department_id
                     WHERE {$whereSql}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT e.name AS employee_name, d.name AS department_name,
                       lr.lunch_date, lr.had_lunch, lr.marked_at, lr.marked_source, lr.id, lr.employee_id
                FROM lunch_records lr
                INNER JOIN employees e ON e.id = lr.employee_id
                INNER JOIN departments d ON d.id = e.department_id
                WHERE {$whereSql}
                ORDER BY {$col} {$dir}
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $summarySql = "SELECT
                           SUM(CASE WHEN lr.had_lunch = 1 THEN 1 ELSE 0 END) AS total_yes,
                           SUM(CASE WHEN lr.had_lunch = 0 THEN 1 ELSE 0 END) AS total_no,
                           COUNT(DISTINCT lr.employee_id) AS total_employees
                       FROM lunch_records lr
                       INNER JOIN employees e ON e.id = lr.employee_id
                       WHERE {$whereSql}";
        $summaryStmt = $pdo->prepare($summarySql);
        $summaryStmt->execute($params);
        $summary = $summaryStmt->fetch() ?: [
            'total_yes' => 0,
            'total_no' => 0,
            'total_employees' => 0,
        ];

        return [
            'records' => $records,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
            'summary' => $summary,
        ];
    }

    public static function exportAll(
        string $dateStart,
        string $dateEnd,
        ?int $departmentId,
        ?string $status
    ): array {
        $result = self::search($dateStart, $dateEnd, $departmentId, $status, 'lunch_date', 'DESC', 1, 100000);
        return $result['records'];
    }

    public static function adminUpdate(int $id, int $hadLunch): bool
    {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE lunch_records SET had_lunch = ? WHERE id = ?');
        return $stmt->execute([$hadLunch, $id]);
    }

    public static function adminAll(int $limit = 100): array
    {
        $pdo = getDB();
        $sql = 'SELECT lr.id, lr.lunch_date, lr.had_lunch, lr.marked_at,
                       e.name AS employee_name, d.name AS department_name
                FROM lunch_records lr
                INNER JOIN employees e ON e.id = lr.employee_id
                INNER JOIN departments d ON d.id = e.department_id
                ORDER BY lr.lunch_date DESC, lr.marked_at DESC
                LIMIT ?';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Resumo por dia de um mês (para o calendário admin). */
    public static function monthSummary(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        $active = Employee::countActive();

        $pdo = getDB();
        $sql = 'SELECT lr.lunch_date,
                       SUM(CASE WHEN lr.had_lunch = 1 THEN 1 ELSE 0 END) AS total_yes,
                       SUM(CASE WHEN lr.had_lunch = 0 THEN 1 ELSE 0 END) AS total_no
                FROM lunch_records lr
                INNER JOIN employees e ON e.id = lr.employee_id AND e.active = 1
                WHERE lr.lunch_date BETWEEN ? AND ?
                GROUP BY lr.lunch_date';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll();

        $days = [];
        foreach ($rows as $row) {
            $yes = (int) $row['total_yes'];
            $no = (int) $row['total_no'];
            $days[$row['lunch_date']] = [
                'total_yes' => $yes,
                'total_no' => $no,
                'total_pending' => max(0, $active - $yes - $no),
                'total_active' => $active,
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'total_active' => $active,
            'days' => $days,
        ];
    }

    /** Define ou remove marcação de um colaborador (admin — qualquer data). */
    public static function adminSetEmployee(int $employeeId, string $date, ?int $hadLunch): bool
    {
        $pdo = getDB();
        if ($hadLunch === null) {
            $stmt = $pdo->prepare('DELETE FROM lunch_records WHERE employee_id = ? AND lunch_date = ?');
            return $stmt->execute([$employeeId, $date]);
        }

        return self::toggle($employeeId, $date, $hadLunch ? 1 : 0, 'admin');
    }

    /** Detalhe de um dia para o calendário admin. */
    public static function adminDayDetail(string $date): array
    {
        $grouped = Employee::activeGroupedByDepartment($date);
        $totals = self::dayTotals($date);
        $flat = [];

        foreach ($grouped as $departmentName => $employees) {
            foreach ($employees as $emp) {
                $hadLunch = $emp['had_lunch'];
                $status = 'pending';
                if ($hadLunch !== null) {
                    $status = (int) $hadLunch === 1 ? 'yes' : 'no';
                }
                $flat[] = [
                    'id' => (int) $emp['id'],
                    'name' => formatName($emp['name']),
                    'department_name' => $departmentName,
                    'had_lunch' => $hadLunch === null ? null : (int) $hadLunch,
                    'status' => $status,
                    'marked_at' => $emp['marked_at'] ?? null,
                ];
            }
        }

        return [
            'date' => $date,
            'employees' => $flat,
            'totals' => $totals,
            'locked' => isDayLocked($date),
        ];
    }
}
