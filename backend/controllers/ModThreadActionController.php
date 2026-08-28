<?php
// backend/controllers/ModThreadActionController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThreadModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../services/EmailService.php';

$db        = new Database();
$conn      = $db->getConnection();
$model     = new ThreadModel($conn);
$logModel  = new ActivityLogModel($conn);

$thread_id = (int)($_POST['thread_id'] ?? 0);
$action    = trim($_POST['action'] ?? '');
$mod_id    = (int)($_SESSION['user_id'] ?? 0);

if (!$thread_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid thread ID.']);
    exit;
}

function notifyThreadAuthor(ThreadModel $model, int $thread_id, callable $send): void
{
    $author = $model->getThreadAuthor($thread_id);
    if ($author && !empty($author['email'])) {
        $emailService = new EmailService();
        $send($emailService, $author);
    }
}

function buildThreadMeta(ThreadModel $model, int $thread_id, string $notes): array
{
    $author = $model->getThreadAuthor($thread_id);
    return [
        'target_type' => 'thread',
        'target_id'   => $thread_id,
        'target_name' => $author['subject'] ?? "(Thread #{$thread_id})",
        'target_user' => $author['name']    ?? '',
        'notes'       => $notes,
    ];
}

switch ($action) {

    case 'flag':
        $result = $model->setThreadFlag($thread_id, 1);
        if ($result) {
            $logModel->log($mod_id, 'thread_flagged', buildThreadMeta($model, $thread_id, 'Thread flagged for review.'));
        }
        echo json_encode([
            'status'  => $result ? 'success' : 'error',
            'flagged' => true,
            'message' => $result ? 'Thread flagged.' : 'Failed to flag thread.',
        ]);
        break;

    case 'unflag':
        $result = $model->setThreadFlag($thread_id, 0);
        if ($result) {
            $logModel->log($mod_id, 'thread_unflagged', buildThreadMeta($model, $thread_id, 'Flag removed after review.'));
        }
        echo json_encode([
            'status'  => $result ? 'success' : 'error',
            'flagged' => false,
            'message' => $result ? 'Flag removed.' : 'Failed to remove flag.',
        ]);
        break;

    case 'remove':
        $result = $model->setThreadRemoved($thread_id, 1);
        if ($result) {
            $logModel->log($mod_id, 'thread_removed', buildThreadMeta($model, $thread_id, 'Thread hidden from residents.'));
            notifyThreadAuthor($model, $thread_id, function (EmailService $svc, array $author): void {
                $svc->sendRemovalStatusNotification(
                    email: $author['email'],
                    name: $author['name'],
                    threadSubject: $author['subject'],
                    isRemoved: true
                );
            });
        }
        echo json_encode([
            'status'  => $result ? 'success' : 'error',
            'message' => $result ? 'Thread hidden from residents.' : 'Failed to remove thread.',
        ]);
        break;

    case 'restore':
        $result = $model->setThreadRemoved($thread_id, 0);
        if ($result) {
            $logModel->log($mod_id, 'thread_restored', buildThreadMeta($model, $thread_id, 'Thread restored to feed.'));
            notifyThreadAuthor($model, $thread_id, function (EmailService $svc, array $author): void {
                $svc->sendRemovalStatusNotification(
                    email: $author['email'],
                    name: $author['name'],
                    threadSubject: $author['subject'],
                    isRemoved: false
                );
            });
        }
        echo json_encode([
            'status'  => $result ? 'success' : 'error',
            'message' => $result ? 'Thread restored.' : 'Failed to restore thread.',
        ]);
        break;

    case 'pin':
        $result = $model->setThreadPinned($thread_id, 1);
        if ($result) {
            $logModel->log($mod_id, 'thread_pinned', buildThreadMeta($model, $thread_id, 'Thread pinned to top of feed.'));
            notifyThreadAuthor($model, $thread_id, function (EmailService $svc, array $author): void {
                $svc->sendPinStatusNotification(
                    email: $author['email'],
                    name: $author['name'],
                    threadSubject: $author['subject'],
                    isPinned: true
                );
            });
        }
        echo json_encode([
            'status'  => $result ? 'success' : 'error',
            'pinned'  => true,
            'message' => $result ? 'Thread pinned.' : 'Failed to pin thread.',
        ]);
        break;

    case 'unpin':
        $result = $model->setThreadPinned($thread_id, 0);
        if ($result) {
            $logModel->log($mod_id, 'thread_unpinned', buildThreadMeta($model, $thread_id, 'Thread unpinned.'));
            notifyThreadAuthor($model, $thread_id, function (EmailService $svc, array $author): void {
                $svc->sendPinStatusNotification(
                    email: $author['email'],
                    name: $author['name'],
                    threadSubject: $author['subject'],
                    isPinned: false
                );
            });
        }
        echo json_encode([
            'status'  => $result ? 'success' : 'error',
            'pinned'  => false,
            'message' => $result ? 'Thread unpinned.' : 'Failed to unpin thread.',
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
        break;
}