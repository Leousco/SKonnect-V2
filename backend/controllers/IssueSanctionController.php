<?php
// backend/controllers/IssueSanctionController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SanctionModel.php';
require_once __DIR__ . '/../models/CommentReportModel.php';
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../services/EmailService.php';

$db            = new Database();
$conn          = $db->getConnection();
$sanctionModel = new SanctionModel($conn);
$reportModel   = new CommentReportModel($conn);
$commentModel  = new CommentModel($conn);
$logModel      = new ActivityLogModel($conn);

$mod_id    = (int)($_SESSION['user_id'] ?? 0);
$user_id   = (int)($_POST['user_id']   ?? 0);
$level     = (int)($_POST['level']     ?? 0);
$reason    = trim($_POST['reason']     ?? '');
$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : null;

if (!$user_id || !$level) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields (user_id, level).']);
    exit;
}

if ($level < 1 || $level > 3) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid sanction level.']);
    exit;
}

$userStmt = $conn->prepare(
    "SELECT id, CONCAT(first_name, ' ', last_name) AS name, email FROM users WHERE id = :id LIMIT 1"
);
$userStmt->execute([':id' => $user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

$reportedContent = null;
$threadSubject   = null;

if ($report_id) {
    $reportRow = $reportModel->getById($report_id);
    if ($reportRow) {
        $targetType = $reportRow['target_type'];
        $targetId   = (int)$reportRow['target_id'];

        if ($targetType === 'comment') {
            $q = $conn->prepare("SELECT tc.message, t.subject FROM thread_comments tc JOIN threads t ON t.id = tc.thread_id WHERE tc.id = :id LIMIT 1");
        } else {
            $q = $conn->prepare(
                "SELECT cr.message, t.subject
                 FROM comment_replies cr
                 JOIN thread_comments tc ON tc.id = cr.comment_id
                 JOIN threads t          ON t.id  = tc.thread_id
                 WHERE cr.id = :id LIMIT 1"
            );
        }
        $q->execute([':id' => $targetId]);
        $row = $q->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $reportedContent = $row['message'];
            $threadSubject   = $row['subject'];
        }
    }
}

$sanction_id = $sanctionModel->issue(
    user_id:   $user_id,
    issued_by: $mod_id,
    level:     $level,
    reason:    $reason ?: '(No additional reason provided)',
    report_id: $report_id
);

$content_removed = false;
if ($level >= 2 && $report_id) {
    $reportRow = $reportModel->getById($report_id);
    if ($reportRow) {
        $targetType = $reportRow['target_type'];
        $targetId   = (int)$reportRow['target_id'];

        if ($targetType === 'comment') {
            $content_removed = $commentModel->removeCommentByMod($targetId);
        } elseif ($targetType === 'reply') {
            $content_removed = $commentModel->removeReplyByMod($targetId);
        }
    }
}

if ($report_id) {
    $reportModel->updateStatus($report_id, 'reviewed');
}

// ── Log the sanction action ───────────────────────────────────
$logActionMap = [1 => 'warning_issued', 2 => 'mute_issued', 3 => 'ban_issued'];
$levelLabels  = [1 => 'Warning',        2 => '7-Day Ban',   3 => 'Permanent Ban'];

$notesStr = $levelLabels[$level] . ' issued.';
if ($reason)       $notesStr .= " Reason: {$reason}.";
if ($threadSubject) $notesStr .= " Related thread: \"{$threadSubject}\".";
if ($content_removed) $notesStr .= ' Reported content also removed.';

$logModel->log($mod_id, $logActionMap[$level], [
    'target_type' => 'user',
    'target_id'   => $user_id,
    'target_name' => $user['name'],
    'target_user' => '',
    'notes'       => $notesStr,
]);
// ─────────────────────────────────────────────────────────────

$emailSvc  = new EmailService();
$emailSent = $emailSvc->sendSanctionNotification(
    email:           $user['email'],
    name:            $user['name'],
    level:           $level,
    reason:          $reason,
    reportedContent: $reportedContent,
    threadSubject:   $threadSubject
);

echo json_encode([
    'status'          => 'success',
    'message'         => "Sanction issued: Level {$level} ({$levelLabels[$level]}) to {$user['name']}.",
    'sanction_id'     => $sanction_id,
    'new_level'       => $level,
    'email_sent'      => $emailSent,
    'content_removed' => $content_removed,
]);