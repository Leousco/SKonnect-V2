<?php
// backend/controllers/NotificationController.php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

header('Content-Type: application/json');

require_once __DIR__ . '/../models/NotificationModel.php';

$model  = new NotificationModel();
$userId = (int) ($_SESSION['user_id'] ?? 0);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function notifJson(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if (!$userId) {
    notifJson(['status' => 'error', 'message' => 'Unauthorized.'], 401);
}

switch ($action) {

    // GET — return filtered notification list + stats for the current user
    case 'list':
        $filters = [];
        if (!empty($_GET['type']))   $filters['type']   = $_GET['type'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (isset($_GET['is_read']) && $_GET['is_read'] !== '') {
            $filters['is_read'] = (int) $_GET['is_read'];
        }

        notifJson([
            'status' => 'success',
            'data'   => $model->getByUser($userId, $filters),
            'stats'  => $model->getStats($userId),
        ]);

    // POST — mark a single notification as read
    case 'markRead':
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) notifJson(['status' => 'error', 'message' => 'Invalid ID.'], 400);
        $model->markRead($id, $userId);
        notifJson(['status' => 'success']);

    // POST — mark all notifications as read
    case 'markAllRead':
        $model->markAllRead($userId);
        notifJson(['status' => 'success']);

    // POST — dismiss (soft-hide) a notification
    case 'dismiss':
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) notifJson(['status' => 'error', 'message' => 'Invalid ID.'], 400);
        $model->dismiss($id, $userId);
        notifJson(['status' => 'success']);

    default:
        notifJson(['status' => 'error', 'message' => 'Unknown action.'], 400);
}