<?php
declare(strict_types=1);

class Department
{
    public static function all(): array
    {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id, name FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name): int
    {
        $pdo = getDB();
        $stmt = $pdo->prepare('INSERT INTO departments (name) VALUES (?)');
        $stmt->execute([trim($name)]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name): bool
    {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE departments SET name = ? WHERE id = ?');
        return $stmt->execute([trim($name), $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = getDB();
        $check = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE department_id = ?');
        $check->execute([$id]);
        if ((int) $check->fetchColumn() > 0) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
