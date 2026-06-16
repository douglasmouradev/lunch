<?php
declare(strict_types=1);

/** Formata nome para exibição (título, preposições em minúsculo). */
function formatName(string $name): string
{
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') {
        return '';
    }

    $lower = function_exists('mb_strtolower')
        ? mb_strtolower($name, 'UTF-8')
        : strtolower($name);

    $parts = explode(' ', $lower);
    $small = ['de', 'da', 'do', 'dos', 'das', 'e'];

    foreach ($parts as $i => $part) {
        if ($i > 0 && in_array($part, $small, true)) {
            continue;
        }
        $parts[$i] = function_exists('mb_convert_case')
            ? mb_convert_case($part, MB_CASE_TITLE, 'UTF-8')
            : ucfirst($part);
    }

    return implode(' ', $parts);
}
