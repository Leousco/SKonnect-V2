<?php
// backend/controllers/UpdateThreadStatusController.php
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

$db       = new Database();
$conn     = $db->getConnection();
$model    = new ThreadModel($conn);
$logModel = new ActivityLogModel($conn);

$mod_id = (int)($_SESSION['user_id'] ?? 0);

$thread_id  = (int)($_POST['thread_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');

$allowed_statuses = ['pending', 'responded', 'resolved'];

if (!$thread_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid thread ID.']);
    exit;
}
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value.']);
    exit;
}

$result = $model->updateThreadStatus($thread_id, $new_status);

if ($result) {
    echo json_encode([
        'status'     => 'success',
        'new_status' => $new_status,
        'message'    => 'Thread status updated.',
    ]);

    // ── EMAIL NOTIFICATION ────────────────────────────────────────────────────
    // Notify the thread author on every status change so they stay informed.
    $author = $model->getThreadAuthor($thread_id);
    if ($author && !empty($author['email'])) {
        $emailService = new EmailService();
        $emailService->sendStatusChangeNotification(
            email: $author['email'],
            name: $author['name'],
            threadSubject: $author['subject'],
            newStatus: $new_status
        );
    }
    // ─────────────────────────────────────────────────────────────────────────

    // ── ACTIVITY LOG ──────────────────────────────────────────────────────────
    $author ??= $model->getThreadAuthor($thread_id);
    $logModel->log($mod_id, 'thread_status_updated', [
        'target_type' => 'thread',
        'target_id'   => $thread_id,
        'target_name' => $author['subject'] ?? "(Thread #{$thread_id})",
        'target_user' => $author['name']    ?? '',
        'notes'       => "Status changed to \"{$new_status}\".",
    ]);
    // ─────────────────────────────────────────────────────────────────────────

} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
}