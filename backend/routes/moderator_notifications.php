<?php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/NotificationModel.php';

header('Content-Type: application/json; charset=utf-8');

function moderatorNotificationJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$currentRole = RoleMiddleware::currentRole();
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId < 1) {
    moderatorNotificationJson(['success' => false, 'message' => 'Authentication is required.'], 401);
}
if ($currentRole !== 'moderator') {
    moderatorNotificationJson(['success' => false, 'message' => 'This endpoint is for moderators only.'], 403);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
if ($method === 'GET') {
    if (($_GET['action'] ?? '') !== 'list') {
        moderatorNotificationJson(['success' => false, 'message' => 'Unknown action.'], 400);
    }

    $limit = filter_var($_GET['limit'] ?? 6, FILTER_VALIDATE_INT);
    if ($limit === false || $limit < 1 || $limit > 100) {
        moderatorNotificationJson(['success' => false, 'message' => 'Limit must be an integer from 1 to 100.'], 400);
    }
    $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT);
    if ($offset === false || $offset < 0) {
        moderatorNotificationJson(['success' => false, 'message' => 'Offset must be a non-negative integer.'], 400);
    }
    $category = $_GET['category'] ?? '';
    if (!is_string($category) || ($category !== '' && !in_array($category, ['reports', 'admin_activity'], true))) {
        moderatorNotificationJson(['success' => false, 'message' => 'Invalid notification category.'], 400);
    }
    $isRead = $_GET['is_read'] ?? '';
    if ((!is_string($isRead) && !is_int($isRead)) ||
        ($isRead !== '' && !in_array((string)$isRead, ['0', '1'], true))) {
        moderatorNotificationJson(['success' => false, 'message' => 'is_read must be 0 or 1.'], 400);
    }
    $search = $_GET['search'] ?? '';
    if (!is_string($search) || strlen($search) > 200) {
        moderatorNotificationJson(['success' => false, 'message' => 'Search must be a string of at most 200 characters.'], 400);
    }

    try {
        $result = (new NotificationModel())->getModeratorNotifications($userId, [
            'limit' => $limit,
            'offset' => $offset,
            'category' => $category,
            'is_read' => $isRead,
            'search' => trim($search),
        ]);
        moderatorNotificationJson([
            'success' => true,
            'notifications' => $result['notifications'],
            'unreadCount' => $result['unreadCount'],
            'total' => $result['total'],
        ]);
    } catch (Throwable $e) {
        error_log('Moderator notification listing failed: ' . $e->getMessage());
        moderatorNotificationJson(['success' => false, 'message' => 'Unable to load notifications.'], 500);
    }
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    moderatorNotificationJson(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
    moderatorNotificationJson(['success' => false, 'message' => 'A valid JSON request body is required.'], 400);
}
$action = $body['action'] ?? '';
if (!is_string($action)) {
    moderatorNotificationJson(['success' => false, 'message' => 'Action must be a string.'], 400);
}

try {
    $model = new NotificationModel();
    if ($action === 'mark_read') {
        $rawId = $body['id'] ?? null;
        if (!is_int($rawId) && !(is_string($rawId) && preg_match('/^[1-9]\d*$/', $rawId))) {
            moderatorNotificationJson(['success' => false, 'message' => 'A positive notification id is required.'], 400);
        }
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            moderatorNotificationJson(['success' => false, 'message' => 'A positive notification id is required.'], 400);
        }
        if (!$model->markModeratorNotificationRead($id, $userId)) {
            moderatorNotificationJson(['success' => false, 'message' => 'Notification not found.'], 404);
        }
        moderatorNotificationJson(['success' => true]);
    }

    if ($action === 'mark_all_read') {
        $model->markAllModeratorNotificationsRead($userId);
        moderatorNotificationJson(['success' => true]);
    }

    moderatorNotificationJson(['success' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    error_log('Moderator notification update failed: ' . $e->getMessage());
    moderatorNotificationJson(['success' => false, 'message' => 'Unable to update notifications.'], 500);
}
