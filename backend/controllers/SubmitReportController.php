<?php
// backend/controllers/SubmitReportController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ReportModel.php';

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReportModel($conn);

$reporter_id = (int)($_SESSION['user_id'] ?? 0);
if (!$reporter_id) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

$allowed_categories = ['inappropriate', 'spam', 'misinformation', 'harassment'];
$allowed_types      = ['thread', 'comment', 'reply'];

$report_type = trim($_POST['report_type'] ?? '');
$target_id   = (int)($_POST['target_id']   ?? 0);
$category    = trim($_POST['category']     ?? '');
$note        = trim($_POST['note']         ?? '') ?: null;

// --- Validate ---
if (!in_array($report_type, $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid report type.']);
    exit;
}
if ($target_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid target.']);
    exit;
}
if (!in_array($category, $allowed_categories)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a report category.']);
    exit;
}

// --- Duplicate check & insert ---
if ($report_type === 'thread') {
    if ($model->hasReportedThread($target_id, $reporter_id)) {
        echo json_encode(['status' => 'error', 'message' => 'You have already reported this thread.']);
        exit;
    }
    $ok = $model->createThreadReport($target_id, $reporter_id, $category, $note);
} else {
    // 'comment' or 'reply'
    if ($model->hasReportedComment($report_type, $target_id, $reporter_id)) {
        echo json_encode(['status' => 'error', 'message' => 'You have already reported this ' . $report_type . '.']);
        exit;
    }
    $ok = $model->createCommentReport($report_type, $target_id, $reporter_id, $category, $note);
}

if ($ok) {
    echo json_encode(['status' => 'success', 'message' => 'Report submitted. Thank you.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Could not submit report. Please try again.']);
}