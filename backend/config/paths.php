<?php

function appBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    foreach (['/backend/', '/views/', '/api/'] as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            return substr($scriptName, 0, $position);
        }
    }

    return '';
}

function appUrl(string $path = ''): string
{
    $basePath = rtrim(appBasePath(), '/');
    $path = ltrim($path, '/');

    return $basePath . ($path === '' ? '/' : '/' . $path);
}

function appAbsoluteUrl(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return appUrl($path);
    }

    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    $scheme = in_array($forwardedProto, ['http', 'https'], true)
        ? $forwardedProto
        : ((!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http');

    return $scheme . '://' . $host . appUrl($path);
}

function publicAssetUrl(?string $path): string
{
    if ($path === null || trim($path) === '') {
        return '';
    }

    $path = str_replace('\\', '/', trim($path));
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    // Paths written by older uploads may be absolute filesystem paths. Keep
    // only their public assets/uploads suffix before building the URL.
    if (preg_match('#(?:^|/)(assets|uploads)/(.+)$#i', $path, $matches)) {
        return appUrl($matches[1] . '/' . $matches[2]);
    }

    // Relative paths in the database must be rooted at the application, not
    // relative to whichever nested management page rendered them.
    if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) && $path[0] !== '/') {
        return appUrl($path);
    }

    return $path;
}
