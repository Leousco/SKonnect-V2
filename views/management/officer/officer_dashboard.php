<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');

// ── DEPENDENCIES ──────────────────────────────────────────────
require_once __DIR__ . '/../../../backend/config/Database.php';
require_once __DIR__ . '/../../../backend/models/ServiceRequestModel.php';
require_once __DIR__ . '/../../../backend/models/AnnouncementModel.php';
require_once __DIR__ . '/../../../backend/models/ServiceModel.php';

// ── DB + MODEL INSTANCES ──────────────────────────────────────
$db   = (new Database())->getConnection();
$srm  = new ServiceRequestModel();
$annM = new AnnouncementModel();
$svcM = new ServiceModel();

// ─────────────────────────────────────────────────────────────
// HELPER FUNCTIONS
// ─────────────────────────────────────────────────────────────

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

function dashCategoryBadge(string $category): string {
    $map = [
        'medical'     => ['badge-indigency', 'Medical'],
        'education'   => ['badge-residency', 'Education'],
        'scholarship' => ['badge-residency', 'Scholarship'],
        'livelihood'  => ['badge-business',  'Livelihood'],
        'assistance'  => ['badge-clearance', 'Assistance'],
        'legal'       => ['badge-business',  'Legal'],
        'other'       => ['badge-business',  'Other'],
    ];
    [$cls, $lbl] = $map[$category] ?? ['badge-business', ucfirst($category)];
    return '<span class="req-badge ' . $cls . '">' . htmlspecialchars($lbl) . '</span>';
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
    $type = $entry['type'];
    $note = $entry['description'] ?? '';
    $subj = htmlspecialchars($entry['subject'] ?? '');

    if ($type === 'announcement') {
        return [
            'icon_class' => 'icon-cyan',
            'svg'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
            'text'       => "Published announcement: <strong>{$subj}</strong>",
        ];
    }
    if ($type === 'event') {
        return [
            'icon_class' => 'icon-indigo',
            'svg'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5"/></svg>',
            'text'       => "Scheduled event: <strong>{$subj}</strong>",
        ];
    }
    if (stripos($note, 'Request Approved') === 0) {
        return [
            'icon_class' => 'icon-green',
            'svg'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>',
            'text'       => "Approved request for <strong>{$subj}</strong>",
        ];
    }
    if (stripos($note, 'Request Declined') === 0) {
        return [
            'icon_class' => 'icon-amber',
            'svg'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>',
            'text'       => "Declined request from <strong>{$subj}</strong>",
        ];
    }
    if (stripos($note, 'Request Cancelled') === 0) {
        return [
            'icon_class' => 'icon-amber',
            'svg'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>',
            'text'       => "Cancelled request for <strong>{$subj}</strong>",
        ];
    }
    return [
        'icon_class' => 'icon-cyan',
        'svg'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>',
        'text'       => "Officer note added for <strong>{$subj}</strong>",
    ];
}

// ─────────────────────────────────────────────────────────────
// DATA FETCHING
// ─────────────────────────────────────────────────────────────

// ── 1. WIDGET COUNTS ──────────────────────────────────────────
$statusCounts        = $srm->getStatusCounts();
$pendingCount        = $statusCounts['pending'] + $statusCounts['action_required'];
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

