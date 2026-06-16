<?php
declare(strict_types=1);

$localConfig = __DIR__ . '/database.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

if (!defined('DB_HOST')) {
    define('DB_HOST', Env::get('DB_HOST', '127.0.0.1') ?? '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', Env::int('DB_PORT', 3306));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', Env::get('DB_NAME', 'titanium_lunch') ?? 'titanium_lunch');
}
if (!defined('DB_USER')) {
    define('DB_USER', Env::get('DB_USER', 'root') ?? 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', Env::get('DB_PASS', '') ?? '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4');
}

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
