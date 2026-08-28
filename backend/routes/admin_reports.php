<?php
// backend/routes/admin_reports.php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

header('Content-Type: application/json');

$db     = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/* ── helpers ────────────────────────────────────────────── */

function jsonSuccess($data = [], string $message = 'OK'): void {
    echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

/* ── GET ?action=list ───────────────────────────────────── */

if ($method === 'GET' && $action === 'list') {

    $type   = $_GET['type']   ?? 'all';
    $reason = $_GET['reason'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $search = trim($_GET['search'] ?? '');

    /*
     * Unify thread_reports + comment_reports into one result set.
     *
     * thread_reports  → category maps to reason
     * comment_reports → target_type is 'comment' or 'reply'
     *                   category maps to reason
     *
     * Columns returned:
     *   id, type (thread|comment|reply), target_id,
     *   content (subject/message excerpt), excerpt,
     *   reported_by, author, reason, date, status
     */

    $parts  = [];
    $params = [];

    /* ---- thread_reports ---- */
    if ($type === 'all' || $type === 'thread') {
        $tSql = "
            SELECT
                tr.id,
                'thread'                                        AS type,
                tr.thread_id                                    AS target_id,
                t.subject                                       AS content,
                LEFT(t.message, 120)                           AS excerpt,
                CONCAT(ru.first_name, ' ', ru.last_name)       AS reported_by,
                CONCAT(au.first_name, ' ', au.last_name)       AS author,
                tr.category                                     AS reason,
                tr.note                                        AS details,
                tr.status,
                tr.created_at                                   AS date,
                t.author_id                                     AS author_id,
                ru.id                                           AS reporter_id
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
            $tSql .= " AND (t.subject LIKE :search_t
                        OR CONCAT(ru.first_name,' ',ru.last_name) LIKE :search_t2)";
            $params[':search_t']  = '%' . $search . '%';
            $params[':search_t2'] = '%' . $search . '%';
        }

        $parts[] = $tSql;
    }

    /* ---- comment_reports ---- */
    if ($type === 'all' || $type === 'comment' || $type === 'reply') {
        $cSql = "
            SELECT
                cr.id,
                cr.target_type                                  AS type,
                cr.target_id,
                CONCAT('Comment on thread #', COALESCE(tc.thread_id, cr.target_id)) AS content,
                LEFT(COALESCE(tc.message, cr2.message, ''), 120) AS excerpt,
                CONCAT(ru.first_name, ' ', ru.last_name)        AS reported_by,
                CONCAT(au.first_name, ' ', au.last_name)        AS author,
                cr.category                                     AS reason,
                cr.note                                        AS details,
                cr.status,
                cr.created_at                                   AS date,
                COALESCE(tc.author_id, cr2.author_id)           AS author_id,
                ru.id                                           AS reporter_id
            FROM comment_reports cr
            JOIN users ru ON ru.id = cr.reporter_id
            LEFT JOIN thread_comments tc
                ON cr.target_type = 'comment' AND tc.id = cr.target_id
            LEFT JOIN comment_replies cr2
                ON cr.target_type = 'reply'   AND cr2.id = cr.target_id
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
            $cSql .= " AND (CONCAT(ru.first_name,' ',ru.last_name) LIKE :search_c)";
            $params[':search_c'] = '%' . $search . '%';
        }

        // Filter by type when explicitly selected
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
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess($reports);
}

/* ── POST ?action=ignore ─────────────────────────────────── */

if ($method === 'POST' && $action === 'ignore') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id']   ?? 0);
    $type = $body['type'] ?? '';

    if ($id === 0) jsonError('Invalid report ID.');

    if ($type === 'thread') {
        $db->prepare('UPDATE thread_reports  SET status = "dismissed" WHERE id = :id')
           ->execute([':id' => $id]);
    } else {
        $db->prepare('UPDATE comment_reports SET status = "dismissed" WHERE id = :id')
           ->execute([':id' => $id]);
    }

    jsonSuccess([], 'Report dismissed.');
}

/* ── POST ?action=warn ───────────────────────────────────── */
/*
 * Issues a level-1 sanction (warning) to the content author.
 * Marks the report as reviewed.
 */

