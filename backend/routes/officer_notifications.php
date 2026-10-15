<?php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../services/OfficerNotificationService.php';

header('Content-Type: application/json; charset=utf-8');

function officerNotificationJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$currentRole = RoleMiddleware::currentRole();
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId < 1) {
    officerNotificationJson(['success' => false, 'message' => 'Authentication is required.'], 401);
}
if ($currentRole !== 'sk_officer') {
    officerNotificationJson(['success' => false, 'message' => 'This endpoint is for officers only.'], 403);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action !== 'list') {
        officerNotificationJson(['success' => false, 'message' => 'Unknown action.'], 400);
    }

    $limit = filter_var($_GET['limit'] ?? 6, FILTER_VALIDATE_INT);
    if ($limit === false || $limit < 1 || $limit > 100) {
        officerNotificationJson(['success' => false, 'message' => 'Limit must be an integer from 1 to 100.'], 400);
    }
    $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT);
    if ($offset === false || $offset < 0) {
        officerNotificationJson(['success' => false, 'message' => 'Offset must be a non-negative integer.'], 400);
    }

    $categories = ['requests', 'attention', 'admin_updates', 'events', 'announcements'];
    $category = $_GET['category'] ?? '';
    if (!is_string($category) || ($category !== '' && !in_array($category, $categories, true))) {
        officerNotificationJson(['success' => false, 'message' => 'Invalid notification category.'], 400);
    }

    $isRead = $_GET['is_read'] ?? '';
    if (!is_string($isRead) && !is_int($isRead)) {
        officerNotificationJson(['success' => false, 'message' => 'is_read must be 0 or 1.'], 400);
    }
    if ($isRead !== '' && !in_array((string)$isRead, ['0', '1'], true)) {
        officerNotificationJson(['success' => false, 'message' => 'is_read must be 0 or 1.'], 400);
    }

    $search = $_GET['search'] ?? '';
    if (!is_string($search) || strlen($search) > 200) {
        officerNotificationJson(['success' => false, 'message' => 'Search must be a string of at most 200 characters.'], 400);
    }

    try {
        $now = time();
        $lastGeneration = (int)($_SESSION['officer_notification_generation_at'] ?? 0);
        if ($now - $lastGeneration >= 300) {
            (new OfficerNotificationService())->generateOnListRequest($userId);
            $_SESSION['officer_notification_generation_at'] = $now;
        }

        $result = (new NotificationModel())->getOfficerNotifications($userId, [
            'limit' => $limit,
            'offset' => $offset,
            'category' => $category,
            'is_read' => $isRead,
            'search' => trim($search),
        ]);
        officerNotificationJson([
            'success' => true,
            'notifications' => $result['notifications'],
            'unreadCount' => $result['unreadCount'],
            'total' => $result['total'],
        ]);
    } catch (Throwable $e) {
        error_log('Officer notification listing failed: ' . $e->getMessage());
        officerNotificationJson(['success' => false, 'message' => 'Unable to load notifications.'], 500);
    }
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    officerNotificationJson(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true);
if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
    officerNotificationJson(['success' => false, 'message' => 'A valid JSON request body is required.'], 400);
}

$action = $body['action'] ?? '';
if (!is_string($action)) {
    officerNotificationJson(['success' => false, 'message' => 'Action must be a string.'], 400);
}

try {
    $model = new NotificationModel();
    if ($action === 'mark_read') {
        $rawId = $body['id'] ?? null;
        if (!is_int($rawId) && !(is_string($rawId) && preg_match('/^[1-9]\d*$/', $rawId))) {
            officerNotificationJson(['success' => false, 'message' => 'A positive notification id is required.'], 400);
        }
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            officerNotificationJson(['success' => false, 'message' => 'A positive notification id is required.'], 400);
        }
        if (!$model->markRead($id, $userId)) {
            officerNotificationJson(['success' => false, 'message' => 'Notification not found.'], 404);
        }
        officerNotificationJson(['success' => true]);
    }

    if ($action === 'mark_all_read') {
        $model->markAllRead($userId);
        officerNotificationJson(['success' => true]);
    }

    officerNotificationJson(['success' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    error_log('Officer notification action failed: ' . $e->getMessage());
    officerNotificationJson(['success' => false, 'message' => 'Unable to update notifications.'], 500);
}
