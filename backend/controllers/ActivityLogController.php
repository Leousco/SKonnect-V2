<?php
/**
 * ActivityLogController.php
 *
 * GET  ?action=get_logs  → paginated, filtered log entries (admin only)
 * Static helper  ActivityLogController::log(...)  → INSERT a row from any controller
 *
 * Usage: ActivityLogController::log($conn, $userId, 'approved', 'Approved request for Juan');
 */

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

class ActivityLogController
{
    /**
     * @param PDO         $conn
     * @param int|null    $userId   Null = system action
     * @param string      $action   e.g. 'approved', 'deleted', 'login'
     * @param string      $description
     * @param string|null $ip       Auto-detected if null
     */
    public static function log(PDO $conn, ?int $userId, string $action, string $description, ?string $ip = null): void
    {
        try {
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $action, $description, $ip ?? self::clientIp()]);
        } catch (Exception $e) {
            error_log('[ActivityLog] ' . $e->getMessage());
        }
    }

    private static function clientIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) return trim(explode(',', $_SERVER[$key])[0]);
        }
        return '0.0.0.0';
    }
}

/* ── HTTP endpoint ── */
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {

    RoleMiddleware::requireAdmin();
    header('Content-Type: application/json');

    $action = $_GET['action'] ?? '';

    if ($action !== 'get_logs') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Unknown action: '$action'"]);
        exit;
    }

    try {
        $db   = new Database();
        $conn = $db->getConnection();

        $search   = trim($_GET['search']        ?? '');
        $actFlt   = trim($_GET['action_filter'] ?? '');
        $dateFrom = trim($_GET['date_from']     ?? '');
        $dateTo   = trim($_GET['date_to']       ?? '');
        $page     = max(1, (int)($_GET['page']      ?? 1));
        $pageSize = max(1, min(100, (int)($_GET['page_size'] ?? 10)));

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = "(al.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR al.action LIKE ?)";
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($actFlt !== '') {
            $where[]  = 'al.action = ?';
            $params[] = $actFlt;
        }
        if ($dateFrom !== '') {
            $where[]  = 'DATE(al.created_at) >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[]  = 'DATE(al.created_at) <= ?';
            $params[] = $dateTo;
        }

        $whereSQL = implode(' AND ', $where);
        $offset   = ($page - 1) * $pageSize;

        $cntStmt = $conn->prepare("
            SELECT COUNT(*) FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE {$whereSQL}
        ");
        $cntStmt->execute($params);
        $total = (int)$cntStmt->fetchColumn();

        $rowStmt = $conn->prepare("
            SELECT
                al.id,
                al.user_id,
                COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System') AS user_name,
                COALESCE(u.role, 'system')                                  AS user_role,
                al.action,
                al.description,
                al.created_at
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE {$whereSQL}
            ORDER BY al.created_at DESC
            LIMIT {$pageSize} OFFSET {$offset}
        ");
        $rowStmt->execute($params);
        $rows = $rowStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data'   => [
                'logs'      => $rows,
                'total'     => $total,
                'page'      => $page,
                'page_size' => $pageSize,
                'pages'     => (int)ceil($total / $pageSize),
            ],
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}