if ($method === 'POST' && $action === 'warn') {

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id        = (int) ($body['id']        ?? 0);
    $type      = $body['type']      ?? '';
    $author_id = (int) ($body['author_id'] ?? 0);
    $note      = trim($body['note'] ?? 'Warning issued by admin.');

    if ($id === 0 || $author_id === 0) jsonError('Missing required fields.');

    // Record warning in user_sanctions (level 1 = warning)
    $db->prepare("
        INSERT INTO user_sanctions (user_id, issued_by, level, reason)
        VALUES (:user_id, :issued_by, 1, :reason)
    ")->execute([
        ':user_id'   => $author_id,
        ':issued_by' => $_SESSION['user_id'] ?? 0,
        ':reason'    => $note,
    ]);

    // Mark report reviewed
    if ($type === 'thread') {
        $db->prepare('UPDATE thread_reports  SET status = "reviewed" WHERE id = :id')
           ->execute([':id' => $id]);
    } else {
        $db->prepare('UPDATE comment_reports SET status = "reviewed" WHERE id = :id')
           ->execute([':id' => $id]);
    }

    jsonSuccess([], 'Warning issued to user.');
}

/* ── POST ?action=delete_content ─────────────────────────── */

if ($method === 'POST' && $action === 'delete_content') {

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id        = (int) ($body['id']        ?? 0);
    $type      = $body['type']      ?? '';
    $target_id = (int) ($body['target_id'] ?? 0);

    if ($id === 0 || $target_id === 0) jsonError('Missing required fields.');

    // Soft-delete the actual content
    if ($type === 'thread') {
        $db->prepare('UPDATE threads         SET is_removed = 1 WHERE id = :id')
           ->execute([':id' => $target_id]);
        $db->prepare('UPDATE thread_reports  SET status = "reviewed" WHERE id = :id')
           ->execute([':id' => $id]);
    } elseif ($type === 'comment') {
        $db->prepare('UPDATE thread_comments SET is_removed = 1 WHERE id = :id')
           ->execute([':id' => $target_id]);
        $db->prepare('UPDATE comment_reports SET status = "reviewed" WHERE id = :id')
           ->execute([':id' => $id]);
    } elseif ($type === 'reply') {
        $db->prepare('UPDATE comment_replies SET is_removed = 1 WHERE id = :id')
           ->execute([':id' => $target_id]);
        $db->prepare('UPDATE comment_reports SET status = "reviewed" WHERE id = :id')
           ->execute([':id' => $id]);
    } else {
        jsonError('Unknown content type.');
    }

    jsonSuccess([], 'Content deleted.');
}

/* ── POST ?action=ban ────────────────────────────────────── */
/*
 * Bans the user account (is_banned = 1) and marks report reviewed.
 */

if ($method === 'POST' && $action === 'ban') {

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id        = (int) ($body['id']        ?? 0);
    $type      = $body['type']      ?? '';
    $author_id = (int) ($body['author_id'] ?? 0);
    $note      = trim($body['note'] ?? 'Banned by admin.');

    if ($id === 0 || $author_id === 0) jsonError('Missing required fields.');

    // Ban the user
    $db->prepare("
        UPDATE user_status
        SET is_banned = 1, banned_reason = :reason
        WHERE user_id = :user_id
    ")->execute([
        ':reason'  => $note,
        ':user_id' => $author_id,
    ]);

    // Record in sanctions (level 3 = permanent ban)
    $db->prepare("
        INSERT INTO user_sanctions (user_id, issued_by, level, reason)
        VALUES (:user_id, :issued_by, 3, :reason)
    ")->execute([
        ':user_id'   => $author_id,
        ':issued_by' => $_SESSION['user_id'] ?? 0,
        ':reason'    => $note,
    ]);

    // Mark report reviewed
    if ($type === 'thread') {
        $db->prepare('UPDATE thread_reports  SET status = "reviewed" WHERE id = :id')
           ->execute([':id' => $id]);
    } else {
        $db->prepare('UPDATE comment_reports SET status = "reviewed" WHERE id = :id')
           ->execute([':id' => $id]);
    }

    jsonSuccess([], 'User has been banned.');
}

jsonError('Invalid action or method.', 405);