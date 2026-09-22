<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SanctionModel.php';
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/ThreadModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../services/EmailService.php';

RoleMiddleware::requireAdmin();

header('Content-Type: application/json');

$db       = (new Database())->getConnection();
$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$admin_id = (int)($_SESSION['user_id'] ?? 0);

function jsonSuccess($data = [], string $message = 'OK'): void {
    echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if ($method === 'GET' && $action === 'list') {

    $type   = $_GET['type']   ?? 'all';
    $reason = $_GET['reason'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $search = trim($_GET['search'] ?? '');

    $parts  = [];
    $params = [];

    if ($type === 'all' || $type === 'thread') {
        $tSql = "
            SELECT
                tr.id,
                'thread'                                        AS type,
                tr.thread_id                                    AS target_id,
                tr.thread_id                                    AS thread_id,
                t.subject                                       AS content,
                LEFT(t.message, 120)                            AS excerpt,
                CONCAT(ru.first_name, ' ', ru.last_name)        AS reported_by,
                CONCAT(au.first_name, ' ', au.last_name)        AS author,
                tr.category                                     AS reason,
                tr.note                                         AS details,
                tr.status,
                tr.created_at                                   AS date,
                t.author_id                                     AS author_id,
                ru.id                                            AS reporter_id,
                t.is_removed                                    AS content_removed,
                COALESCE((
                    SELECT MAX(us.level) FROM user_sanctions us
                    WHERE us.user_id = t.author_id AND us.is_active = TRUE
                ), 0)                                            AS author_sanction_level
            FROM thread_reports tr
            JOIN threads t  ON t.id  = tr.thread_id
            JOIN users   ru ON ru.id = tr.reporter_id
            JOIN users   au ON au.id = t.author_id
            WHERE 1=1
        ";

        if ($reason !== 'all') {
            $tSql .= ' AND tr.category = :reason_t';
            $params[':reason_t'] = $reason;
        }
        if ($status !== 'all') {
            $tSql .= ' AND tr.status = :status_t';
            $params[':status_t'] = $status;
        }
        if ($search !== '') {
            $tSql .= " AND (t.subject ILIKE :search_t OR CONCAT(ru.first_name,' ',ru.last_name) ILIKE :search_t2)";
            $params[':search_t']  = '%' . $search . '%';
            $params[':search_t2'] = '%' . $search . '%';
        }

        $parts[] = $tSql;
    }

    if ($type === 'all' || $type === 'comment' || $type === 'reply') {
        $cSql = "
            SELECT
                cr.id,
                cr.target_type                                  AS type,
                cr.target_id,
                COALESCE(tc.thread_id, crep_tc.thread_id)       AS thread_id,
                COALESCE(th1.subject, th2.subject)              AS content,
                LEFT(COALESCE(tc.message, cr2.message, ''), 120) AS excerpt,
                CONCAT(ru.first_name, ' ', ru.last_name)        AS reported_by,
                CONCAT(au.first_name, ' ', au.last_name)        AS author,
                cr.category                                     AS reason,
                cr.note                                         AS details,
                cr.status,
                cr.created_at                                   AS date,
                COALESCE(tc.author_id, cr2.author_id)           AS author_id,
                ru.id                                            AS reporter_id,
                COALESCE(tc.is_removed, cr2.is_removed, FALSE)  AS content_removed,
                COALESCE((
                    SELECT MAX(us.level) FROM user_sanctions us
                    WHERE us.user_id = COALESCE(tc.author_id, cr2.author_id) AND us.is_active = TRUE
                ), 0)                                            AS author_sanction_level
            FROM comment_reports cr
            JOIN users ru ON ru.id = cr.reporter_id
            LEFT JOIN thread_comments tc
                ON cr.target_type = 'comment' AND tc.id = cr.target_id
            LEFT JOIN threads th1
                ON th1.id = tc.thread_id
            LEFT JOIN comment_replies cr2
                ON cr.target_type = 'reply' AND cr2.id = cr.target_id
            LEFT JOIN thread_comments crep_tc
                ON cr.target_type = 'reply' AND crep_tc.id = cr2.comment_id
            LEFT JOIN threads th2
                ON th2.id = crep_tc.thread_id
            LEFT JOIN users au
                ON au.id = COALESCE(tc.author_id, cr2.author_id)
            WHERE 1=1
        ";

        if ($reason !== 'all') {
            $cSql .= ' AND cr.category = :reason_c';
            $params[':reason_c'] = $reason;
        }
        if ($status !== 'all') {
            $cSql .= ' AND cr.status = :status_c';
            $params[':status_c'] = $status;
        }
        if ($search !== '') {
            $cSql .= " AND (CONCAT(ru.first_name,' ',ru.last_name) ILIKE :search_c OR COALESCE(th1.subject, th2.subject) ILIKE :search_c2)";
            $params[':search_c']  = '%' . $search . '%';
            $params[':search_c2'] = '%' . $search . '%';
        }

        if ($type === 'comment') {
            $cSql .= " AND cr.target_type = 'comment'";
        } elseif ($type === 'reply') {
            $cSql .= " AND cr.target_type = 'reply'";
        }

        $parts[] = $cSql;
    }

    if (empty($parts)) jsonSuccess([]);

    $sql  = implode(' UNION ALL ', $parts);
    $sql .= ' ORDER BY date DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($method === 'POST' && $action === 'ignore') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id']   ?? 0);
    $type = $body['type'] ?? '';

    if ($id === 0) jsonError('Invalid report ID.');

    $logModel = new ActivityLogModel($db);

    if ($type === 'thread') {
        $db->prepare("UPDATE thread_reports SET status = 'dismissed' WHERE id = :id")
           ->execute([':id' => $id]);

        $t = $db->prepare("SELECT thread_id FROM thread_reports WHERE id = :id");
        $t->execute([':id' => $id]);
        $threadId = (int)$t->fetchColumn();

        $logModel->log($admin_id, 'report_dismissed', [
            'target_type' => 'thread',
            'target_id'   => $threadId,
            'notes'       => 'Report dismissed. No action taken.',
        ]);
    } else {
        $db->prepare("UPDATE comment_reports SET status = 'dismissed' WHERE id = :id")
           ->execute([':id' => $id]);

        $logModel->log($admin_id, 'report_dismissed', [
            'target_type' => $type ?: 'comment',
            'target_id'   => $id,
            'notes'       => 'Report dismissed. No action taken.',
        ]);
    }

    jsonSuccess([], 'Report dismissed.');
}

