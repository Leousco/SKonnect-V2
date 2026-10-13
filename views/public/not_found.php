<?php
require_once __DIR__ . '/../../backend/config/paths.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Page Not Found</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('styles/global.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <main>
        <h1>Page not found</h1>
        <p>The page you requested does not exist or may have moved.</p>
        <a href="<?= htmlspecialchars(appUrl(), ENT_QUOTES, 'UTF-8') ?>">Return to SKonnect</a>
    </main>
</body>
</html>