// ── 2. RECENT PENDING REQUESTS TABLE ─────────────────────────
$stmtReq = $db->prepare("
    SELECT
        sa.id,
        sa.full_name,
        sa.status,
        sa.submitted_at,
        sv.name     AS service_name,
        sv.category AS service_category
    FROM service_applications sa
    INNER JOIN services sv ON sv.id = sa.service_id
    WHERE sa.status IN ('pending', 'action_required')
    ORDER BY sa.submitted_at DESC
    LIMIT 5
");
$stmtReq->execute();
$recentRequests = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

// ── 3. BAR CHART: Requests by Service Name (current month) ────
$stmtBar = $db->prepare("
    SELECT sv.name AS service_name, COUNT(*) AS cnt
    FROM service_applications sa
    INNER JOIN services sv ON sv.id = sa.service_id
    WHERE MONTH(sa.submitted_at) = MONTH(CURDATE())
      AND YEAR(sa.submitted_at)  = YEAR(CURDATE())
    GROUP BY sv.id, sv.name
    ORDER BY cnt DESC
    LIMIT 5
");
$stmtBar->execute();
$barData   = $stmtBar->fetchAll(PDO::FETCH_ASSOC);
$barMax    = $barData ? max(array_column($barData, 'cnt')) : 1;
$barColors = ['bar-cyan', 'bar-indigo', 'bar-green', 'bar-amber', 'bar-muted'];

// ── 4. SPARKLINE: Monthly request volume (last 6 months) ──────
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

// Build SVG path (ViewBox: 560×120, 10px top/bottom padding)
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

// Sparkline bottom stats
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

// ── 5. RECENT ACTIVITY FEED ───────────────────────────────────
$stmtActivity = $db->query("
    (
        SELECT
            'request'                                           AS type,
            an.note          COLLATE utf8mb4_unicode_ci         AS description,
            sa.full_name     COLLATE utf8mb4_unicode_ci         AS subject,
            sa.status        COLLATE utf8mb4_unicode_ci         AS status,
            an.created_at                                       AS activity_time
        FROM application_notes an
        INNER JOIN service_applications sa ON sa.id = an.application_id
        ORDER BY an.created_at DESC
        LIMIT 6
    )
    UNION ALL
    (
        SELECT
            'announcement'                                      AS type,
            a.title          COLLATE utf8mb4_unicode_ci         AS description,
            a.title          COLLATE utf8mb4_unicode_ci         AS subject,
            a.status         COLLATE utf8mb4_unicode_ci         AS status,
            a.published_at                                      AS activity_time
        FROM announcements a
        ORDER BY a.published_at DESC
        LIMIT 3
    )
    UNION ALL
    (
        SELECT
            'event'                                             AS type,
            e.title          COLLATE utf8mb4_unicode_ci         AS description,
            e.title          COLLATE utf8mb4_unicode_ci         AS subject,
            'active'         COLLATE utf8mb4_unicode_ci         AS status,
            CAST(e.event_date AS DATETIME)                      AS activity_time
        FROM events e
        ORDER BY e.event_date DESC
        LIMIT 3
    )
    ORDER BY activity_time DESC
    LIMIT 6
");
$recentActivity = $stmtActivity->fetchAll(PDO::FETCH_ASSOC);

// ── 6. RECENT ANNOUNCEMENTS (active, latest 4) ────────────────
$recentAnn = array_slice($annM->getAll(['status' => 'active']), 0, 4);
if (empty($recentAnn)) {
    $recentAnn = array_slice($annM->getAll(), 0, 4);
}

// ── Encode data for JS chart interactivity ────────────────────
$jsData = json_encode([
    'sparkline' => ['labels' => $sparkLabels, 'values' => $sparkValues],
    'barChart'  => array_map(fn($r) => [
        'label' => $r['service_name'],
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
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
</head>
<body>

<div class="off-layout">

    <?php include __DIR__ . '/../../../components/management/officer/officer_sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="off-content">

    <?php
    $pageTitle      = 'Dashboard';
    $pageBreadcrumb = [['Home', '#'], ['Dashboard', null]];
    $officerName    = $_SESSION['user_name'] ?? 'SK Officer';
    $officerRole    = 'SK Officer';
    $notifCount     = 3;
    include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

        <!-- ── STAT WIDGETS ──────────────────────────────────────── -->
        <section class="off-widgets">

            <!-- Pending Requests -->
            <div class="off-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Pending Requests</span>
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

            <!-- Active Announcements -->
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

            <!-- Available Services -->
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
                    <span class="widget-label">Available Services</span>
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

            <!-- Total Residents -->
            <div class="off-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Total Residents</span>
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

        <!--
        ══════════════════════════════════════════════════════════════
         NEWSPAPER GRID
         Single two-column flow — no separate bottom row.
         Every panel stacks naturally; no height-matching needed.
         Left: Requests → Bar chart → Sparkline
         Right: Quick Actions → Activity → Announcements
        ══════════════════════════════════════════════════════════════
        -->
        <div class="off-lower">

            <!-- ── LEFT COLUMN ───────────────────────────────────── -->
            <div class="off-left-col">

                <!-- PENDING SERVICE REQUESTS TABLE -->
                <section class="off-requests-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Pending Service Requests</h2>
                        <a href="officer_requests.php" class="btn-off-sm">View All &rsaquo;</a>
                    </div>
                    <div class="requests-table-wrap">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Resident</th>
                                    <th>Service</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentRequests)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:28px 12px;
                                            color:var(--off-text-muted); font-size:13px;">
                                            &#10003;&nbsp; No pending requests — you're all caught up!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentRequests as $req): ?>
                                        <tr>
                                            <td>
                                                <div class="req-name">
                                                    <div class="req-avatar">
                                                        <?= htmlspecialchars(dashInitials($req['full_name'])) ?>
                                                    </div>
                                                    <?= htmlspecialchars($req['full_name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= dashCategoryBadge($req['service_category']) ?>
                                                <span style="display:block; font-size:11px;
                                                    color:var(--off-text-muted); margin-top:3px;">
                                                    <?= htmlspecialchars($req['service_name']) ?>
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap; font-size:12px;
                                                color:var(--off-text-muted);">
                                                <?= date('M j, Y', strtotime($req['submitted_at'])) ?>
                                            </td>
                                            <td><?= dashStatusPill($req['status']) ?></td>
                                            <td>
                                                <a href="officer_requests.php?id=<?= (int)$req['id'] ?>"
                                                   class="action-link">Review</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- SERVICE ANALYTICS BAR CHART -->
                <section class="chart-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Requests by Service Type</h2>
                        <span class="chart-period"><?= date('M Y') ?></span>
                    </div>
                    <div class="bar-chart-wrap">
                        <?php if (empty($barData)): ?>
                            <p style="font-size:13px; color:var(--off-text-muted);
                                padding:18px 0; text-align:center;">
                                No service requests this month yet.
                            </p>
                        <?php else: ?>
                            <?php foreach ($barData as $i => $bar):
                                $pct = $barMax > 0 ? round(($bar['cnt'] / $barMax) * 100) : 0;
                                $colorClass = $barColors[$i % count($barColors)];
                            ?>
                                <div class="bar-row">
                                    <span class="bar-label"
                                          title="<?= htmlspecialchars($bar['service_name']) ?>">
                                        <?= htmlspecialchars(
                                            mb_strlen($bar['service_name']) > 22
                                                ? mb_substr($bar['service_name'], 0, 22) . '…'
                                                : $bar['service_name']
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

                <!-- REQUEST VOLUME SPARKLINE CHART -->
                <section class="chart-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Request Volume</h2>
                        <span class="chart-period">Last 6 months</span>
                    </div>
                    <div class="sparkline-wrap">
                        <svg class="sparkline-svg"
                             viewBox="0 0 560 120" preserveAspectRatio="none"
                             aria-label="Request volume over last 6 months">
                            <defs>
                                <linearGradient id="offSparkGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%"   stop-color="#2d7d9a" stop-opacity="0.25"/>
                                    <stop offset="100%" stop-color="#2d7d9a" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <?php if (max($sparkValues) > 0): ?>
                                <path d="<?= htmlspecialchars($sparkAreaPath) ?>"
                                      fill="url(#offSparkGrad)"/>
                                <path d="<?= htmlspecialchars($sparkPath) ?>"
                                      fill="none" stroke="#2d7d9a" stroke-width="2.5"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                                <?php
                                $n2     = count($sparkValues);
                                $xStep2 = $n2 > 1 ? 560 / ($n2 - 1) : 560;
                                foreach ($sparkValues as $pi => $pv):
                                    $px = (int)round($pi * $xStep2);
                                    $py = (int)round(120 - 10 - ($pv / max(max($sparkValues), 1)) * 100);
                                ?>
                                    <circle cx="<?= $px ?>" cy="<?= $py ?>"
                                            r="3.5" fill="#2d7d9a" opacity="0.8"/>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <path d="M0,60 L560,60" fill="none" stroke="var(--off-border)"
                                      stroke-width="2" stroke-dasharray="6,4"/>
                                <text x="280" y="64" text-anchor="middle" fill="var(--off-text-muted)"
                                      font-size="11" font-family="Poppins,sans-serif">
                                    No data yet
                                </text>
                            <?php endif; ?>
                        </svg>
                        <div class="sparkline-labels">
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

            </div><!-- /.off-left-col -->

            <!-- ── RIGHT COLUMN ──────────────────────────────────── -->
            <aside class="off-right-col">

                <!-- QUICK ACTIONS -->
                <section class="quick-actions-panel">
                    <h2 class="section-label">Quick Actions</h2>
                    <div class="quick-actions-grid">
                        <a href="officer_announcements.php" class="quick-action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            New Announcement
                        </a>
                        <a href="officer_requests.php" class="quick-action-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            Manage Requests
                        </a>
                        <a href="officer_services.php" class="quick-action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            Add Service
                        </a>
                        <a href="officer_events.php" class="quick-action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5"/>
                            </svg>
                            Create Event
                        </a>
                    </div>
                </section>

                <!-- RECENT ANNOUNCEMENTS -->
                <section class="chart-panel">
                    <div class="panel-header">
                        <h2 class="section-label">Recent Announcements</h2>
                        <a href="officer_announcements.php" class="btn-off-sm">Manage &rsaquo;</a>
                    </div>
                    <ul class="off-ann-list">
                        <?php if (empty($recentAnn)): ?>
                            <li style="padding:20px 0; text-align:center;
                                font-size:13px; color:var(--off-text-muted);">
                                No announcements published yet.
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

                <!-- RECENT ACTIVITY -->
                <section class="off-activity-panel">
                    <h2 class="section-label">Recent Activity</h2>
                    <div class="activity-feed">
                        <?php if (empty($recentActivity)): ?>
                            <p style="font-size:13px; color:var(--off-text-muted);
                                padding:18px 0; text-align:center;">
                                No recent activity to display.
                            </p>
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
                                        <span><?= htmlspecialchars($time) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                

            </aside>
        </div><!-- /.off-lower -->

    </main>
</div><!-- /.off-layout -->

<!-- Embed data for JS -->
<script>
    window.__dashboardData = <?= $jsData ?>;
</script>
<script src="../../../scripts/management/officer/officer_dashboard.js"></script>

</body>
</html>