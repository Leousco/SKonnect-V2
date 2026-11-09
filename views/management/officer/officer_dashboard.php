<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');


require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/models/ServiceRequestModel.php';
require_once __DIR__ . '/../../../backend/models/AnnouncementModel.php';
require_once __DIR__ . '/../../../backend/models/ServiceModel.php';


$db   = (new Database())->getConnection();
$srm  = new ServiceRequestModel();
$annM = new AnnouncementModel();
$svcM = new ServiceModel();





function dashInitials(string $name): string {
    $parts = array_values(array_filter(explode(' ', trim($name))));
    $init  = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $init .= strtoupper(mb_substr($p, 0, 1));
    }
    return $init ?: '?';
}

function dashStatusPill(string $status): string {
    $map = [
        'pending'          => ['status-pending',    'Pending'],
        'action_required'  => ['status-processing', 'Action Req.'],
        'approved'         => ['status-approved',   'Approved'],
        'rejected'         => ['status-declined',   'Declined'],
        'cancelled'        => ['status-declined',   'Cancelled'],
    ];
    [$cls, $lbl] = $map[$status] ?? ['status-pending', ucfirst(str_replace('_', ' ', $status))];
    return '<span class="status-pill ' . $cls . '">' . htmlspecialchars($lbl) . '</span>';
}

