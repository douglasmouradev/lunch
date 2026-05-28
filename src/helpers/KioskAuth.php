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

function requireKioskAccess(): void
{
    if (isKioskIdleExpired()) {
        kioskLock();
    }

    if (isKioskUnlocked()) {
        touchKioskActivity();
        MarkingContext::set(MarkingContext::KIOSK);
        return;
    }

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
            MarkingContext::set(MarkingContext::KIOSK);
            header('Location: ' . base_url('kiosk.php'));
            exit;
        }
        $pinError = 'PIN incorreto. Tente novamente.';
        require APP_ROOT . '/views/kiosk-pin.php';
        exit;
    }

    require APP_ROOT . '/views/kiosk-pin.php';
    exit;
}

function requireMarkingAccess(): void
{
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
