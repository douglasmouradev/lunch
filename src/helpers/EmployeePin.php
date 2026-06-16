<?php
declare(strict_types=1);

class EmployeePin
{
    public static function isRequiredForKiosk(): bool
    {
        return !defined('KIOSK_REQUIRE_EMPLOYEE_PIN') || KIOSK_REQUIRE_EMPLOYEE_PIN;
    }

    public static function isValidFormat(string $pin): bool
    {
        return (bool) preg_match('/^\d{4}$/', $pin);
    }

    public static function normalize(string $pin): string
    {
        return preg_replace('/\D/', '', $pin) ?? '';
    }

    public static function hash(string $pin): string
    {
        return password_hash($pin, PASSWORD_DEFAULT);
    }

    public static function verify(int $employeeId, string $pin): bool
    {
        if (!self::isValidFormat($pin)) {
            return false;
        }

        $pdo = getDB();
        try {
            $stmt = $pdo->prepare('SELECT pin_hash FROM employees WHERE id = ? LIMIT 1');
            $stmt->execute([$employeeId]);
            $hash = $stmt->fetchColumn();
        } catch (PDOException) {
            return false;
        }

        if (!$hash || !is_string($hash)) {
            return false;
        }

        return password_verify($pin, $hash);
    }

    public static function setForEmployee(int $employeeId, string $pin): array
    {
        $pin = self::normalize($pin);
        if (!self::isValidFormat($pin)) {
            return ['success' => false, 'error' => 'O PIN deve ter exatamente 4 números.'];
        }

        $pdo = getDB();
        $hash = self::hash($pin);
        try {
            $stmt = $pdo->prepare('UPDATE employees SET pin_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $employeeId]);
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Coluna pin_hash indisponível. Execute as migrations.'];
        }

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Funcionário não encontrado.'];
        }

        Logger::info('PIN do colaborador atualizado', ['employee_id' => $employeeId]);

        return ['success' => true];
    }

    public static function hasPin(int $employeeId): bool
    {
        $pdo = getDB();
        try {
            $stmt = $pdo->prepare('SELECT pin_hash FROM employees WHERE id = ? LIMIT 1');
            $stmt->execute([$employeeId]);
            $hash = $stmt->fetchColumn();

            return is_string($hash) && $hash !== '';
        } catch (PDOException) {
            return false;
        }
    }

    public static function checkAttemptRateLimit(int $employeeId): bool
    {
        return checkRateLimitBucket('emp_pin_' . $employeeId, 5);
    }

    public static function verifyForMarking(int $employeeId, ?string $pin): ?array
    {
        if (!MarkingContext::isKiosk() || !self::isRequiredForKiosk()) {
            return null;
        }

        if (!self::checkAttemptRateLimit($employeeId)) {
            return ['success' => false, 'error' => 'Muitas tentativas incorretas. Aguarde um minuto.'];
        }

        if (!self::hasPin($employeeId)) {
            return ['success' => false, 'error' => 'PIN não cadastrado. Procure o RH ou a administração.'];
        }

        $pin = self::normalize((string) $pin);
        if (!self::verify($employeeId, $pin)) {
            Logger::warning('PIN de colaborador incorreto', [
                'employee_id' => $employeeId,
                'ip' => clientIp(),
            ]);

            return ['success' => false, 'error' => 'PIN incorreto. Tente novamente.'];
        }

        return null;
    }

    /** @return list<array{name: string, pin: string}> */
    public static function generateMissingPins(): array
    {
        $pdo = getDB();
        $stmt = $pdo->query(
            'SELECT id, name FROM employees WHERE active = 1 AND (pin_hash IS NULL OR pin_hash = \'\') ORDER BY name'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $generated = [];
        $used = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            do {
                $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (isset($used[$pin]));
            $used[$pin] = true;

            self::setForEmployee($id, $pin);
            $generated[] = ['name' => $row['name'], 'pin' => $pin];
        }

        return $generated;
    }
}
