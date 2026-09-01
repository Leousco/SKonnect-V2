<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');
$path = preg_replace('/\.php$/', '', $path);

// Homepage
if ($path === '' || $path === '/index') {
    header('Location: /public/main');
    exit;
}

if (strpos($path, '/backend/') === 0) {
    $file = __DIR__ . '/..' . $path . '.php';   // maps to backend/routes/auth.php
} else {
    $file = __DIR__ . '/../views' . $path . '.php';
}

if (file_exists($file)) {
    require $file;
} else {
    require __DIR__ . '/../views/public/unauthorized.php'; // TODO: Create a proper 404 page
}
exit;