function dashTimeAgo(string $datetime): string {
    $ts = strtotime($datetime);
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

function dashActivityMeta(array $entry): array {
    $action = (string)($entry['action'] ?? '');
    $details = json_decode((string)($entry['description'] ?? ''), true);
    $details = is_array($details) ? $details : [];
    $targetName = htmlspecialchars((string)($details['target_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $targetUser = htmlspecialchars((string)($details['target_user'] ?? ''), ENT_QUOTES, 'UTF-8');
    $actions = [
        'service_request_approved' => ['Approved service request', 'icon-green'],
        'service_request_rejected' => ['Declined service request', 'icon-amber'],
        'service_request_cancelled' => ['Cancelled service request', 'icon-amber'],
        'service_request_note_added' => ['Added a note to', 'icon-cyan'],
        'service_created' => ['Created service', 'icon-cyan'],
        'service_updated' => ['Updated service', 'icon-cyan'],
        'service_deleted' => ['Deleted service', 'icon-amber'],
        'service_status_updated' => ['Updated service status', 'icon-cyan'],
        'announcement_created' => ['Created announcement', 'icon-cyan'],
        'announcement_updated' => ['Updated announcement', 'icon-cyan'],
        'announcement_archived' => ['Archived announcement', 'icon-amber'],
        'announcement_restored' => ['Restored announcement', 'icon-green'],
        'announcement_attachment_removed' => ['Removed an attachment from announcement', 'icon-amber'],
        'announcement_deleted' => ['Deleted announcement', 'icon-amber'],
        'event_created' => ['Created event', 'icon-indigo'],
        'event_updated' => ['Updated event', 'icon-indigo'],
        'event_deleted' => ['Deleted event', 'icon-amber'],
    ];
    [$label, $iconClass] = $actions[$action] ?? [
        htmlspecialchars(ucwords(str_replace('_', ' ', $action ?: 'Officer action')), ENT_QUOTES, 'UTF-8'),
        'icon-cyan',
    ];
    $targetType = htmlspecialchars((string)($details['target_type'] ?? ''), ENT_QUOTES, 'UTF-8');
    $targetLabel = $targetName !== '' ? $targetName : ($targetType !== '' ? $targetType : 'item');
    if ($action === 'service_request_note_added' && $targetUser !== '') {
        $text = "{$label} <strong>{$targetLabel}</strong> request for <strong>{$targetUser}</strong>";
    } elseif (str_starts_with($action, 'service_request_') && $targetUser !== '') {
        $text = "{$label} <strong>{$targetLabel}</strong> for <strong>{$targetUser}</strong>";
    } else {
        $text = "{$label}: <strong>{$targetLabel}</strong>";
    }

    return [
        'icon_class' => $iconClass,
        'svg'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>',
        'text'       => $text,
    ];
}






$statusCounts        = $srm->getStatusCounts();
$pendingCount        = $statusCounts['pending'] + $statusCounts['action_required'];
$actionRequiredCount = $statusCounts['action_required'];

$annStats       = $annM->getStats();
$activeAnnCount = (int)($annStats['published'] ?? 0);

$expiringAnnCount = (int)$db->query("
    SELECT COUNT(*) FROM announcements
    WHERE status = 'active'
      AND expired_at IS NOT NULL
      AND expired_at BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days'
")->fetchColumn();

$activeServicesCount = count($svcM->getAll(['status' => 'active']));

$totalResidents    = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
$newResidentsMonth = (int)$db->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'resident'
      AND EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR FROM created_at)  = EXTRACT(YEAR FROM CURRENT_DATE)
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
$barData   = $stmtBar->fetchAll(PDO::FETCH_ASSOC);
$barMax    = $barData ? max(array_column($barData, 'cnt')) : 1;
$barColors = ['bar-cyan', 'bar-indigo', 'bar-green', 'bar-amber', 'bar-muted'];


$stmtSpark = $db->query("
    SELECT TO_CHAR(submitted_at, 'YYYY-MM') AS ym, COUNT(*) AS cnt
    FROM service_applications
    WHERE submitted_at >= DATE_TRUNC('month', CURRENT_DATE - INTERVAL '5 months')
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


// Points sit at the centre of equal columns so they line up with the
// month labels underneath, and nothing is clipped at the edges.
$svgW   = 560; $svgH = 120; $padT = 12; $padB = 12;
$maxVal = max(array_merge($sparkValues, [1]));
$n      = count($sparkValues);
$xStep  = $svgW / max($n, 1);
$pts    = [];
$dots   = [];
foreach ($sparkValues as $i => $v) {
    $x    = (int)round(($i + 0.5) * $xStep);
    $y    = (int)round($svgH - $padB - ($v / $maxVal) * ($svgH - $padT - $padB));
    $pts[]  = "{$x},{$y}";
    $dots[] = [$x, $y];
}
$sparkPath     = 'M' . implode(' L', $pts);
$firstX        = $dots[0][0];
$lastX         = $dots[count($dots) - 1][0];
$sparkAreaPath = $sparkPath . " L{$lastX},{$svgH} L{$firstX},{$svgH} Z";
$hasSparkData  = max($sparkValues) > 0;


$thisMonthCount = $sparkValues[count($sparkValues) - 1] ?? 0;
$resolvedMonth  = (int)$db->query("
    SELECT COUNT(*) FROM service_applications
    WHERE status = 'approved'
      AND EXTRACT(MONTH FROM updated_at) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR FROM updated_at)  = EXTRACT(YEAR FROM CURRENT_DATE)
")->fetchColumn();
$decidedMonth = (int)$db->query("
    SELECT COUNT(*) FROM service_applications
    WHERE status IN ('approved','rejected')
      AND EXTRACT(MONTH FROM updated_at) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR FROM updated_at)  = EXTRACT(YEAR FROM CURRENT_DATE)
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


$jsData = json_encode([
    'sparkline' => ['labels' => $sparkLabels, 'values' => $sparkValues],
    'barChart'  => array_map(fn($r) => [
        'label' => $r['service_category'],
        'count' => (int)$r['cnt'],
    ], $barData),
    'widgets'   => [
        'pending_requests'     => $pendingCount,
        'active_announcements' => $activeAnnCount,
        'active_services'      => $activeServicesCount,
        'total_residents'      => $totalResidents,
    ],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | SK Officer Dashboard</title>
    <link rel="stylesheet" href="../../../styles/management/officer/officer_dashboard.css">
    <link rel="stylesheet" href="../../../styles/management/officer_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css?v=2">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
</head>
<body>

<div class="off-layout">

    <?php include __DIR__ . '/../../../components/management/officer/officer_sidebar.php'; ?>

    
    <main class="off-content">

    <?php
    $pageTitle      = 'Dashboard';
    $pageBreadcrumb = [['Home', '#'], ['Dashboard', null]];
    $officerName    = $_SESSION['user_name'] ?? 'SK Officer';
    $officerRole    = 'SK Officer';
    $notifCount     = 3;
    include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

        
        <section class="off-widgets">

            
            <div class="off-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Pending requests</span>
                    <p class="widget-number" data-widget="pending_requests">
                        <?= $pendingCount ?>
                    </p>
                    <?php if ($actionRequiredCount > 0): ?>
                        <span class="widget-trend warning">
                            &#9650; <?= $actionRequiredCount ?> need action
                        </span>
                    <?php elseif ($pendingCount > 0): ?>
                        <span class="widget-trend warning">&#9650; Needs attention</span>
                    <?php else: ?>
                        <span class="widget-trend up">&#10003; All clear</span>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="off-widget-card widget-cyan">
                <div class="widget-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Announcements</span>
                    <p class="widget-number" data-widget="active_announcements">
                        <?= $activeAnnCount ?>
                    </p>
                    <?php if ($expiringAnnCount > 0): ?>
                        <span class="widget-trend warning">
                            &#9650; <?= $expiringAnnCount ?> expiring soon
                        </span>
                    <?php else: ?>
                        <span class="widget-trend neutral">Active &amp; published</span>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="off-widget-card widget-green">
                <div class="widget-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Available services</span>
                    <p class="widget-number" data-widget="active_services">
                        <?= $activeServicesCount ?>
                    </p>
                    <?php if ($activeServicesCount > 0): ?>
                        <span class="widget-trend up">&#10003; All active</span>
                    <?php else: ?>
                        <span class="widget-trend warning">No active services</span>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="off-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Total residents</span>
                    <p class="widget-number" data-widget="total_residents">
                        <?= number_format($totalResidents) ?>
                    </p>
                    <?php if ($newResidentsMonth > 0): ?>
                        <span class="widget-trend up">
                            &#9650; <?= $newResidentsMonth ?> new this month
                        </span>
                    <?php else: ?>
                        <span class="widget-trend neutral">No new this month</span>
                    <?php endif; ?>
                </div>
            </div>

        </section>

        








        <div class="off-lower">

            
            <div class="off-left-col">

                
                <section class="off-requests-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Pending service requests</h2>
                        <a href="officer_requests.php" class="btn-off-sm">View all &rsaquo;</a>
                    </div>
                    <?php if (empty($recentRequests)): ?>
                        <div class="panel-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            <strong>No pending requests</strong>
                            New requests from residents will show up here.
                        </div>
                    <?php else: ?>
                    <div class="requests-table-wrap">
                        <table class="requests-table">
                            <colgroup>
                                <col class="requests-col-resident">
                                <col class="requests-col-service">
                                <col class="requests-col-submitted">
                                <col class="requests-col-status">
                                <col class="requests-col-action">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Resident</th>
                                    <th>Service name</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $req): ?>
                                        <tr>
                                            <td class="requests-resident">
                                                <div class="req-name">
                                                    <div class="req-avatar">
                                                        <?= htmlspecialchars(dashInitials($req['full_name'])) ?>
                                                    </div>
                                                    <span class="req-resident-name"><?= htmlspecialchars($req['full_name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="requests-service" title="<?= htmlspecialchars($req['service_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($req['service_name']) ?>
                                            </td>
                                            <td class="requests-submitted">
                                                <?= date('M j, Y', strtotime($req['submitted_at'])) ?>
                                            </td>
                                            <td class="requests-status"><?= dashStatusPill($req['status']) ?></td>
                                            <td class="requests-action">
                                                <a href="officer_requests.php?id=<?= (int)$req['id'] ?>"
                                                   class="action-link">Review</a>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </section>

                
                <section class="chart-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Requests by service category</h2>
                        <span class="chart-period">All time</span>
                    </div>
                    <div class="bar-chart-wrap">
                        <?php if (empty($barData)): ?>
                            <div class="panel-empty">
                                <strong>No requests yet</strong>
                                Service categories will be ranked here once residents apply.
                            </div>
                        <?php else: ?>
                            <?php foreach ($barData as $i => $bar):
                                $pct = $barMax > 0 ? round(($bar['cnt'] / $barMax) * 100) : 0;
                                $colorClass = $barColors[$i % count($barColors)];
                            ?>
                                <div class="bar-row">
                                    <span class="bar-label"
                                      title="<?= htmlspecialchars($bar['service_category']) ?>">
                                        <?= htmlspecialchars(
                                        mb_strlen($bar['service_category']) > 22
                                            ? mb_substr($bar['service_category'], 0, 22) . '…'
                                            : $bar['service_category']
                                        ) ?>
                                    </span>
                                    <div class="bar-track">
                                        <div class="bar-fill <?= $colorClass ?>"
                                             data-width="<?= $pct ?>%"
                                             style="width:0%"></div>
                                    </div>
                                    <span class="bar-count"><?= (int)$bar['cnt'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                
                <section class="chart-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Request volume</h2>
                        <span class="chart-period">Last 6 months</span>
                    </div>
                    <div class="sparkline-wrap">
                        <?php if ($hasSparkData): ?>
                            <svg class="sparkline-svg"
                                 viewBox="0 0 560 120" preserveAspectRatio="none"
                                 aria-label="Request volume over last 6 months">
                                <defs>
                                    <linearGradient id="offSparkGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%"   stop-color="#2d7d9a" stop-opacity="0.25"/>
                                        <stop offset="100%" stop-color="#2d7d9a" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                <path d="<?= htmlspecialchars($sparkAreaPath) ?>"
                                      fill="url(#offSparkGrad)"/>
                                <path d="<?= htmlspecialchars($sparkPath) ?>"
                                      fill="none" stroke="#2d7d9a" stroke-width="2.5"
                                      stroke-linecap="round" stroke-linejoin="round"
                                      vector-effect="non-scaling-stroke"/>
                                <?php foreach ($dots as [$dx, $dy]): ?>
                                    <?php /* zero-length round-capped path = a dot that stays circular when the svg stretches */ ?>
                                    <path d="M<?= $dx ?>,<?= $dy ?> h0" stroke="#2d7d9a" stroke-width="7"
                                          stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                                <?php endforeach; ?>
                            </svg>
                        <?php else: ?>
                            <div class="panel-empty">
                                <strong>No requests in the last 6 months</strong>
                                Monthly volume will be charted here.
                            </div>
                        <?php endif; ?>
                        <div class="sparkline-labels" style="--cols: <?= count($sparkLabels) ?>">
                            <?php foreach ($sparkLabels as $lbl): ?>
                                <span><?= htmlspecialchars($lbl) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="sparkline-stats--quad">
                            <div class="spark-stat">
                                <span class="spark-val" data-widget="pending_requests">
                                    <?= $pendingCount ?>
                                </span>
                                <span class="spark-lbl">Pending</span>
                            </div>
                            <div class="spark-stat">
                                <span class="spark-val"><?= $thisMonthCount ?></span>
                                <span class="spark-lbl">This month</span>
                            </div>
                            <div class="spark-stat">
                                <span class="spark-val"><?= $resolvedMonth ?></span>
                                <span class="spark-lbl">Resolved</span>
                            </div>
                            <div class="spark-stat">
                                <span class="spark-val"><?= htmlspecialchars($approvalRate) ?></span>
                                <span class="spark-lbl">Approval rate</span>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            
            <aside class="off-right-col">

                
                <section class="quick-actions-panel">
                    <h2 class="section-label">Quick actions</h2>
                    <div class="quick-actions-grid">
                        <a href="officer_announcements.php" class="quick-action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            New announcement
                        </a>
                        <a href="officer_requests.php" class="quick-action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            Manage requests
                        </a>
                        <a href="officer_services.php" class="quick-action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            Add service
                        </a>
                        <a href="officer_events.php" class="quick-action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5"/>
                            </svg>
                            Create event
                        </a>
                    </div>
                </section>

                
                <section class="chart-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Recent announcements</h2>
                        <a href="officer_announcements.php" class="btn-off-sm">Manage &rsaquo;</a>
                    </div>
                    <ul class="off-ann-list">
                        <?php if (empty($recentAnn)): ?>
                            <li class="panel-empty">
                                <strong>No announcements yet</strong>
                                Published announcements will appear here.
                            </li>
                        <?php else: ?>
                            <?php foreach ($recentAnn as $ann):
                                $pubDate = strtotime($ann['published_at']);
                                $subInfo = ucfirst($ann['status']);
                                if (!empty($ann['expired_at'])) {
                                    $expTs = strtotime($ann['expired_at']);
                                    if ($expTs < time()) {
                                        $subInfo .= ' · Expired';
                                    } elseif ($expTs < strtotime('+7 days')) {
                                        $subInfo .= ' · Expiring soon';
                                    }
                                }
                            ?>
                                <li class="off-ann-item">
                                    <div class="ann-date-badge">
                                        <span class="ann-day"><?= date('j', $pubDate) ?></span>
                                        <span class="ann-mon"><?= date('M', $pubDate) ?></span>
                                    </div>
                                    <div class="ann-info">
                                        <strong title="<?= htmlspecialchars($ann['title']) ?>">
                                            <?= htmlspecialchars($ann['title']) ?>
                                        </strong>
                                        <span><?= htmlspecialchars($subInfo) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </section>

                
                <section class="off-activity-panel">
                    <h2 class="section-label">Recent activity</h2>
                    <div class="activity-feed">
                        <?php if (empty($recentActivity)): ?>
                            <div class="panel-empty">
                                <strong>No recent activity</strong>
                                Actions performed by officers will show up here.
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $entry):
                                $meta = dashActivityMeta($entry);
                                $time = dashTimeAgo($entry['activity_time'] ?? '');
                            ?>
                                <div class="activity-entry">
                                    <div class="activity-icon <?= $meta['icon_class'] ?>">
                                        <?= $meta['svg'] ?>
                                    </div>
                                    <div class="activity-info">
                                        <p><?= $meta['text'] ?></p>
                                        <span><?= htmlspecialchars((string)$entry['actor_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                

            </aside>
        </div>

    </main>
</div>


<script>
    window.__dashboardData = <?= $jsData ?>;
</script>
<script src="../../../scripts/management/officer/officer_dashboard.js"></script>

</body>
</html>