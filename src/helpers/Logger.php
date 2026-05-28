<?php
declare(strict_types=1);

class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        self::rotateIfNeeded($dir);

        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        $ctx = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = date('Y-m-d H:i:s') . " [{$level}] {$message}{$ctx}" . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private static function rotateIfNeeded(string $dir): void
    {
        $maxBytes = defined('LOG_MAX_BYTES') ? (int) LOG_MAX_BYTES : 5242880;
        if ($maxBytes <= 0) {
            return;
        }

        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        if (!is_file($file) || filesize($file) < $maxBytes) {
            return;
        }

        $rotated = $dir . '/app-' . date('Y-m-d') . '-' . date('His') . '.log';
        @rename($file, $rotated);

        $cutoff = time() - 14 * 86400;
        foreach (glob($dir . '/app-*.log') ?: [] as $old) {
            if (is_file($old) && filemtime($old) < $cutoff) {
                @unlink($old);
            }
        }
    }
}
