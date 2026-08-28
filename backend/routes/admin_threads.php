<?php
// backend/routes/admin_threads.php

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

    $category = $_GET['category'] ?? 'all';
    $status   = $_GET['status']   ?? 'all';
    $search   = trim($_GET['search'] ?? '');

    $sql = "
        SELECT
            t.id,
            t.subject        AS title,
            t.message        AS excerpt,
            t.category,
            t.status,
            t.is_pinned      AS pinned,
            t.is_flagged     AS flagged,
            t.is_removed,
            t.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS author,
            COUNT(tc.id) AS comments
        FROM threads t
        JOIN users u ON u.id = t.author_id
        LEFT JOIN thread_comments tc
            ON tc.thread_id = t.id AND tc.is_removed = 0
        WHERE t.is_removed = 0
    ";

    $params = [];

    if ($category !== 'all') {
        $sql .= ' AND t.category = :category';
        $params[':category'] = $category;
    }
    if ($status !== 'all') {
        $sql .= ' AND t.status = :status';
        $params[':status'] = $status;
    }
    if ($search !== '') {
        $sql .= ' AND (t.subject LIKE :search OR CONCAT(u.first_name, \' \', u.last_name) LIKE :search2)';
        $params[':search']  = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }

    $sql .= ' GROUP BY t.id ORDER BY t.is_pinned DESC, t.created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast types
    foreach ($threads as &$t) {
        $t['pinned']     = (bool) $t['pinned'];
        $t['flagged']    = (bool) $t['flagged'];
        $t['is_removed'] = (bool) $t['is_removed'];
        $t['comments']   = (int)  $t['comments'];
    }
    unset($t);

    jsonSuccess($threads);
}

/* ── POST ?action=pin ───────────────────────────────────── */

if ($method === 'POST' && $action === 'pin') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? 0);
    if ($id === 0) jsonError('Invalid thread ID.');

    // Toggle
    $stmt = $db->prepare('UPDATE threads SET is_pinned = NOT is_pinned WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $row = $db->prepare('SELECT is_pinned FROM threads WHERE id = :id');
    $row->execute([':id' => $id]);
    $pinned = (bool) $row->fetchColumn();

    jsonSuccess(['pinned' => $pinned], $pinned ? 'Thread pinned.' : 'Thread unpinned.');
}

/* ── POST ?action=delete ────────────────────────────────── */

if ($method === 'POST' && $action === 'delete') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? 0);
    if ($id === 0) jsonError('Invalid thread ID.');

    $check = $db->prepare('SELECT id FROM threads WHERE id = :id');
    $check->execute([':id' => $id]);
    if (!$check->fetch()) jsonError('Thread not found.', 404);

    // Soft-delete
    $stmt = $db->prepare('UPDATE threads SET is_removed = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);

    jsonSuccess([], 'Thread deleted.');
}

jsonError('Invalid action or method.', 405);