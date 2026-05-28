<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers/Env.php';
Env::load(__DIR__);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/helpers/Logger.php';
require_once __DIR__ . '/src/helpers/Format.php';
require_once __DIR__ . '/src/helpers/Security.php';
require_once __DIR__ . '/src/helpers/Csrf.php';
require_once __DIR__ . '/src/helpers/Flash.php';
require_once __DIR__ . '/src/helpers/SetupGuard.php';
require_once __DIR__ . '/src/helpers/MarkingContext.php';
require_once __DIR__ . '/src/helpers/Auth.php';
require_once __DIR__ . '/src/helpers/KioskAuth.php';
require_once __DIR__ . '/src/helpers/EmployeePin.php';
require_once __DIR__ . '/src/helpers/XlsxReader.php';
require_once __DIR__ . '/src/helpers/EmployeeImporter.php';
require_once __DIR__ . '/src/models/Employee.php';
require_once __DIR__ . '/src/models/LunchRecord.php';
require_once __DIR__ . '/src/models/Department.php';
require_once __DIR__ . '/src/models/ImportLog.php';
require_once __DIR__ . '/src/controllers/LunchController.php';
require_once __DIR__ . '/src/controllers/ReportController.php';
require_once __DIR__ . '/src/controllers/AdminController.php';

sendSecurityHeaders();
