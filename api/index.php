<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = rawurldecode(is_string($requestPath) ? $requestPath : '/');
$requestPath = '/' . trim($requestPath, '/');

if ($requestPath === '/' || in_array($requestPath, ['/index', '/index.php'], true)) {
    header('Location: /views/public/main.php');
    exit;
}

if (str_starts_with($requestPath, '/backend/')) {
    $relativePath = $requestPath;
} elseif (str_starts_with($requestPath, '/views/')) {
    $relativePath = $requestPath;
} else {
    $relativePath = '/views' . $requestPath;
}

if (!preg_match('#^/(?:backend|views)/[A-Za-z0-9_./-]+$#', $relativePath)) {
    http_response_code(404);
    require __DIR__ . '/../views/public/not_found.php';
    exit;
}

if (!preg_match('/\.php$/i', $relativePath)) {
    $relativePath .= '.php';
}

$projectRoot = realpath(__DIR__ . '/..');
$target = realpath($projectRoot . $relativePath);
$allowedRoots = [
    realpath($projectRoot . '/backend'),
    realpath($projectRoot . '/views'),
];
$isAllowedTarget = $target !== false && is_file($target);
if ($isAllowedTarget) {
    $isAllowedTarget = false;
    foreach ($allowedRoots as $allowedRoot) {
        if ($allowedRoot !== false && str_starts_with($target, $allowedRoot . DIRECTORY_SEPARATOR)) {
            $isAllowedTarget = true;
            break;
        }
    }
}

if (!$isAllowedTarget) {
    http_response_code(404);
    require __DIR__ . '/../views/public/not_found.php';
    exit;
}

require $target;
