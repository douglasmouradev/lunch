<?php
declare(strict_types=1);

class Flash
{
    public static function set(string $message, string $type = 'ok'): void
    {
        $_SESSION['flash'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    /** @return array{message: ?string, type: string, pin_export_ready: bool} */
    public static function pull(): array
    {
        $message = $_SESSION['flash'] ?? null;
        $type = $_SESSION['flash_type'] ?? 'ok';
        $pinReady = !empty($_SESSION['pin_export_ready']);
        unset($_SESSION['flash'], $_SESSION['flash_type'], $_SESSION['pin_export_ready']);

        return [
            'message' => is_string($message) ? $message : null,
            'type' => is_string($type) ? $type : 'ok',
            'pin_export_ready' => $pinReady,
        ];
    }
}