if ($method === 'POST' && $action === 'delete_content') {

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id        = (int)($body['id']        ?? 0);
    $type      = $body['type']      ?? '';
    $target_id = (int)($body['target_id'] ?? 0);

    if ($id === 0 || $target_id === 0) jsonError('Missing required fields.');

    $threadModel  = new ThreadModel($db);
    $commentModel = new CommentModel($db);
    $logModel     = new ActivityLogModel($db);

    if ($type === 'thread') {
        $author = $threadModel->getThreadAuthor($target_id);
        $ok     = $threadModel->setThreadRemoved($target_id, 1);
        $db->prepare("UPDATE thread_reports SET status = 'reviewed' WHERE id = :id")
           ->execute([':id' => $id]);

        if ($ok) {
            $logModel->log($admin_id, 'thread_removed', [
                'target_type' => 'thread',
                'target_id'   => $target_id,
                'target_name' => $author['subject'] ?? "(Thread #{$target_id})",
                'target_user' => $author['name'] ?? '',
                'notes'       => 'Thread hidden from residents following a report.',
            ]);

            if ($author && !empty($author['email'])) {
                (new EmailService())->sendRemovalStatusNotification(
                    email: $author['email'],
                    name: $author['name'],
                    threadSubject: $author['subject'],
                    isRemoved: true
                );
            }
        }
    } elseif ($type === 'comment') {
        $ok = $commentModel->removeCommentByMod($target_id);
        $db->prepare("UPDATE comment_reports SET status = 'reviewed' WHERE id = :id")
           ->execute([':id' => $id]);

        if ($ok) {
            $logModel->log($admin_id, 'comment_removed', [
                'target_type' => 'comment',
                'target_id'   => $target_id,
                'notes'       => 'Comment removed following a report.',
            ]);
        }
    } elseif ($type === 'reply') {
        $ok = $commentModel->removeReplyByMod($target_id);
        $db->prepare("UPDATE comment_reports SET status = 'reviewed' WHERE id = :id")
           ->execute([':id' => $id]);

        if ($ok) {
            $logModel->log($admin_id, 'comment_removed', [
                'target_type' => 'reply',
                'target_id'   => $target_id,
                'notes'       => 'Reply removed following a report.',
            ]);
        }
    } else {
        jsonError('Unknown content type.');
    }

    jsonSuccess([], 'Content removed.');
}

