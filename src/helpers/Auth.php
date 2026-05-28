<?php
declare(strict_types=1);

const ADMIN_SESSION_TIMEOUT = 7200;
const DEFAULT_ADMIN_PASSWORD = 'titanium2024';

function isAdminLoggedIn(): bool
{
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    $last = $_SESSION['admin_last_activity'] ?? 0;
    if (time() - $last > ADMIN_SESSION_TIMEOUT) {
        adminLogout();
        return false;
    }
    $_SESSION['admin_last_activity'] = time();

    return true;
}

function adminMustChangePassword(): bool
{
    if (!isAdminLoggedIn()) {
        return false;
    }

    return !empty($_SESSION['admin_must_change_password']);
}

function requireAdmin(bool $allowPasswordChange = false): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . base_url('admin/index.php'));
        exit;
    }

    if (!$allowPasswordChange && adminMustChangePassword()) {
        header('Location: ' . base_url('admin/index.php?action=change-password'));
        exit;
    }
}

function adminLogin(int $adminId, string $username, bool $mustChangePassword): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_must_change_password'] = $mustChangePassword;
}

function adminLogout(): void
{
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_username'],
        $_SESSION['admin_last_activity'],
        $_SESSION['admin_must_change_password']
    );
}

/** @return array{success: bool, error?: string} */
function attemptAdminLogin(string $username, string $password): array
{
    if (!checkAdminLoginRateLimit()) {
        return [
            'success' => false,
            'error' => 'Muitas tentativas de login. Aguarde um minuto e tente novamente.',
        ];
    }

    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, must_change_password FROM admin_users WHERE username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        Logger::warning('Login admin falhou', ['username' => $username, 'ip' => clientIp()]);

        return ['success' => false, 'error' => 'Usuário ou senha incorretos.'];
    }

    $mustChange = (int) ($user['must_change_password'] ?? 0) === 1;
    if (password_verify(DEFAULT_ADMIN_PASSWORD, $user['password_hash'])) {
        $mustChange = true;
    }

    adminLogin((int) $user['id'], $user['username'], $mustChange);

    return ['success' => true];
}

function changeAdminPassword(int $adminId, string $currentPassword, string $newPassword): array
{
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'A nova senha deve ter pelo menos 8 caracteres.'];
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = ? LIMIT 1');
    $stmt->execute([$adminId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Senha atual incorreta.'];
    }

    if (password_verify($newPassword, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Escolha uma senha diferente da atual.'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE admin_users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
    $upd->execute([$hash, $adminId]);
    $_SESSION['admin_must_change_password'] = false;

    Logger::info('Senha admin alterada', ['admin_id' => $adminId]);

    return ['success' => true];
}
