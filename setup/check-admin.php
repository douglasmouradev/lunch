<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
requireCli();

$pdo = getDB();
$users = $pdo->query('SELECT id, username, must_change_password FROM admin_users')->fetchAll();
echo 'Admin users:' . PHP_EOL;
print_r($users);

$hash = $pdo->query("SELECT password_hash FROM admin_users WHERE username = 'admin' LIMIT 1")->fetchColumn();
echo 'titanium2024: ' . (password_verify('titanium2024', (string) $hash) ? 'OK' : 'FALHA') . PHP_EOL;
