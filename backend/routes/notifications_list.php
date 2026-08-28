<?php
/**
 * notifications_list.php
 * Returns notifications for the current admin user for the topbar dropdown.
 * Place at: /backend/routes/notifications_list.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

$db     = new Database();
$conn   = $db->getConnection();
$userId = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'mark-read') {
        $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid")
             ->execute([':uid' => $userId]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Fetch latest 8 notifications
    $stmt = $conn->prepare("
        SELECT id, type, title, message, is_read, created_at
        FROM notifications
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $stmt->execute([':uid' => $userId]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Unread count
    $unreadStmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $unreadStmt->execute([':uid' => $userId]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    // Also build system-level alerts (pending requests, flagged reports)
    $pendingStmt = $conn->query("SELECT COUNT(*) FROM service_applications WHERE status IN ('pending','action_required')");
    $pendingCount = (int)$pendingStmt->fetchColumn();

    $flaggedStmt = $conn->query("
        SELECT (SELECT COUNT(*) FROM thread_reports WHERE status='pending') +
               (SELECT COUNT(*) FROM comment_reports WHERE status='pending') AS total
    ");
    $flaggedCount = (int)$flaggedStmt->fetchColumn();

    echo json_encode([
        'status'       => 'success',
        'notifications' => $notifs,
        'unreadCount'  => $unreadCount,
        'systemAlerts' => [
            'pendingRequests' => $pendingCount,
            'flaggedReports'  => $flaggedCount,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}