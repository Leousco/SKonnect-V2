<?php
// backend/controllers/ModQueueController.php
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
require_once __DIR__ . '/../services/EmailService.php';

$db          = new Database();
$conn        = $db->getConnection();
$threadModel = new ThreadModel($conn);
$reportModel = new ReportModel($conn);
$logModel    = new ActivityLogModel($conn);

$report_id = (int)($_POST['report_id'] ?? 0);
$action    = trim($_POST['action']    ?? '');
$mod_id    = (int)($_SESSION['user_id'] ?? 0);

if (!$report_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid report ID.']);
    exit;
}

$reports = $reportModel->getThreadReports();
$report  = null;
foreach ($reports as $r) {
    if ((int)$r['report_id'] === $report_id) { $report = $r; break; }
}

if (!$report) {
    echo json_encode(['status' => 'error', 'message' => 'Report not found.']);
    exit;
}

$thread_id        = (int)$report['thread_id'];
$thread_author_id = (int)$report['thread_author_id'];
$category         = $report['category'];
$thread_subject   = $report['thread_subject'];

function notifyAuthorEmail(ThreadModel $tm, int $thread_id, callable $send): void
{
    $author = $tm->getThreadAuthor($thread_id);
    if ($author && !empty($author['email'])) {
        $svc = new EmailService();
        $send($svc, $author);
    }
}

switch ($action) {

    case 'dismiss':
        $ok = $reportModel->updateThreadReportStatus($report_id, 'dismissed');
        if ($ok) {
            $logModel->log($mod_id, 'report_dismissed', [
                'target_type' => 'thread',
                'target_id'   => $thread_id,
                'target_name' => $thread_subject,
                'target_user' => $report['thread_author_name'] ?? '',
                'notes'       => "Report dismissed. Category: {$category}. No action taken.",
            ]);
        }
        echo json_encode([
            'status'        => $ok ? 'success' : 'error',
            'message'       => $ok ? 'Report dismissed.' : 'Failed to dismiss report.',
            'report_status' => 'dismissed',
        ]);
        break;

    case 'resolve':
        $hideOk   = $threadModel->setThreadRemoved($thread_id, 1);
        $reportOk = $reportModel->updateThreadReportStatus($report_id, 'reviewed');

        if ($hideOk) {
            $logModel->log($mod_id, 'report_resolved', [
                'target_type' => 'thread',
                'target_id'   => $thread_id,
                'target_name' => $thread_subject,
                'target_user' => $report['thread_author_name'] ?? '',
                'notes'       => "Report resolved. Category: {$category}. Thread hidden and author notified.",
            ]);

            notifyAuthorEmail($threadModel, $thread_id, function (EmailService $svc, array $author): void {
                $svc->sendRemovalStatusNotification(
                    email: $author['email'],
                    name: $author['name'],
                    threadSubject: $author['subject'],
                    isRemoved: true
                );
            });
        }

        echo json_encode([
            'status'        => ($hideOk && $reportOk) ? 'success' : 'error',
            'message'       => $hideOk
                ? 'Report resolved. Thread hidden and author notified.'
                : 'Failed to hide thread.',
            'report_status' => 'reviewed',
            'thread_hidden' => $hideOk,
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
        break;
}