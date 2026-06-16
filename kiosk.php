<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

MarkingContext::set(MarkingContext::KIOSK);

if (isset($_GET['action']) && $_GET['action'] === 'lock') {
    kioskLogout();
    header('Location: ' . base_url('kiosk.php'));
    exit;
}

requireKioskAccess();

$data = LunchController::kioskData();
extract($data);
require __DIR__ . '/views/kiosk.php';
