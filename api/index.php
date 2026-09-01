<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

// Homepage
if ($path === '' || $path === '/index.php') {
    require __DIR__ . '/../views/public/main.php';
    exit;
}

$file = __DIR__ . '/../views' . $path . '.php';

if (file_exists($file)) {
    require $file;
} else {
    require __DIR__ . '/../views/public/unauthorized.php'; // or a real 404 page if you have one
}
exit;