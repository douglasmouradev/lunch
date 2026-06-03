<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

MarkingContext::set(MarkingContext::HOME);

$rawPage = $_GET['page'] ?? 'home';
$page = is_string($rawPage) && in_array($rawPage, ['home', 'report'], true) ? $rawPage : 'home';

if ($page === 'home' && !isset($_GET['page'])) {
    header('Location: ' . base_url('index.php?page=home'));
    exit;
}

if ($page === 'report') {
    requireAdmin();
}

$pageTitle = $page === 'report' ? 'Relatório' : 'Marcação de Almoço';
$activeTab = $page;

if ($page === 'home') {
    requireAppPinAccess(base_url('index.php?page=home'));
    $data = LunchController::homeData();
    extract($data);
    $contentView = __DIR__ . '/views/home.php';
} else {
    $departments = Department::all();
    $contentView = __DIR__ . '/views/report.php';
}

$extraData = [];
require __DIR__ . '/views/layout.php';
