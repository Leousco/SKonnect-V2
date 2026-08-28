<?php
// backend/controllers/ModGetThreadController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThreadModel.php';
require_once __DIR__ . '/../models/CommentModel.php';

$db    = new Database();
$conn  = $db->getConnection();

$threadModel  = new ThreadModel($conn);
$commentModel = new CommentModel($conn);

$thread_id = (int)($_GET['id'] ?? 0);

if (!$thread_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid thread ID.']);
    exit;
}

// Pass 0 as user_id — moderator doesn't need personal bookmark/support state
$thread = $threadModel->getThreadById($thread_id, 0);

if (!$thread) {
    echo json_encode(['status' => 'error', 'message' => 'Thread not found.']);
    exit;
}

$images   = $threadModel->getThreadImages($thread_id);
$comments = $commentModel->getCommentsByThread($thread_id, 0);

echo json_encode([
    'status'   => 'success',
    'thread'   => $thread,
    'images'   => $images,
    'comments' => $comments,
]);