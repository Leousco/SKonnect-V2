<?php
// backend/controllers/ToggleBookmarkController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThreadBookmarkModel.php';

$db    = new Database();
$conn  = $db->getConnection();
$model = new BookmarkModel($conn);

$user_id   = $_SESSION['user_id'] ?? null;
$thread_id = (int)($_POST['thread_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}
if (!$thread_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid thread.']);
    exit;
}

$bookmarked = $model->toggle($thread_id, (int)$user_id);

echo json_encode(['status' => 'success', 'bookmarked' => $bookmarked]);