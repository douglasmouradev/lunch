<?php
declare(strict_types=1);

class LunchController
{
    public static function homeData(): array
    {
        $date = date('Y-m-d');
        $grouped = Employee::activeGroupedByDepartment($date);
        $totals = LunchRecord::dayTotals($date);
        $locked = isDayLocked($date) || !isPublicMarkingAllowed();

        return [
            'date' => $date,
            'grouped' => $grouped,
            'totals' => $totals,
            'locked' => $locked,
            'marking_mode' => defined('MARKING_MODE') ? MARKING_MODE : 'open',
        ];
    }

    public static function kioskData(): array
    {
        $date = date('Y-m-d');
        $employees = Employee::activeForKiosk($date);

        return [
            'date' => $date,
            'employees' => $employees,
            'totals' => LunchRecord::dayTotals($date),
            'locked' => isDayLocked($date),
            'idle_minutes' => defined('KIOSK_IDLE_MINUTES') ? (int) KIOSK_IDLE_MINUTES : 15,
            'refresh_seconds' => defined('KIOSK_REFRESH_SECONDS') ? (int) KIOSK_REFRESH_SECONDS : 120,
            'require_employee_pin' => EmployeePin::isRequiredForKiosk(),
            'active_without_pin' => Employee::countActiveWithoutPin(),
            'list_hash' => Employee::kioskListHash($date),
            'server_time' => date('H:i:s'),
        ];
    }

    public static function kioskSnapshot(): array
    {
        $data = self::kioskData();

        return [
            'success' => true,
            'date' => $data['date'],
            'locked' => $data['locked'],
            'list_hash' => $data['list_hash'],
            'server_time' => $data['server_time'],
            'active_without_pin' => $data['active_without_pin'],
            'total_active' => (int) ($data['totals']['total_active'] ?? 0),
            'total_yes' => (int) ($data['totals']['total_yes'] ?? 0),
            'total_no' => (int) ($data['totals']['total_no'] ?? 0),
            'total_pending' => (int) ($data['totals']['total_pending'] ?? 0),
        ];
    }

    private static function markingSource(): string
    {
        return MarkingContext::isKiosk() ? 'kiosk' : 'home';
    }

    public static function toggle(
        int $employeeId,
        int $hadLunch,
        string $date,
        ?string $employeePin = null
    ): array {
        if (!isPublicMarkingAllowed() && !MarkingContext::isKiosk()) {
            return ['success' => false, 'error' => 'Marcação disponível apenas no quiosque.'];
        }

        if (!checkRateLimit('toggle_lunch')) {
            Logger::warning('Rate limit excedido', ['ip' => clientIp(), 'employee_id' => $employeeId]);

            return ['success' => false, 'error' => 'Muitas requisições. Aguarde um momento.'];
        }

        if (isMarkingBlockedByCalendar($date)) {
            return ['success' => false, 'error' => 'Marcação indisponível em finais de semana.'];
        }

        if (isDayLocked($date)) {
            return ['success' => false, 'error' => 'Os registros deste dia estão bloqueados.'];
        }

        $today = date('Y-m-d');
        if ($date !== $today) {
            return [
                'success' => false,
                'error' => 'A marcação só vale para hoje (' . (new DateTime($today))->format('d/m/Y') . '). Recarregue a página.',
            ];
        }

        $employee = Employee::find($employeeId);
        if (!$employee || !(int) $employee['active']) {
            return ['success' => false, 'error' => 'Funcionário não encontrado ou inativo.'];
        }

        $pinError = EmployeePin::verifyForMarking($employeeId, $employeePin);
        if ($pinError !== null) {
            return $pinError;
        }

        $existing = LunchRecord::getForEmployeeDate($employeeId, $date);
        $previousHadLunch = $existing === null ? null : (int) $existing['had_lunch'];

        $hadLunch = $hadLunch ? 1 : 0;
        $source = self::markingSource();
        LunchRecord::toggle($employeeId, $date, $hadLunch, $source);
        $totals = LunchRecord::dayTotals($date);

        $_SESSION['last_lunch_undo'] = [
            'employee_id' => $employeeId,
            'employee_name' => formatName($employee['name']),
            'date' => $date,
            'previous_had_lunch' => $previousHadLunch,
            'new_had_lunch' => $hadLunch,
            'expires_at' => time() + (int) UNDO_SECONDS,
        ];

        Logger::info('Marcação registrada', [
            'employee_id' => $employeeId,
            'name' => $employee['name'],
            'had_lunch' => $hadLunch,
            'date' => $date,
            'source' => $source,
            'ip' => clientIp(),
        ]);

        return [
            'success' => true,
            'employee_id' => $employeeId,
            'employee_name' => formatName($employee['name']),
            'had_lunch' => $hadLunch,
            'total_yes' => $totals['total_yes'],
            'total_no' => $totals['total_no'],
            'total_pending' => $totals['total_pending'],
            'undo_seconds' => (int) UNDO_SECONDS,
        ];
    }

    public static function undoLast(?int $employeeId = null): array
    {
        $undo = $_SESSION['last_lunch_undo'] ?? null;
        if (!$undo || time() > (int) ($undo['expires_at'] ?? 0)) {
            unset($_SESSION['last_lunch_undo']);

            return ['success' => false, 'error' => 'Não há marcação recente para desfazer.'];
        }

        $undoEmployeeId = (int) $undo['employee_id'];
        if ($employeeId !== null && $employeeId !== $undoEmployeeId) {
            return ['success' => false, 'error' => 'Só é possível desfazer a última marcação feita.'];
        }

        $date = $undo['date'];
        if (isDayLocked($date)) {
            return ['success' => false, 'error' => 'Os registros deste dia estão bloqueados.'];
        }

        $previous = $undo['previous_had_lunch'];
        if ($previous !== null) {
            $previous = (int) $previous;
        }

        LunchRecord::revert($undoEmployeeId, $date, $previous);
        unset($_SESSION['last_lunch_undo']);

        $totals = LunchRecord::dayTotals($date);
        $employee = Employee::find($undoEmployeeId);

        Logger::info('Marcação desfeita', ['employee_id' => $undoEmployeeId, 'date' => $date]);

        return [
            'success' => true,
            'employee_id' => $undoEmployeeId,
            'employee_name' => $employee ? formatName($employee['name']) : '',
            'had_lunch' => $previous,
            'total_yes' => $totals['total_yes'],
            'total_no' => $totals['total_no'],
            'total_pending' => $totals['total_pending'],
            'reverted' => true,
        ];
    }
}
