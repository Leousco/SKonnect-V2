<?php
// backend/controllers/CommentReportActionController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CommentReportModel.php';

$db          = new Database();
$conn        = $db->getConnection();
$reportModel = new CommentReportModel($conn);

$report_id = (int)($_POST['report_id'] ?? 0);
$action    = trim($_POST['action']     ?? '');

if (!$report_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid report ID.']);
    exit;
}

switch ($action) {
    case 'dismiss':
        $ok = $reportModel->updateStatus($report_id, 'dismissed');
        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Report dismissed.' : 'Failed to dismiss report.',
            'new_status' => 'dismissed',
        ]);
        break;

    case 'reviewed':
        $ok = $reportModel->updateStatus($report_id, 'reviewed');
        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Report marked as reviewed.' : 'Failed.',
            'new_status' => 'reviewed',
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
        break;
}