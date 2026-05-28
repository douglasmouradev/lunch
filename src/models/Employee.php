<?php
declare(strict_types=1);

class Employee
{
    public static function nameKey(string $name): string
    {
        $name = trim($name);
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($name, 'UTF-8');
        }

        return strtoupper($name);
    }

    public static function findByNameKey(string $name): ?array
    {
        $pdo = getDB();
        $key = self::nameKey($name);
        $stmt = $pdo->prepare('SELECT id, name, active, department_id FROM employees WHERE name_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function activeGroupedByDepartment(string $date): array
    {
        $pdo = getDB();
        $sql = 'SELECT e.id, e.name, e.department_id, d.name AS department_name,
                       lr.had_lunch, lr.marked_at
                FROM employees e
                INNER JOIN departments d ON d.id = e.department_id
                LEFT JOIN lunch_records lr ON lr.employee_id = e.id AND lr.lunch_date = ?
                WHERE e.active = 1
                ORDER BY d.name ASC, e.name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date]);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $dept = $row['department_name'];
            if (!isset($grouped[$dept])) {
                $grouped[$dept] = [];
            }
            $grouped[$dept][] = $row;
        }
        return $grouped;
    }

    public static function allForAdmin(): array
    {
        $pdo = getDB();
        try {
            $sql = 'SELECT e.id, e.name, e.active, e.department_id, d.name AS department_name,
                           (e.pin_hash IS NOT NULL AND e.pin_hash != \'\') AS has_pin
                    FROM employees e
                    INNER JOIN departments d ON d.id = e.department_id
                    ORDER BY e.active DESC, e.name ASC';

            return $pdo->query($sql)->fetchAll();
        } catch (PDOException) {
            $sql = 'SELECT e.id, e.name, e.active, e.department_id, d.name AS department_name
                    FROM employees e
                    INNER JOIN departments d ON d.id = e.department_id
                    ORDER BY e.active DESC, e.name ASC';
            $rows = $pdo->query($sql)->fetchAll();
            foreach ($rows as &$row) {
                $row['has_pin'] = 0;
            }

            return $rows;
        }
    }

    public static function find(int $id): ?array
    {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT e.*, d.name AS department_name FROM employees e
             INNER JOIN departments d ON d.id = e.department_id WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, int $departmentId): int
    {
        $existing = self::findByNameKey($name);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $pdo = getDB();
        $trimmed = trim($name);
        $key = self::nameKey($trimmed);
        try {
            $stmt = $pdo->prepare('INSERT INTO employees (name, name_key, department_id) VALUES (?, ?, ?)');
            $stmt->execute([$trimmed, $key, $departmentId]);
        } catch (PDOException) {
            $stmt = $pdo->prepare('INSERT INTO employees (name, department_id) VALUES (?, ?)');
            $stmt->execute([$trimmed, $departmentId]);
        }

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name, int $departmentId): bool
    {
        $pdo = getDB();
        $trimmed = trim($name);
        $key = self::nameKey($trimmed);
        try {
            $stmt = $pdo->prepare('UPDATE employees SET name = ?, name_key = ?, department_id = ? WHERE id = ?');

            return $stmt->execute([$trimmed, $key, $departmentId, $id]);
        } catch (PDOException) {
            $stmt = $pdo->prepare('UPDATE employees SET name = ?, department_id = ? WHERE id = ?');

            return $stmt->execute([$trimmed, $departmentId, $id]);
        }
    }

    public static function delete(int $id): array
    {
        $emp = self::find($id);
        if (!$emp) {
            return ['success' => false, 'error' => 'Funcionário não encontrado.'];
        }

        $pdo = getDB();
        $pdo->prepare('DELETE FROM lunch_records WHERE employee_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);

        Logger::info('Funcionário excluído', ['id' => $id, 'name' => $emp['name']]);

        return ['success' => true];
    }

    public static function setActive(int $id, bool $active): bool
    {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE employees SET active = ? WHERE id = ?');
        return $stmt->execute([$active ? 1 : 0, $id]);
    }

    public static function countActive(): int
    {
        $pdo = getDB();
        return (int) $pdo->query('SELECT COUNT(*) FROM employees WHERE active = 1')->fetchColumn();
    }

    /** Colaboradores ativos sem registro na data. */
    public static function pendingOnDate(string $date, ?int $departmentId = null): array
    {
        $pdo = getDB();
        $sql = 'SELECT e.id AS employee_id, e.name AS employee_name, d.name AS department_name
                FROM employees e
                INNER JOIN departments d ON d.id = e.department_id
                LEFT JOIN lunch_records lr ON lr.employee_id = e.id AND lr.lunch_date = ?
                WHERE e.active = 1 AND lr.id IS NULL';
        $params = [$date];
        if ($departmentId !== null && $departmentId > 0) {
            $sql .= ' AND e.department_id = ?';
            $params[] = $departmentId;
        }
        $sql .= ' ORDER BY e.name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Lista plana para modo quiosque (ordem alfabética). */
    public static function activeForKiosk(string $date): array
    {
        $pdo = getDB();
        $sql = 'SELECT e.id, e.name, lr.had_lunch, lr.marked_at
                FROM employees e
                LEFT JOIN lunch_records lr ON lr.employee_id = e.id AND lr.lunch_date = ?
                WHERE e.active = 1
                ORDER BY CASE WHEN lr.had_lunch IS NULL THEN 0 ELSE 1 END, e.name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }
}
