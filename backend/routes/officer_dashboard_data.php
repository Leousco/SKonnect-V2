<?php






require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ServiceRequestModel.php';
require_once __DIR__ . '/../models/AnnouncementModel.php';
require_once __DIR__ . '/../models/ServiceModel.php';

header('Content-Type: application/json; charset=utf-8');
ob_clean();

try {
    $db   = (new Database())->getConnection();
    $srm  = new ServiceRequestModel();
    $annM = new AnnouncementModel();
    $svcM = new ServiceModel();

    
    $statusCounts       = $srm->getStatusCounts();
    $pendingCount       = $statusCounts['pending'] + $statusCounts['action_required'];
    $actionRequiredCount = $statusCounts['action_required'];

    $annStats       = $annM->getStats();
    $activeAnnCount = (int)($annStats['published'] ?? 0);

    $expiringAnnCount = (int)$db->query("
        SELECT COUNT(*) FROM announcements
        WHERE status = 'active'
          AND expired_at IS NOT NULL
          AND expired_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ")->fetchColumn();

    $activeServicesCount = count($svcM->getAll(['status' => 'active']));

    $totalResidents    = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
    $newResidentsMonth = (int)$db->query("
        SELECT COUNT(*) FROM users
        WHERE role = 'resident'
          AND MONTH(created_at) = MONTH(CURDATE())
          AND YEAR(created_at)  = YEAR(CURDATE())
    ")->fetchColumn();

    
    $stmtReq = $db->prepare("
        SELECT
            sa.id,
            sa.full_name,
            sa.status,
            sa.submitted_at,
            sv.name AS service_name
        FROM service_applications sa
        INNER JOIN services sv ON sv.id = sa.service_id
        WHERE sa.status IN ('pending', 'action_required')
        ORDER BY sa.submitted_at DESC
        LIMIT 5
    ");
    $stmtReq->execute();
    $recentRequests = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

    
    $stmtBar = $db->prepare("
        SELECT sv.category AS service_category, COUNT(*) AS cnt
        FROM service_applications sa
        INNER JOIN services sv ON sv.id = sa.service_id
        GROUP BY sv.category
        ORDER BY cnt DESC, sv.category ASC
        LIMIT 5
    ");
    $stmtBar->execute();
    $barData = $stmtBar->fetchAll(PDO::FETCH_ASSOC);

    
    $stmtSpark = $db->query("
        SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS ym, COUNT(*) AS cnt
        FROM service_applications
        WHERE submitted_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
        GROUP BY ym
        ORDER BY ym ASC
    ");
    $sparkRaw = [];
    foreach ($stmtSpark->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sparkRaw[$row['ym']] = (int)$row['cnt'];
    }

    $sparkLabels = [];
    $sparkValues = [];
    for ($i = 5; $i >= 0; $i--) {
        $sparkLabels[] = date('M', strtotime("-{$i} months"));
        $key           = date('Y-m', strtotime("-{$i} months"));
        $sparkValues[] = $sparkRaw[$key] ?? 0;
    }

    
    $svgW   = 560; $svgH = 120; $padT = 10; $padB = 10;
    $maxVal = max(array_merge($sparkValues, [1]));
    $n      = count($sparkValues);
    $xStep  = $n > 1 ? $svgW / ($n - 1) : $svgW;
    $pts    = [];
    foreach ($sparkValues as $i => $v) {
        $x    = (int)round($i * $xStep);
        $y    = (int)round($svgH - $padB - ($v / $maxVal) * ($svgH - $padT - $padB));
        $pts[] = "{$x},{$y}";
    }
    $sparkPath     = 'M' . implode(' L', $pts);
    $lastParts     = explode(',', end($pts));
    $lastX         = $lastParts[0];
    $sparkAreaPath = $sparkPath . " L{$lastX},{$svgH} L0,{$svgH} Z";

    
    $thisMonthCount = $sparkValues[count($sparkValues) - 1] ?? 0;
    $resolvedMonth  = (int)$db->query("
        SELECT COUNT(*) FROM service_applications
        WHERE status = 'approved'
          AND MONTH(updated_at) = MONTH(CURDATE())
          AND YEAR(updated_at)  = YEAR(CURDATE())
    ")->fetchColumn();
    $decidedMonth = (int)$db->query("
        SELECT COUNT(*) FROM service_applications
        WHERE status IN ('approved','rejected')
          AND MONTH(updated_at) = MONTH(CURDATE())
          AND YEAR(updated_at)  = YEAR(CURDATE())
    ")->fetchColumn();
    $approvalRate = $decidedMonth > 0 ? round(($resolvedMonth / $decidedMonth) * 100) . '%' : 'N/A';

    
    
    $stmtActivity = $db->query("
        SELECT
            al.action,
            al.description,
            al.created_at AS activity_time,
            COALESCE(NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), ''), 'Officer') AS actor_name
        FROM activity_logs al
        INNER JOIN users u ON u.id = al.user_id
        WHERE u.role = 'sk_officer'
        ORDER BY al.created_at DESC, al.id DESC
        LIMIT 100
    ");
    $recentActivity = $stmtActivity->fetchAll(PDO::FETCH_ASSOC);

    
    $recentAnn = array_slice($annM->getAll(['status' => 'active']), 0, 4);
    
    if (empty($recentAnn)) {
        $recentAnn = array_slice($annM->getAll(), 0, 4);
    }

    
    echo json_encode([
        'success' => true,
        'widgets' => [
            'pending_requests'      => $pendingCount,
            'action_required'       => $actionRequiredCount,
            'active_announcements'  => $activeAnnCount,
            'expiring_announcements'=> $expiringAnnCount,
            'active_services'       => $activeServicesCount,
            'total_residents'       => $totalResidents,
            'new_residents_month'   => $newResidentsMonth,
        ],
        'recent_requests'  => $recentRequests,
        'bar_chart'        => $barData,
        'bar_month_label'  => 'All time',
        'sparkline'        => [
            'labels'         => $sparkLabels,
            'values'         => $sparkValues,
            'path'           => $sparkPath,
            'area_path'      => $sparkAreaPath,
            'stats'          => [
                'pending'       => $pendingCount,
                'this_month'    => $thisMonthCount,
                'resolved'      => $resolvedMonth,
                'approval_rate' => $approvalRate,
            ],
        ],
        'recent_activity'   => $recentActivity,
        'recent_announcements' => $recentAnn,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Dashboard data error: ' . $e->getMessage(),
    ]);
}