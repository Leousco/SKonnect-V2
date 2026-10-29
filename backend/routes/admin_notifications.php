<?php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../services/AdminNotificationService.php';

header('Content-Type: application/json; charset=utf-8');

function adminNotificationJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$currentRole = RoleMiddleware::currentRole();
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId < 1) {
    adminNotificationJson(['success' => false, 'message' => 'Authentication is required.'], 401);
}
if ($currentRole !== 'admin') {
    adminNotificationJson(['success' => false, 'message' => 'This endpoint is for administrators only.'], 403);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
try {
    $model = new NotificationModel();
    if ($method === 'GET') {
        if (($_GET['action'] ?? '') !== 'list') {
            adminNotificationJson(['success' => false, 'message' => 'Unknown action.'], 400);
        }

        $limit = filter_var($_GET['limit'] ?? 6, FILTER_VALIDATE_INT);
        $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 100 || $offset === false || $offset < 0) {
            adminNotificationJson(['success' => false, 'message' => 'Invalid notification page.'], 400);
        }
        $category = $_GET['category'] ?? '';
        if (!is_string($category) || ($category !== '' && !in_array(
            $category,
            ['announcements', 'services', 'requests', 'reports', 'users'],
            true
        ))) {
            adminNotificationJson(['success' => false, 'message' => 'Invalid notification category.'], 400);
        }
        $isRead = $_GET['is_read'] ?? '';
        if ((!is_string($isRead) && !is_int($isRead)) ||
            ($isRead !== '' && !in_array((string)$isRead, ['0', '1'], true))) {
            adminNotificationJson(['success' => false, 'message' => 'is_read must be 0 or 1.'], 400);
        }
        $search = $_GET['search'] ?? '';
        if (!is_string($search) || strlen($search) > 200) {
            adminNotificationJson(['success' => false, 'message' => 'Search must be at most 200 characters.'], 400);
        }

        $now = time();
        if ($now - (int)($_SESSION['admin_notification_generation_at'] ?? 0) >= 30) {
            (new AdminNotificationService())->generateForAdmins();
            $_SESSION['admin_notification_generation_at'] = $now;
        }

        $result = $model->getAdminNotifications($userId, [
            'limit' => $limit,
            'offset' => $offset,
            'category' => $category,
            'is_read' => $isRead,
            'search' => trim($search),
        ]);
        adminNotificationJson([
            'success' => true,
            'notifications' => $result['notifications'],
            'unreadCount' => $result['unreadCount'],
            'total' => $result['total'],
        ]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        adminNotificationJson(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
        adminNotificationJson(['success' => false, 'message' => 'A valid JSON request body is required.'], 400);
    }
    $action = $body['action'] ?? '';
    if ($action === 'mark_read') {
        $id = filter_var($body['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            adminNotificationJson(['success' => false, 'message' => 'A positive notification id is required.'], 400);
        }
        if (!$model->markAdminNotificationRead((int)$id, $userId)) {
            adminNotificationJson(['success' => false, 'message' => 'Notification not found.'], 404);
        }
        adminNotificationJson(['success' => true]);
    }
    if ($action === 'mark_all_read') {
        $model->markAllAdminNotificationsRead($userId);
        adminNotificationJson(['success' => true]);
    }
    adminNotificationJson(['success' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    error_log('Admin notifications request failed: ' . $e->getMessage());
    adminNotificationJson(['success' => false, 'message' => 'Unable to load or update notifications.'], 500);
}
