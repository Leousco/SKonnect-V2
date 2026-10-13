<?php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../services/EmailService.php';

RoleMiddleware::requireAuth();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_POST['token'] ?? '';
if (!is_string($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired email dispatch token.']);
    exit;
}
$token = trim($token);
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired email dispatch token.']);
    exit;
}

$job = $_SESSION['deferred_email_notifications'][$token] ?? null;
if (
    !is_array($job)
    || (int)($job['user_id'] ?? 0) !== (int)$_SESSION['user_id']
    || !is_array($job['notification'] ?? null)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired email dispatch token.']);
    exit;
}

unset($_SESSION['deferred_email_notifications'][$token]);
$_SESSION['deferred_email_notification_tokens'] = array_values(array_filter(
    $_SESSION['deferred_email_notification_tokens'] ?? [],
    static fn($queuedToken): bool => is_string($queuedToken)
        && preg_match('/^[a-f0-9]{64}$/', $queuedToken)
        && $queuedToken !== $token
));
if (empty($_SESSION['deferred_email_notifications'])) {
    unset($_SESSION['deferred_email_notifications'], $_SESSION['deferred_email_notification_tokens']);
}
session_write_close();

try {
    $sent = (new EmailService())->deliverQueuedNotification($job['notification']);
    if (!$sent) {
        error_log('Deferred SKonnect email delivery failed.');
    }
    echo json_encode(['success' => $sent]);
} catch (Throwable $e) {
    error_log('Deferred SKonnect email delivery error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Email delivery failed.']);
}
