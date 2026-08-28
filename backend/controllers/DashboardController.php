<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/DashboardModel.php';

RoleMiddleware::requireRole('resident');
header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$model  = new DashboardModel();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        echo json_encode(['status' => 'success', 'data' => $model->getWidgetStats($userId)]);
        break;

    case 'activity':
        echo json_encode(['status' => 'success', 'data' => $model->getRecentActivity($userId)]);
        break;

    case 'announcements':
        echo json_encode(['status' => 'success', 'data' => $model->getLatestAnnouncements()]);
        break;

    case 'events':
        echo json_encode(['status' => 'success', 'data' => $model->getUpcomingEvents()]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
}