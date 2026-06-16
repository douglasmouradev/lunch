<?php
declare(strict_types=1);

class EmployeeImporter
{
    private const DEPT_NAME = 'Colaboradores';
    private const LIST_FILE = __DIR__ . '/../../data/employees-list.json';

    public static function syncFromJson(?string $file = null): array
    {
        $path = $file ?? self::LIST_FILE;
        if (!is_file($path)) {
            throw new RuntimeException('Arquivo de lista não encontrado: ' . $path);
        }

        $raw = file_get_contents($path);
        $names = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($names)) {
            throw new RuntimeException('JSON inválido na lista de funcionários.');
        }

        return self::syncFromNames($names, 'json');
    }

    /** @param list<string> $names */
    public static function syncFromNames(array $names, string $logSource = 'sync'): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            static fn ($n) => trim((string) $n),
            $names
        ))));

        if ($names === []) {
            throw new RuntimeException('Nenhum nome válido encontrado.');
        }

        file_put_contents(
            self::LIST_FILE,
            json_encode($names, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        $pdo = getDB();
        $deptId = self::ensureDepartment();

        $existing = $pdo->query(
            'SELECT id, name, active FROM employees'
        )->fetchAll();

        $byKey = [];
        foreach ($existing as $row) {
            $byKey[self::nameKey($row['name'])] = $row;
        }

        $imported = 0;
        $reactivated = 0;
        $kept = 0;
        $importKeys = [];

        foreach ($names as $name) {
            $key = self::nameKey($name);
            $importKeys[$key] = true;

            if (isset($byKey[$key])) {
                $emp = $byKey[$key];
                if (!(int) $emp['active']) {
                    Employee::setActive((int) $emp['id'], true);
                    $reactivated++;
                } else {
                    $kept++;
                }
                if ($emp['name'] !== $name) {
                    Employee::update((int) $emp['id'], $name, $deptId);
                }
                continue;
            }

            $created = Employee::create($name, $deptId);
            if ($created['created'] || $created['reactivated']) {
                $imported++;
            }
        }

        $deactivated = 0;
        foreach ($existing as $row) {
            $key = self::nameKey($row['name']);
            if (!isset($importKeys[$key]) && (int) $row['active']) {
                Employee::setActive((int) $row['id'], false);
                $deactivated++;
            }
        }

        $result = [
            'total_list' => count($names),
            'imported' => $imported,
            'reactivated' => $reactivated,
            'kept' => $kept,
            'deactivated' => $deactivated,
        ];

        $adminId = !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
        ImportLog::add(
            $adminId,
            $logSource,
            sprintf(
                '%d nomes — %d novos, %d reativados, %d desativados',
                $result['total_list'],
                $imported,
                $reactivated,
                $deactivated
            ),
            $result
        );

        return $result;
    }

    private static function ensureDepartment(): int
    {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        $stmt->execute([self::DEPT_NAME]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        return Department::create(self::DEPT_NAME);
    }

    private static function nameKey(string $name): string
    {
        $name = trim($name);
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($name, 'UTF-8');
        }
        return strtoupper($name);
    }
}
