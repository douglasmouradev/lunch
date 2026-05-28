<?php
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

if (str_starts_with($uri, '/assets/')) {
    $asset = dirname(__DIR__) . $uri;
    if (is_file($asset)) {
        $ext = pathinfo($asset, PATHINFO_EXTENSION);
        $types = ['css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($asset);
        return true;
    }
}

if (str_starts_with($uri, '/api/')) {
    $script = __DIR__ . $uri;
    if (is_file($script)) {
        require $script;
        return true;
    }
}

if (str_starts_with($uri, '/export/')) {
    $script = __DIR__ . $uri;
    if (is_file($script)) {
        require $script;
        return true;
    }
}

if ($uri === '/manifest.json' && is_file(__DIR__ . '/../manifest.json')) {
    header('Content-Type: application/manifest+json');
    readfile(__DIR__ . '/../manifest.json');
    return true;
}

define('USE_PUBLIC_ROOT', true);

if ($uri === '/kiosk.php' || $uri === '/kiosk') {
    require __DIR__ . '/kiosk.php';
    return true;
}

if (str_starts_with($uri, '/admin')) {
    require __DIR__ . '/admin/index.php';
    return true;
}

require __DIR__ . '/index.php';
return true;
