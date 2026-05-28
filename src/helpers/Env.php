<?php
declare(strict_types=1);

final class Env
{
    private static bool $loaded = false;

    public static function load(string $baseDir): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $parent = dirname($baseDir) . DIRECTORY_SEPARATOR . '.env';
        $local = $baseDir . DIRECTORY_SEPARATOR . '.env';

        if (is_file($parent)) {
            self::parseFile($parent);
        }
        if (is_file($local)) {
            self::parseFile($local);
        }
    }

    private static function parseFile(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }

            $name = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $fromEnv = getenv($key);
        if ($fromEnv !== false) {
            return $fromEnv;
        }

        return $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default): int
    {
        $value = self::get($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}
