<?php
declare(strict_types=1);

function kioskPinEnabled(): bool
{
    return defined('KIOSK_PIN') && KIOSK_PIN !== '';
}

function isKioskUnlocked(): bool
{
    if (!kioskPinEnabled()) {
        return true;
    }

    return !empty($_SESSION['kiosk_unlocked']) && $_SESSION['kiosk_unlocked'] === true;
}

function touchKioskActivity(): void
{
    if (isKioskUnlocked()) {
        $_SESSION['kiosk_last_activity'] = time();
    }
}

function isKioskIdleExpired(): bool
{
    if (!kioskPinEnabled() || !isKioskUnlocked()) {
        return false;
    }

    $idleMinutes = defined('KIOSK_IDLE_MINUTES') ? (int) KIOSK_IDLE_MINUTES : 15;
    if ($idleMinutes <= 0) {
        return false;
    }

    $last = (int) ($_SESSION['kiosk_last_activity'] ?? 0);
    if ($last === 0) {
        return false;
    }

    return time() - $last > $idleMinutes * 60;
}

function kioskLock(): void
{
    unset($_SESSION['kiosk_unlocked'], $_SESSION['kiosk_last_activity']);
    if (MarkingContext::isKiosk()) {
        MarkingContext::set(MarkingContext::HOME);
    }
}

function kioskLogout(): void
{
    kioskLock();
}

function appPinDefaultRedirect(): string
{
    return base_url('kiosk.php');
}

function appPinRedirectAllowed(string $target): bool
{
    $target = trim($target);
    if ($target === '') {
        return false;
    }
    if (str_contains($target, '://') || str_starts_with($target, '//')) {
        return false;
    }

    return str_starts_with($target, '/');
}

/** Exige PIN do aparelho (KIOSK_PIN) antes de exibir marcação ou quiosque. */
function requireAppPinAccess(?string $redirectAfterUnlock = null): void
{
    if (!kioskPinEnabled()) {
        return;
    }

    if (isKioskIdleExpired()) {
        kioskLock();
    }

    if (isKioskUnlocked()) {
        touchKioskActivity();
        return;
    }

    $pinFormAction = $redirectAfterUnlock ?? appPinDefaultRedirect();
    $pinRedirect = $pinFormAction;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kiosk_pin'])) {
        if (!checkRateLimit('kiosk_device_pin', 10)) {
            $pinError = 'Muitas tentativas. Aguarde um minuto.';
            require APP_ROOT . '/views/kiosk-pin.php';
            exit;
        }

        $pin = (string) ($_POST['kiosk_pin'] ?? '');
        if (hash_equals((string) KIOSK_PIN, $pin)) {
            $_SESSION['kiosk_unlocked'] = true;
            touchKioskActivity();
            $target = trim((string) ($_POST['pin_redirect'] ?? ''));
            if ($target === '' || !appPinRedirectAllowed($target)) {
                $target = $redirectAfterUnlock ?? appPinDefaultRedirect();
            }
            header('Location: ' . $target);
            exit;
        }
        $pinError = 'PIN incorreto. Tente novamente.';
        require APP_ROOT . '/views/kiosk-pin.php';
        exit;
    }

    require APP_ROOT . '/views/kiosk-pin.php';
    exit;
}

function requireKioskAccess(): void
{
    MarkingContext::set(MarkingContext::KIOSK);
    requireAppPinAccess(base_url('kiosk.php'));
}

function requireMarkingAccess(): void
{
    if (kioskPinEnabled() && !isKioskUnlocked() && !isAdminLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Informe o PIN do sistema para marcar.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isPublicMarkingAllowed() && !MarkingContext::isKiosk()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Marcação disponível apenas no quiosque.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (defined('MARKING_MODE') && MARKING_MODE === 'kiosk_only' && !isKioskUnlocked()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Marcação disponível apenas no quiosque desbloqueado.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (MarkingContext::isKiosk() && isKioskUnlocked()) {
        touchKioskActivity();
    }
}
