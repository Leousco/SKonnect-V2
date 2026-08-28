<?php
// backend/controllers/DeleteContentController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/ThreadModel.php';

$db      = new Database();
$conn    = $db->getConnection();
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$type      = trim($_POST['type']      ?? '');   // 'thread' | 'comment' | 'reply'
$target_id = (int)($_POST['target_id'] ?? 0);

if (!$target_id || !in_array($type, ['thread', 'comment', 'reply'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

switch ($type) {

    // ── THREAD ──────────────────────────────────────────────────────────────
    case 'thread': {
        $model = new ThreadModel($conn);

        // Ownership check
        $stmt = $conn->prepare(
            "SELECT author_id FROM threads WHERE id = :id AND is_removed = 0 LIMIT 1"
        );
        $stmt->execute([':id' => $target_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Thread not found.']);
            exit;
        }
        if ((int)$row['author_id'] !== $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'You can only delete your own threads.']);
            exit;
        }

        // Soft-delete: mark is_removed + removed_by_user
        $stmt = $conn->prepare(
            "UPDATE threads
             SET is_removed = 1, removed_by_user = 1
             WHERE id = :id AND author_id = :uid AND is_removed = 0"
        );
        $ok = $stmt->execute([':id' => $target_id, ':uid' => $user_id]);

        echo json_encode([
            'status'  => $ok && $stmt->rowCount() > 0 ? 'success' : 'error',
            'message' => $ok && $stmt->rowCount() > 0
                ? 'Thread deleted.'
                : 'Could not delete thread.',
        ]);
        break;
    }

    // ── COMMENT ─────────────────────────────────────────────────────────────
    case 'comment': {
        $model = new CommentModel($conn);

        // Ownership check
        $stmt = $conn->prepare(
            "SELECT author_id FROM thread_comments
             WHERE id = :id AND is_removed = 0 LIMIT 1"
        );
        $stmt->execute([':id' => $target_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Comment not found.']);
            exit;
        }
        if ((int)$row['author_id'] !== $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'You can only delete your own comments.']);
            exit;
        }

        $ok = $model->removeCommentByUser($target_id, $user_id);

        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Comment deleted.' : 'Could not delete comment.',
        ]);
        break;
    }

    // ── REPLY ────────────────────────────────────────────────────────────────
    case 'reply': {
        $model = new CommentModel($conn);

        // Ownership check
        $stmt = $conn->prepare(
            "SELECT author_id FROM comment_replies
             WHERE id = :id AND is_removed = 0 LIMIT 1"
        );
        $stmt->execute([':id' => $target_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Reply not found.']);
            exit;
        }
        if ((int)$row['author_id'] !== $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'You can only delete your own replies.']);
            exit;
        }

        $ok = $model->removeReplyByUser($target_id, $user_id);

        echo json_encode([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Reply deleted.' : 'Could not delete reply.',
        ]);
        break;
    }
}