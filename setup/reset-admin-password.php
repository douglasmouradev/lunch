<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

$newPassword = $argv[1] ?? 'titanium2024';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$pdo = getDB();
$stmt = $pdo->prepare('UPDATE admin_users SET password_hash = ?, must_change_password = 1 WHERE username = ?');
$stmt->execute([$hash, 'admin']);

echo "Senha do admin redefinida para: {$newPassword}" . PHP_EOL;
echo "Usuário: admin" . PHP_EOL;
echo "No próximo login será pedido para trocar a senha." . PHP_EOL;
