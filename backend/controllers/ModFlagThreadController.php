<?php
// backend/controllers/ModFlagThreadController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThreadModel.php';
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

$db          = new Database();
$conn        = $db->getConnection();
$threadModel = new ThreadModel($conn);
$reportModel = new ReportModel($conn);
$logModel    = new ActivityLogModel($conn);

$thread_id = (int)($_POST['thread_id'] ?? 0);
$category  = trim($_POST['category']  ?? '');
$mod_id    = (int)($_SESSION['user_id'] ?? 0);

$allowed_categories = ['inappropriate', 'spam', 'misinformation', 'harassment'];

if (!$thread_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid thread ID.']);
    exit;
}
if (!in_array($category, $allowed_categories, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid category.']);
    exit;
}
if (!$mod_id) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please log in again.']);
    exit;
}

$flagged = $threadModel->setThreadFlag($thread_id, 1);

if (!$flagged) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to flag thread.']);
    exit;
}

if (!$reportModel->hasReportedThread($thread_id, $mod_id)) {
    $reportModel->createThreadReport(
        thread_id:   $thread_id,
        reporter_id: $mod_id,
        category:    $category,
        note:        null
    );
}

$author = $threadModel->getThreadAuthor($thread_id);
$logModel->log($mod_id, 'thread_flagged', [
    'target_type' => 'thread',
    'target_id'   => $thread_id,
    'target_name' => $author['subject'] ?? "(Thread #{$thread_id})",
    'target_user' => $author['name']    ?? '',
    'notes'       => "Flagged for: {$category}. Added to mod queue.",
]);

echo json_encode([
    'status'  => 'success',
    'message' => 'Thread flagged.',
    'flagged' => true,
]);