<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EventModel.php';

RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

try {
    // Recent community threads
    $recentThreadsStmt = $conn->query("
        SELECT t.id, t.subject, t.category, t.status, t.created_at,
               CONCAT(u.first_name, ' ', u.last_name) AS author_name,
               (SELECT COUNT(*) FROM thread_comments tc
                WHERE tc.thread_id = t.id AND tc.is_removed = FALSE) AS comment_count
        FROM threads t
        JOIN users u ON u.id = t.author_id
        WHERE t.is_removed = FALSE
        ORDER BY t.created_at DESC, t.id DESC
        LIMIT 12
    ");
    $recentThreads = $recentThreadsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending reports
    $pendingReportsStmt = $conn->query("
        SELECT *
        FROM (
            SELECT tr.id, 'thread' AS type, t.subject AS content,
                   tr.category AS reason, tr.created_at,
                   CONCAT(u.first_name, ' ', u.last_name) AS reporter_name
            FROM thread_reports tr
            JOIN threads t ON t.id = tr.thread_id
            JOIN users u ON u.id = tr.reporter_id
            WHERE tr.status = 'pending'

            UNION ALL

            SELECT cr.id, cr.target_type AS type,
                   COALESCE(th.subject, LEFT(COALESCE(tc.message, rep.message, '(deleted content)'), 70)) AS content,
                   cr.category AS reason, cr.created_at,
                   CONCAT(u.first_name, ' ', u.last_name) AS reporter_name
            FROM comment_reports cr
            JOIN users u ON u.id = cr.reporter_id
            LEFT JOIN thread_comments tc
                ON cr.target_type = 'comment' AND tc.id = cr.target_id
            LEFT JOIN comment_replies rep
                ON cr.target_type = 'reply' AND rep.id = cr.target_id
            LEFT JOIN thread_comments parent_comment
                ON cr.target_type = 'reply' AND parent_comment.id = rep.comment_id
            LEFT JOIN threads th
                ON th.id = COALESCE(tc.thread_id, parent_comment.thread_id)
            WHERE cr.status = 'pending'
        ) reports
        ORDER BY created_at DESC
        LIMIT 12
    ");
    $pendingReports = $pendingReportsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent announcements
    $recentAnnouncementsStmt = $conn->query("
        SELECT id, title, category, status, published_at
        FROM announcements
        WHERE status <> 'deleted'
        ORDER BY published_at DESC, id DESC
        LIMIT 5
    ");
    $recentAnnouncements = $recentAnnouncementsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activity logs
    $recentActivityStmt = $conn->query("
        SELECT
            al.description,
            al.created_at,
            COALESCE(NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), ''), 'System') AS user_name
        FROM activity_logs al
        LEFT JOIN users u ON u.id = al.user_id
        ORDER BY al.created_at DESC, al.id DESC
        LIMIT 20
    ");
    $recentActivity = $recentActivityStmt->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming events
    $eventModel = new EventModel();
    $allEvents = $eventModel->getAll();
    $today = date('Y-m-d');
    $upcomingEvents = array_values(array_filter($allEvents, fn($e) => $e['event_date'] >= $today));
    usort($upcomingEvents, static function ($a, $b) {
        $dateOrder = strcmp($b['event_date'], $a['event_date']);
        if ($dateOrder !== 0) {
            return $dateOrder;
        }
        return strcmp($b['event_time'] ?? '', $a['event_time'] ?? '');
    });
    $upcomingEvents = array_slice($upcomingEvents, 0, 4);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'threads' => $recentThreads,
            'reports' => $pendingReports,
            'announcements' => $recentAnnouncements,
            'activity' => $recentActivity,
            'events' => $upcomingEvents,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
