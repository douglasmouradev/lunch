<?php
declare(strict_types=1);

function sendSecurityHeaders(): void
{
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "style-src 'self' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "script-src 'self'; " .
        "img-src 'self' data:;"
    );
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function isWeekend(string $date): bool
{
    $w = (int) (new DateTime($date))->format('N');

    return $w >= 6;
}

function isMarkingBlockedByCalendar(string $date): bool
{
    return defined('BLOCK_WEEKENDS') && BLOCK_WEEKENDS && isWeekend($date);
}

function isDayLocked(string $date): bool
{
    $today = date('Y-m-d');
    if ($date !== $today) {
        return true;
    }

    if (isMarkingBlockedByCalendar($date)) {
        return true;
    }

    $lockTime = defined('DAY_LOCK_TIME') ? (string) DAY_LOCK_TIME : '23:59';
    if (!preg_match('/^\d{2}:\d{2}$/', $lockTime)) {
        $lockTime = '23:59';
    }

    return date('H:i') >= $lockTime;
}

function isPublicMarkingAllowed(): bool
{
    return !defined('MARKING_MODE') || MARKING_MODE !== 'kiosk_only';
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function checkRateLimitBucket(string $bucketKey, int $maxPerMinute): bool
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    $now = time();
    $window = $now - 60;
    if (!isset($_SESSION['rate_limit'][$bucketKey])) {
        $_SESSION['rate_limit'][$bucketKey] = [];
    }
    $_SESSION['rate_limit'][$bucketKey] = array_values(
        array_filter(
            $_SESSION['rate_limit'][$bucketKey],
            static fn (int $t): bool => $t > $window
        )
    );
    if (count($_SESSION['rate_limit'][$bucketKey]) >= $maxPerMinute) {
        return false;
    }
    $_SESSION['rate_limit'][$bucketKey][] = $now;
    return true;
}

function checkRateLimit(string $key, int $maxPerMinute = 60): bool
{
    $sessionOk = checkRateLimitBucket($key . '_session', $maxPerMinute);
    $ipOk = checkRateLimitBucket($key . '_ip_' . clientIp(), $maxPerMinute * 2);

    return $sessionOk && $ipOk;
}

function checkAdminLoginRateLimit(): bool
{
    return checkRateLimit('admin_login', 8);
}
