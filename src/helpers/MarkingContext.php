<?php
declare(strict_types=1);

/** Contexto de marcação (home vs quiosque) — definido no carregamento da página, não no POST. */
class MarkingContext
{
    public const HOME = 'home';
    public const KIOSK = 'kiosk';

    public static function set(string $context): void
    {
        if (in_array($context, [self::HOME, self::KIOSK], true)) {
            $_SESSION['marking_context'] = $context;
        }
    }

    public static function isKiosk(): bool
    {
        return ($_SESSION['marking_context'] ?? '') === self::KIOSK;
    }

    public static function isHome(): bool
    {
        return ($_SESSION['marking_context'] ?? '') === self::HOME;
    }
}
