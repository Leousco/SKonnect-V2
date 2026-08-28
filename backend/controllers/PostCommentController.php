<?php
// backend/controllers/PostCommentController.php
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
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../services/EmailService.php';

$db          = new Database();
$conn        = $db->getConnection();
$model       = new CommentModel($conn);
$threadModel = new ThreadModel($conn);
$logModel    = new ActivityLogModel($conn);

$user_id   = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'resident';
$thread_id = (int)($_POST['thread_id'] ?? 0);
$message   = trim($_POST['message'] ?? '');

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}
if (!$thread_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid thread.']);
    exit;
}
if (strlen($message) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Comment is too short.']);
    exit;
}
if (!$model->threadExists($thread_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Thread not found.']);
    exit;
}

$is_mod  = ($user_role === 'moderator') ? 1 : 0;
$comment = $model->createComment($thread_id, (int)$user_id, $message, $is_mod);

// In-system notification
if ($comment) {
    require_once __DIR__ . '/../services/NotificationService.php';
    $threadAuthor = $threadModel->getThreadAuthor($thread_id);
    if ($threadAuthor) {
        NotificationService::notifyThreadComment(
            (int) $threadAuthor['author_id'],
            (int) $user_id,
            $_SESSION['user_name'] ?? 'Someone',
            $thread_id,
            $threadAuthor['subject'],
            $message,
            (bool) $is_mod
        );
    }
}

if ($is_mod && $comment) {
    $author = $threadAuthor ?? $threadModel->getThreadAuthor($thread_id);
    $authorIsCommenter = $author && (strtolower(trim($author['email'] ?? '')) === strtolower(trim($_SESSION['email'] ?? '')));
    $isPending = $author && strtolower($author['status'] ?? '') === 'pending';

    if ($isPending) {
        $threadModel->updateThreadStatus($thread_id, 'responded');
        $logModel->log((int)$user_id, 'thread_status_updated', [
            'target_type' => 'thread',
            'target_id'   => $thread_id,
            'target_name' => $author['subject'] ?? "(Thread #{$thread_id})",
            'target_user' => $author['name']    ?? '',
            'notes'       => 'Status auto-updated to "responded" on moderator comment.',
        ]);
    }

    if ($author && $author['email'] && !$authorIsCommenter) {
        $emailService = new EmailService();
        if ($isPending) {
            $emailService->sendModCommentRespondedNotification(
                email: $author['email'],
                name: $author['name'],
                threadSubject: $author['subject'],
                commentSnippet: $message
            );
        } else {
            $emailService->sendModCommentNotification(
                email: $author['email'],
                name: $author['name'],
                threadSubject: $author['subject'],
                commentSnippet: $message
            );
        }
    }

    $logModel->log((int)$user_id, 'mod_comment_posted', [
        'target_type' => 'thread',
        'target_id'   => $thread_id,
        'target_name' => $author['subject'] ?? "(Thread #{$thread_id})",
        'target_user' => $author['name']    ?? '',
        'notes'       => 'Moderator posted a comment on this thread.',
    ]);
}

echo json_encode(['status' => 'success', 'comment' => $comment]);