<?php
/**
 * dashboard_stats.php
 * Returns real-time stats for the Admin Dashboard from the skonnect DB.
 * Place this at: /backend/routes/dashboard_stats.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

try {

    /* ── 1. Total registered residents (not deleted) ─────────────── */
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM users u
        JOIN user_status us ON u.id = us.user_id
        WHERE u.role = 'resident'
          AND us.is_deleted = 0
    ");
    $totalMembers = (int) $stmt->fetchColumn();

    /* ── 2. New residents added this calendar month ───────────────── */
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM users u
        JOIN user_status us ON u.id = us.user_id
        WHERE u.role = 'resident'
          AND us.is_deleted = 0
          AND MONTH(u.created_at) = MONTH(CURDATE())
          AND YEAR(u.created_at)  = YEAR(CURDATE())
    ");
    $membersThisMonth = (int) $stmt->fetchColumn();

    /* ── 3. Pending / action-required service applications ───────── */
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM service_applications
        WHERE status IN ('pending', 'action_required')
    ");
    $pendingRequests = (int) $stmt->fetchColumn();

    /* ── 4. Active announcements ──────────────────────────────────── */
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM announcements
        WHERE status = 'active'
    ");
    $announcements = (int) $stmt->fetchColumn();

    /* ── 5. Announcements expiring within the next 7 days ────────── */
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM announcements
        WHERE status    = 'active'
          AND expired_at IS NOT NULL
          AND expired_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $expiringSoon = (int) $stmt->fetchColumn();

    /* ── 6. Pending flagged reports (threads + comments combined) ─── */
    $stmt = $conn->query("
        SELECT
          (SELECT COUNT(*) FROM thread_reports  WHERE status = 'pending') +
          (SELECT COUNT(*) FROM comment_reports WHERE status = 'pending') AS total
    ");
    $flaggedReports = (int) $stmt->fetchColumn();

    /* ── 7. Recent pending requests (for the table, max 5) ───────── */
    $stmt = $conn->query("
        SELECT
            sa.id,
            sa.full_name,
            s.name     AS service_name,
            s.category,
            sa.submitted_at,
            sa.status
        FROM service_applications sa
        JOIN services s ON sa.service_id = s.id
        WHERE sa.status IN ('pending', 'action_required')
        ORDER BY sa.submitted_at DESC
        LIMIT 5
    ");
    $pendingList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ── 8. Service applications by category (current month) ─────── */
    $stmt = $conn->query("
        SELECT
            s.category,
            COUNT(*) AS count
        FROM service_applications sa
        JOIN services s ON sa.service_id = s.id
        WHERE MONTH(sa.submitted_at) = MONTH(CURDATE())
          AND YEAR(sa.submitted_at)  = YEAR(CURDATE())
        GROUP BY s.category
        ORDER BY count DESC
    ");
    $byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ── 9. Member registrations — last 6 calendar months ────────── */
    $stmt = $conn->query("
        SELECT
            DATE_FORMAT(created_at, '%b')    AS month,
            DATE_FORMAT(created_at, '%Y-%m') AS ym,
            COUNT(*)                         AS count
        FROM users
        WHERE role       = 'resident'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY ym, month
        ORDER BY ym ASC
    ");
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ── 10. Total residents registered in the last 6 months ─────── */
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role       = 'resident'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ");
    $membersSince6 = (int) $stmt->fetchColumn();

    /* ── 11. Active-member rate (not banned, not deleted) ────────── */
    $stmt = $conn->query("
        SELECT COUNT(*) AS active
        FROM users u
        JOIN user_status us ON u.id = us.user_id
        WHERE u.role       = 'resident'
          AND us.is_active  = 1
          AND us.is_deleted = 0
          AND us.is_banned  = 0
    ");
    $activeMembers = (int) $stmt->fetchColumn();
    $activeRate    = $totalMembers > 0
        ? round(($activeMembers / $totalMembers) * 100)
        : 0;

    /* ── Response ─────────────────────────────────────────────────── */
    echo json_encode([
        'status' => 'success',
        'data'   => [
            'totalMembers'     => $totalMembers,
            'membersThisMonth' => $membersThisMonth,
            'pendingRequests'  => $pendingRequests,
            'announcements'    => $announcements,
            'expiringSoon'     => $expiringSoon,
            'flaggedReports'   => $flaggedReports,
            'pendingList'      => $pendingList,
            'byCategory'       => $byCategory,
            'registrations'    => $registrations,
            'membersSince6'    => $membersSince6,
            'activeRate'       => $activeRate,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}