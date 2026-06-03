<?php
declare(strict_types=1);

function systemHealthInfo(): array
{
    $gitCommit = null;
    $gitDir = APP_ROOT . DIRECTORY_SEPARATOR . '.git';
    if (is_dir($gitDir)) {
        $head = $gitDir . DIRECTORY_SEPARATOR . 'HEAD';
        if (is_file($head)) {
            $ref = trim((string) file_get_contents($head));
            if (str_starts_with($ref, 'ref: ')) {
                $refFile = $gitDir . DIRECTORY_SEPARATOR . substr($ref, 5);
                if (is_file($refFile)) {
                    $gitCommit = substr(trim((string) file_get_contents($refFile)), 0, 7);
                }
            } else {
                $gitCommit = substr($ref, 0, 7);
            }
        }
    }

    $stats = Employee::adminStats();

    return [
        'timezone' => date_default_timezone_get(),
        'server_time' => date('d/m/Y H:i:s'),
        'today' => date('Y-m-d'),
        'day_lock_time' => defined('DAY_LOCK_TIME') ? (string) DAY_LOCK_TIME : '23:59',
        'marking_mode' => defined('MARKING_MODE') ? (string) MARKING_MODE : 'open',
        'kiosk_pin_set' => defined('KIOSK_PIN') && KIOSK_PIN !== '',
        'require_employee_pin' => EmployeePin::isRequiredForKiosk(),
        'active_employees' => $stats['active'],
        'inactive_employees' => $stats['inactive'],
        'without_pin' => $stats['without_pin'],
        'git_commit' => $gitCommit,
        'app_env' => defined('APP_ENV') ? (string) APP_ENV : 'production',
    ];
}
