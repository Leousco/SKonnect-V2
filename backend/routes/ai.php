<?php

header('Content-Type: application/json; charset=utf-8');

function aiJsonResponse(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    aiJsonResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

// Cookie-authenticated POSTs must originate from this site. Browser fetch POSTs
// include Origin; Referer is accepted as a fallback for compatible clients.
$originHeader = $_SERVER['HTTP_ORIGIN'] ?? '';
$refererHeader = $_SERVER['HTTP_REFERER'] ?? '';
$requestHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$requestScheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    ? 'https'
    : 'http';
$sourceHeader = $originHeader !== '' ? $originHeader : $refererHeader;
$source = $sourceHeader !== '' ? parse_url($sourceHeader) : false;
$sourceHost = is_array($source) ? strtolower($source['host'] ?? '') : '';
$sourceScheme = is_array($source) ? strtolower($source['scheme'] ?? '') : '';
$sourcePort = is_array($source) ? ($source['port'] ?? null) : null;
$requestPort = (int) ($_SERVER['SERVER_PORT'] ?? ($requestScheme === 'https' ? 443 : 80));
$effectiveSourcePort = $sourcePort ?? ($sourceScheme === 'https' ? 443 : 80);

if (
    $requestHost === '' ||
    !is_array($source) ||
    !in_array($sourceScheme, ['http', 'https'], true) ||
    $sourceScheme !== $requestScheme ||
    $sourceHost !== strtolower(preg_replace('/:\\d+$/', '', $requestHost)) ||
    $effectiveSourcePort !== $requestPort
) {
    aiJsonResponse(403, ['success' => false, 'message' => 'Request origin not allowed.']);
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../middleware/RoleMiddleware.php';

$residentId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($residentId === false || $residentId === null) {
    aiJsonResponse(401, ['success' => false, 'message' => 'Authentication required.']);
}

if (!RoleMiddleware::is('resident')) {
    aiJsonResponse(403, ['success' => false, 'message' => 'Resident access required.']);
}

$rawBody = file_get_contents('php://input');
try {
    $request = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    aiJsonResponse(400, ['success' => false, 'message' => 'Invalid JSON request body.']);
}

if (!is_array($request) || !array_key_exists('message', $request) || !is_string($request['message'])) {
    aiJsonResponse(400, ['success' => false, 'message' => 'Message must be a string.']);
}

$message = trim($request['message']);
if ($message === '') {
    aiJsonResponse(400, ['success' => false, 'message' => 'Message cannot be empty.']);
}

if (mb_strlen($message, 'UTF-8') > 2000) {
    aiJsonResponse(413, ['success' => false, 'message' => 'Message must be 2,000 characters or fewer.']);
}

require_once __DIR__ . '/../controllers/AIController.php';

try {
    $controller = new AIController();
    aiJsonResponse(200, $controller->handleMessage($message, (int) $residentId));
} catch (Throwable $e) {
    error_log('AI route failure: ' . $e);
    aiJsonResponse(500, ['success' => false, 'message' => 'Unable to process the chat request.']);
}
