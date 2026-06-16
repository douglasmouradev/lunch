<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

$appLocal = __DIR__ . '/app.local.php';
if (is_file($appLocal)) {
    require $appLocal;
}

if (!defined('BASE_URL')) {
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $webRoot = defined('USE_PUBLIC_ROOT') && USE_PUBLIC_ROOT
        ? str_replace('\\', '/', APP_ROOT . '/public')
        : str_replace('\\', '/', APP_ROOT);

    if ($docRoot !== '' && str_starts_with($webRoot, $docRoot)) {
        $base = substr($webRoot, strlen($docRoot));
        define('BASE_URL', $base === '' ? '' : $base);
    } else {
        $appRoot = str_replace('\\', '/', APP_ROOT);
        if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
            define('BASE_URL', $base === '' ? '' : $base);
        } else {
            define('BASE_URL', '');
        }
    }
}

if (!defined('APP_ENV')) {
    define('APP_ENV', Env::get('APP_ENV', 'production') ?? 'production');
}

if (!defined('KIOSK_PIN')) {
    define('KIOSK_PIN', Env::get('KIOSK_PIN', '') ?? '');
}

if (!defined('UNDO_SECONDS')) {
    define('UNDO_SECONDS', 30);
}

if (!defined('DAY_LOCK_TIME')) {
    define('DAY_LOCK_TIME', '23:59');
}

if (!defined('BLOCK_WEEKENDS')) {
    define('BLOCK_WEEKENDS', false);
}

if (!defined('KIOSK_IDLE_MINUTES')) {
    define('KIOSK_IDLE_MINUTES', Env::int('KIOSK_IDLE_MINUTES', 15));
}

/** Intervalo (segundos) para o quiosque verificar lista atualizada (0 = desliga). */
if (!defined('KIOSK_REFRESH_SECONDS')) {
    define('KIOSK_REFRESH_SECONDS', Env::int('KIOSK_REFRESH_SECONDS', 120));
}

/** open = marcação na home; kiosk_only = só quiosque (com PIN se configurado) */
if (!defined('MARKING_MODE')) {
    define('MARKING_MODE', Env::get('MARKING_MODE', 'open') ?? 'open');
}

if (!defined('LOG_MAX_BYTES')) {
    define('LOG_MAX_BYTES', 5 * 1024 * 1024);
}

/** No quiosque, exige PIN de 4 dígitos do colaborador para marcar. */
if (!defined('KIOSK_REQUIRE_EMPLOYEE_PIN')) {
    define('KIOSK_REQUIRE_EMPLOYEE_PIN', Env::bool('KIOSK_REQUIRE_EMPLOYEE_PIN', true));
}

/** Fuso para data/hora do almoço (padrão: Salvador / Bahia). */
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', Env::get('APP_TIMEZONE', 'America/Bahia') ?? 'America/Bahia');
}

$appTz = APP_TIMEZONE;
if (!in_array($appTz, timezone_identifiers_list(), true)) {
    $appTz = 'America/Bahia';
}
date_default_timezone_set($appTz);

function base_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = BASE_URL;

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset_path(string $path): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $inPublic = APP_ROOT . '/public/assets/' . $path;
    if (is_file($inPublic)) {
        return $inPublic;
    }

    return APP_ROOT . '/assets/' . $path;
}

function asset_url(string $path): string
{
    $full = asset_path($path);
    $v = is_file($full) ? (string) filemtime($full) : '1';

    return base_url('assets/' . ltrim(str_replace('\\', '/', $path), '/')) . '?v=' . $v;
}

function api_url(string $path): string
{
    return base_url('api/' . ltrim($path, '/'));
}

function isProduction(): bool
{
    return APP_ENV === 'production';
}
