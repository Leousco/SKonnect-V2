<?php






header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

try {

    
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM users u
        JOIN user_status us ON u.id = us.user_id
        WHERE u.role = 'resident'
          AND us.is_deleted = FALSE
    ");
    $totalMembers = (int) $stmt->fetchColumn();

    
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM users u
        JOIN user_status us ON u.id = us.user_id
        WHERE u.role = 'resident'
          AND us.is_deleted = FALSE
          AND EXTRACT(MONTH FROM u.created_at) = EXTRACT(MONTH FROM CURRENT_DATE)
          AND EXTRACT(YEAR FROM u.created_at)  = EXTRACT(YEAR FROM CURRENT_DATE)
    ");
    $membersThisMonth = (int) $stmt->fetchColumn();

    
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM service_applications
        WHERE status IN ('pending', 'action_required')
    ");
    $pendingRequests = (int) $stmt->fetchColumn();

    
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM announcements
        WHERE status = 'active'
    ");
    $announcements = (int) $stmt->fetchColumn();

    
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM announcements
        WHERE status    = 'active'
          AND expired_at IS NOT NULL
          AND expired_at BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL '7 days')
    ");
    $expiringSoon = (int) $stmt->fetchColumn();

    
    $stmt = $conn->query("
        SELECT
          (SELECT COUNT(*) FROM thread_reports  WHERE status = 'pending') +
          (SELECT COUNT(*) FROM comment_reports WHERE status = 'pending') AS total
    ");
    $flaggedReports = (int) $stmt->fetchColumn();

    
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

    
    $stmt = $conn->query("
        SELECT
            s.category,
            COUNT(*) AS count
        FROM service_applications sa
        JOIN services s ON sa.service_id = s.id
        WHERE EXTRACT(MONTH FROM sa.submitted_at) = EXTRACT(MONTH FROM CURRENT_DATE)
          AND EXTRACT(YEAR FROM sa.submitted_at)  = EXTRACT(YEAR FROM CURRENT_DATE)
        GROUP BY s.category
        ORDER BY count DESC
    ");
    $byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $stmt = $conn->query("
        SELECT
            to_char(created_at, 'Mon')    AS month,
            to_char(created_at, 'YYYY-MM') AS ym,
            COUNT(*)                         AS count
        FROM users
        WHERE role       = 'resident'
          AND created_at >= (CURRENT_DATE - INTERVAL '6 months')
        GROUP BY ym, month
        ORDER BY ym ASC
    ");
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $stmt = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role       = 'resident'
          AND created_at >= (CURRENT_DATE - INTERVAL '6 months')
    ");
    $membersSince6 = (int) $stmt->fetchColumn();

    
    $stmt = $conn->query("
        SELECT COUNT(*) AS active
        FROM users u
        JOIN user_status us ON u.id = us.user_id
        WHERE u.role       = 'resident'
          AND us.is_active  = TRUE
          AND us.is_deleted = FALSE
          AND us.is_banned  = FALSE
    ");
    $activeMembers = (int) $stmt->fetchColumn();
    $activeRate    = $totalMembers > 0
        ? round(($activeMembers / $totalMembers) * 100)
        : 0;

    
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