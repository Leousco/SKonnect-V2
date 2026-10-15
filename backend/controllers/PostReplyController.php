<?php
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

$db    = new Database();
$conn  = $db->getConnection();
$model = new CommentModel($conn);
$threadModel = new ThreadModel($conn);
$logModel = new ActivityLogModel($conn);

$user_id    = $_SESSION['user_id'] ?? null;
$user_role  = $_SESSION['user_role'] ?? 'resident';
$comment_id = (int)($_POST['comment_id'] ?? 0);
$message    = trim($_POST['message'] ?? '');

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}
if (!$comment_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid comment.']);
    exit;
}
if (strlen($message) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Reply is too short.']);
    exit;
}

$is_mod = in_array($user_role, ['moderator', 'admin'], true) ? 1 : 0;
$reply  = $model->createReply($comment_id, (int)$user_id, $message, $is_mod);

if ($reply) {
    require_once __DIR__ . '/../services/NotificationService.php';
    $commentContext = $threadModel->getCommentAuthorAndThread($comment_id);
    if ($commentContext) {
        NotificationService::notifyCommentReply(
            (int) $commentContext['comment_author_id'],
            (int) $user_id,
            $_SESSION['user_name'] ?? 'Someone',
            (int) $commentContext['thread_id'],
            $commentContext['thread_subject'],
            $message,
            (bool) $is_mod
        );
    }
}

if (!$reply) {
    echo json_encode(['status' => 'error', 'message' => 'Comment not found or has been removed.']);
    exit;
}

$threadStatus = null;
$threadStatusUpdateFailed = false;

if ($is_mod) {
    $author      = $threadModel->getThreadAuthorByComment($comment_id);
    if ($user_role === 'admin' && $author) {
        require_once __DIR__ . '/../services/ModeratorNotificationService.php';
        (new ModeratorNotificationService($conn))->notifyAdminComment(
            (int)$author['thread_id'],
            $author['subject'],
            true
        );
    }

    $threadStatus = $author['status'] ?? null;
    if ($author && strtolower($author['status'] ?? '') === 'pending') {
        if ($threadModel->updateThreadStatus((int)$author['thread_id'], 'responded')) {
            $threadStatus = 'responded';
            $logModel->log((int)$user_id, 'thread_status_updated', [
                'target_type' => 'thread',
                'target_id'   => (int)$author['thread_id'],
                'target_name' => $author['subject'] ?? "(Thread #{$author['thread_id']})",
                'target_user' => $author['name']    ?? '',
                'notes'       => 'Status auto-updated to "responded" on moderator reply.',
            ]);
            if ($user_role === 'admin') {
                require_once __DIR__ . '/../services/ModeratorNotificationService.php';
                (new ModeratorNotificationService($conn))->notifyAdminThreadAction(
                    'status',
                    (int)$author['thread_id'],
                    $author['subject'] ?? "(Thread #{$author['thread_id']})",
                    'responded'
                );
            }
        } else {
            $threadStatusUpdateFailed = true;
        }
    }

    if ($author && $author['email']) {
        $authorIsReplier = (strtolower(trim($author['email'])) === strtolower(trim($_SESSION['email'] ?? '')));

        if (!$authorIsReplier) {
            $emailService = new EmailService();
            $emailService->sendModReplyNotification(
                email: $author['email'],
                name: $author['name'],
                threadSubject: $author['subject'],
                replySnippet: $message
            );
        }
    }
}


echo json_encode([
    'status' => 'success',
    'reply' => $reply,
    'thread_status' => $threadStatus,
    'thread_status_update_failed' => $threadStatusUpdateFailed,
]);