if ($method === 'POST' && $action === 'sanction') {

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id        = (int)($body['id']        ?? 0);
    $type      = $body['type']      ?? '';
    $target_id = (int)($body['target_id'] ?? 0);
    $author_id = (int)($body['author_id'] ?? 0);
    $level     = (int)($body['level']     ?? 0);
    $reason    = trim($body['reason']     ?? '');

    if ($id === 0 || $author_id === 0 || $target_id === 0) jsonError('Missing required fields.');
    if ($level < 1 || $level > 3) jsonError('Invalid sanction level.');

    $userStmt = $db->prepare(
        "SELECT id, CONCAT(first_name, ' ', last_name) AS name, email FROM users WHERE id = :id LIMIT 1"
    );
    $userStmt->execute([':id' => $author_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) jsonError('User not found.');

    $threadModel   = new ThreadModel($db);
    $commentModel  = new CommentModel($db);
    $sanctionModel = new SanctionModel($db);
    $logModel      = new ActivityLogModel($db);

    $reportedContent = null;
    $threadSubject   = null;
    $threadAuthor    = null;

    if ($type === 'thread') {
        $threadAuthor  = $threadModel->getThreadAuthor($target_id);
        $threadSubject = $threadAuthor['subject'] ?? null;

        $msgStmt = $db->prepare("SELECT message FROM threads WHERE id = :id LIMIT 1");
        $msgStmt->execute([':id' => $target_id]);
        $reportedContent = $msgStmt->fetchColumn() ?: null;
    } else {
        if ($type === 'comment') {
            $q = $db->prepare(
                "SELECT tc.message, t.subject FROM thread_comments tc
                 JOIN threads t ON t.id = tc.thread_id
                 WHERE tc.id = :id LIMIT 1"
            );
        } else {
            $q = $db->prepare(
                "SELECT cr.message, t.subject
                 FROM comment_replies cr
                 JOIN thread_comments tc ON tc.id = cr.comment_id
                 JOIN threads t          ON t.id  = tc.thread_id
                 WHERE cr.id = :id LIMIT 1"
            );
        }
        $q->execute([':id' => $target_id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $reportedContent = $row['message'];
            $threadSubject   = $row['subject'];
        }
    }

    $sanction_id = $sanctionModel->issue(
        user_id:   $author_id,
        issued_by: $admin_id,
        level:     $level,
        reason:    $reason ?: '(No additional reason provided)',
        report_id: $id
    );

    $content_removed = false;
    if ($level >= 2) {
        if ($type === 'thread') {
            $content_removed = $threadModel->setThreadRemoved($target_id, 1);
            if ($content_removed && $threadAuthor && !empty($threadAuthor['email'])) {
                (new EmailService())->sendRemovalStatusNotification(
                    email: $threadAuthor['email'],
                    name: $threadAuthor['name'],
                    threadSubject: $threadAuthor['subject'],
                    isRemoved: true
                );
            }
        } elseif ($type === 'comment') {
            $content_removed = $commentModel->removeCommentByMod($target_id);
        } elseif ($type === 'reply') {
            $content_removed = $commentModel->removeReplyByMod($target_id);
        }
    }

    if ($type === 'thread') {
        $db->prepare("UPDATE thread_reports SET status = 'reviewed' WHERE id = :id")
           ->execute([':id' => $id]);
    } else {
        $db->prepare("UPDATE comment_reports SET status = 'reviewed' WHERE id = :id")
           ->execute([':id' => $id]);
    }

    $logActionMap = [1 => 'warning_issued', 2 => 'mute_issued', 3 => 'ban_issued'];
    $levelLabels  = [1 => 'Warning',        2 => '7-Day Ban',   3 => 'Permanent Ban'];

    $notesStr = $levelLabels[$level] . ' issued.';
    if ($reason)         $notesStr .= " Reason: {$reason}.";
    if ($threadSubject)  $notesStr .= " Related thread: \"{$threadSubject}\".";
    if ($content_removed) $notesStr .= ' Reported content also removed.';

    $logModel->log($admin_id, $logActionMap[$level], [
        'target_type' => 'user',
        'target_id'   => $author_id,
        'target_name' => $user['name'],
        'target_user' => '',
        'notes'       => $notesStr,
    ]);

    $emailSent = (new EmailService())->sendSanctionNotification(
        email:           $user['email'],
        name:            $user['name'],
        level:           $level,
        reason:          $reason,
        reportedContent: $reportedContent,
        threadSubject:   $threadSubject
    );

    jsonSuccess([
        'sanction_id'     => $sanction_id,
        'new_level'       => $level,
        'email_sent'      => $emailSent,
        'content_removed' => $content_removed,
    ], "Sanction issued: Level {$level} ({$levelLabels[$level]}) to {$user['name']}.");
}

jsonError('Invalid action or method.', 405);