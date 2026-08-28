<?php
/**
 * backend/routes/officer_analytics_data.php
 * JSON API — provides all officer analytics data.
 * Accepts: ?period=month|quarter|year
 */

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');

require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json; charset=utf-8');
ob_clean();

try {
    $db     = (new Database())->getConnection();
    $period = $_GET['period'] ?? 'month';
    $now    = new DateTime();

    // ── DATE RANGE ────────────────────────────────────────────────
    switch ($period) {
        case 'quarter':
            $q          = (int)ceil((int)$now->format('n') / 3);
            $qStartMonth = (($q - 1) * 3) + 1;
            $qStart     = new DateTime($now->format('Y') . '-' . str_pad($qStartMonth, 2, '0', STR_PAD_LEFT) . '-01');
            $qEnd       = (clone $qStart)->modify('+3 months -1 day');
            $dateStart  = $qStart->format('Y-m-d');
            $dateEnd    = $qEnd->format('Y-m-d');
            $periodLabel = 'Q' . $q . ' ' . $now->format('Y');
            break;
        case 'year':
            $dateStart   = $now->format('Y') . '-01-01';
            $dateEnd     = $now->format('Y') . '-12-31';
            $periodLabel = 'CY ' . $now->format('Y');
            break;
        default:
            $dateStart   = $now->format('Y-m-01');
            $dateEnd     = $now->format('Y-m-t');
            $periodLabel = $now->format('M Y');
            break;
    }

    // ── 1. KPIs ───────────────────────────────────────────────────
    $stmtKpi = $db->prepare("
        SELECT
            COUNT(*)                                                            AS total,
            SUM(status = 'approved')                                            AS approved,
            SUM(status = 'rejected')                                            AS declined,
            SUM(status IN ('approved','rejected'))                              AS decided,
            SUM(status = 'pending')                                             AS pending,
            SUM(status = 'action_required')                                     AS action_required,
            AVG(CASE WHEN status IN ('approved','rejected')
                THEN TIMESTAMPDIFF(HOUR, submitted_at, updated_at) / 24.0
                ELSE NULL END)                                                  AS avg_days
        FROM service_applications
        WHERE DATE(submitted_at) BETWEEN :start AND :end
    ");
    $stmtKpi->execute([':start' => $dateStart, ':end' => $dateEnd]);
    $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);

    $totalRequests   = (int)$kpi['total'];
    $approvedCount   = (int)$kpi['approved'];
    $declinedCount   = (int)$kpi['declined'];
    $decidedCount    = (int)$kpi['decided'];
    $pendingCount    = (int)$kpi['pending'];
    $processingCount = (int)$kpi['action_required'];
    $approvalRate    = $decidedCount > 0 ? round(($approvedCount / $decidedCount) * 100) . '%' : 'N/A';
    $avgDays         = $kpi['avg_days'] !== null ? round((float)$kpi['avg_days'], 1) : 0;

    $annCount = (int)$db->query("SELECT COUNT(*) FROM announcements WHERE status = 'active'")->fetchColumn();

    // ── 2. VOLUME CHART ───────────────────────────────────────────
    $stmtVol = $db->prepare("
        SELECT
            DATE(submitted_at)                              AS d,
            SUM(status = 'approved')                        AS approved,
            SUM(status = 'rejected')                        AS declined,
            SUM(status IN ('pending','action_required'))    AS pending
        FROM service_applications
        WHERE DATE(submitted_at) BETWEEN :start AND :end
        GROUP BY DATE(submitted_at)
        ORDER BY d ASC
    ");
    $stmtVol->execute([':start' => $dateStart, ':end' => $dateEnd]);
    $volRows = [];
    foreach ($stmtVol->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $volRows[$r['d']] = $r;
    }

    $volLabels = $volApproved = $volDeclined = $volPending = [];

    if ($period === 'month') {
        $cursor = new DateTime($dateStart);
        $today  = new DateTime($now->format('Y-m-d'));
        while ($cursor <= $today) {
            $key           = $cursor->format('Y-m-d');
            $volLabels[]   = $cursor->format('M j');
            $volApproved[] = (int)($volRows[$key]['approved'] ?? 0);
            $volDeclined[] = (int)($volRows[$key]['declined'] ?? 0);
            $volPending[]  = (int)($volRows[$key]['pending']  ?? 0);
            $cursor->modify('+1 day');
        }
    } else {
        // Aggregate to monthly buckets
        $monthMap = [];
        foreach ($volRows as $key => $r) {
            $mo = substr($key, 0, 7);
            $monthMap[$mo]['approved'] = ($monthMap[$mo]['approved'] ?? 0) + (int)$r['approved'];
            $monthMap[$mo]['declined'] = ($monthMap[$mo]['declined'] ?? 0) + (int)$r['declined'];
            $monthMap[$mo]['pending']  = ($monthMap[$mo]['pending']  ?? 0) + (int)$r['pending'];
        }

        $months = $period === 'quarter' ? 3 : 12;
        $mStart = new DateTime($dateStart);
        for ($i = 0; $i < $months; $i++) {
            $mo            = (clone $mStart)->modify("+{$i} months");
            $key           = $mo->format('Y-m');
            $volLabels[]   = $mo->format('M');
            $volApproved[] = $monthMap[$key]['approved'] ?? 0;
            $volDeclined[] = $monthMap[$key]['declined'] ?? 0;
            $volPending[]  = $monthMap[$key]['pending']  ?? 0;
        }
    }

    // ── 3. SERVICE BREAKDOWN ──────────────────────────────────────
    $stmtSvcBreak = $db->prepare("
        SELECT sv.name, sv.category, COUNT(*) AS cnt
        FROM service_applications sa
        INNER JOIN services sv ON sv.id = sa.service_id
        WHERE DATE(sa.submitted_at) BETWEEN :start AND :end
        GROUP BY sv.id, sv.name, sv.category
        ORDER BY cnt DESC
        LIMIT 8
    ");
    $stmtSvcBreak->execute([':start' => $dateStart, ':end' => $dateEnd]);
    $serviceBreakdown = array_map(fn($r) => [
        'key'   => $r['category'],
        'label' => $r['name'],
        'count' => (int)$r['cnt'],
    ], $stmtSvcBreak->fetchAll(PDO::FETCH_ASSOC));

    // ── 4. EVENTS ─────────────────────────────────────────────────
    $today        = $now->format('Y-m-d');
    $upcomingEvts = (int)$db->query("SELECT COUNT(*) FROM events WHERE event_date >= '{$today}'")->fetchColumn();
    $pastEvts     = (int)$db->query("SELECT COUNT(*) FROM events WHERE event_date < '{$today}'")->fetchColumn();

    $stmtEvtMo = $db->prepare("
        SELECT MONTH(event_date) AS mo, COUNT(*) AS cnt
        FROM events
        WHERE YEAR(event_date) = :yr
        GROUP BY MONTH(event_date)
    ");
    $stmtEvtMo->execute([':yr' => $now->format('Y')]);
    $evtMoMap = [];
    foreach ($stmtEvtMo->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $evtMoMap[(int)$r['mo']] = (int)$r['cnt'];
    }

    $evLabels = $evCounts = [];
    if ($period === 'month') {
        $evLabels[] = $now->format('M');
        $evCounts[] = $evtMoMap[(int)$now->format('n')] ?? 0;
    } elseif ($period === 'quarter') {
        for ($i = 0; $i < 3; $i++) {
            $mo         = (clone new DateTime($dateStart))->modify("+{$i} months");
            $evLabels[] = $mo->format('M');
            $evCounts[] = $evtMoMap[(int)$mo->format('n')] ?? 0;
        }
    } else {
        for ($m = 1; $m <= 12; $m++) {
            $evLabels[] = date('M', mktime(0, 0, 0, $m, 1));
            $evCounts[] = $evtMoMap[$m] ?? 0;
        }
    }

    // ── 5. ANNOUNCEMENTS ──────────────────────────────────────────
    $annStats = $db->query("
        SELECT
            SUM(status = 'active')   AS published,
            SUM(status = 'draft')    AS drafts,
            SUM(status = 'archived') AS archived
        FROM announcements
    ")->fetch(PDO::FETCH_ASSOC);

    // ── 6. SERVICES LIST ──────────────────────────────────────────
    $stmtSvcList = $db->prepare("
        SELECT sv.name, sv.category, sv.status,
               COUNT(sa.id) AS requests
        FROM services sv
        LEFT JOIN service_applications sa
            ON sa.service_id = sv.id
            AND DATE(sa.submitted_at) BETWEEN :start AND :end
        GROUP BY sv.id, sv.name, sv.category, sv.status
        ORDER BY requests DESC, sv.name ASC
        LIMIT 8
    ");
    $stmtSvcList->execute([':start' => $dateStart, ':end' => $dateEnd]);
    $servicesList = array_map(fn($r) => [
        'name'     => $r['name'],
        'category' => $r['category'],
        'status'   => $r['status'],
        'requests' => (int)$r['requests'],
    ], $stmtSvcList->fetchAll(PDO::FETCH_ASSOC));

    // ── 7. RECENT ACTIVITY ────────────────────────────────────────
    // COLLATE required — application_notes/services are unicode_ci,
    // announcements/events are general_ci.
    $stmtAct = $db->query("
        (
            SELECT
                an.note         COLLATE utf8mb4_unicode_ci AS raw_note,
                sa.full_name    COLLATE utf8mb4_unicode_ci AS subject,
                sa.status       COLLATE utf8mb4_unicode_ci AS app_status,
                sv.name         COLLATE utf8mb4_unicode_ci AS service_name,
                'request'       COLLATE utf8mb4_unicode_ci AS source,
                an.created_at AS act_time
            FROM application_notes an
            INNER JOIN service_applications sa ON sa.id = an.application_id
            INNER JOIN services sv ON sv.id = sa.service_id
            ORDER BY an.created_at DESC
            LIMIT 5
        )
        UNION ALL
        (
            SELECT
                a.title         COLLATE utf8mb4_unicode_ci,
                a.title         COLLATE utf8mb4_unicode_ci,
                a.status        COLLATE utf8mb4_unicode_ci,
                CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci,
                'announcement'  COLLATE utf8mb4_unicode_ci,
                a.published_at
            FROM announcements a
            ORDER BY a.published_at DESC
            LIMIT 3
        )
        UNION ALL
        (
            SELECT
                e.title         COLLATE utf8mb4_unicode_ci,
                e.title         COLLATE utf8mb4_unicode_ci,
                'active'        COLLATE utf8mb4_unicode_ci,
                CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci,
                'event'         COLLATE utf8mb4_unicode_ci,
                CAST(e.event_date AS DATETIME)
            FROM events e
            ORDER BY e.created_at DESC
            LIMIT 3
        )
        ORDER BY act_time DESC
        LIMIT 8
    ");

    $activity = [];
    foreach ($stmtAct->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['source'] === 'request') {
            switch ($row['app_status']) {
                case 'approved':
                    $icon = 'green'; $type = 'approve';
                    $text = 'Approved request from <strong>' . htmlspecialchars($row['subject']) . '</strong> — ' . htmlspecialchars($row['service_name']);
                    break;
                case 'rejected':
                    $icon = 'amber'; $type = 'decline';
                    $text = 'Declined request from <strong>' . htmlspecialchars($row['subject']) . '</strong> — ' . htmlspecialchars($row['service_name']);
                    break;
                case 'action_required':
                    $icon = 'cyan'; $type = 'process';
                    $text = 'Requested additional info from <strong>' . htmlspecialchars($row['subject']) . '</strong> — ' . htmlspecialchars($row['service_name']);
                    break;
                default:
                    $icon = 'slate'; $type = 'process';
                    $text = 'Note added on <strong>' . htmlspecialchars($row['subject']) . '</strong> — ' . htmlspecialchars($row['service_name']);
            }
        } elseif ($row['source'] === 'announcement') {
            $icon = 'cyan'; $type = 'announce';
            $text = 'Announcement: <strong>' . htmlspecialchars($row['subject']) . '</strong>';
        } else {
            $icon = 'slate'; $type = 'event';
            $text = 'Event: <strong>' . htmlspecialchars($row['subject']) . '</strong>';
        }

        $ts = strtotime($row['act_time']);
        $activity[] = [
            'icon' => $icon,
            'type' => $type,
            'text' => $text,
            'time' => $ts ? date('M j, Y · g:i A', $ts) : '—',
        ];
    }

    // ── OUTPUT ────────────────────────────────────────────────────
    echo json_encode([
        'success' => true,
        'period'  => $periodLabel,
        'kpi' => [
            'totalRequests' => $totalRequests,
            'approvalRate'  => $approvalRate,
            'avgDays'       => $avgDays,
            'announcements' => $annCount,
        ],
        'spark' => [
            'pending'    => $pendingCount,
            'processing' => $processingCount,
            'approved'   => $approvedCount,
            'declined'   => $declinedCount,
        ],
        'volume' => [
            'labels'   => $volLabels,
            'approved' => $volApproved,
            'declined' => $volDeclined,
            'pending'  => $volPending,
        ],
        'serviceBreakdown' => $serviceBreakdown,
        'events' => [
            'upcoming'      => $upcomingEvts,
            'past'          => $pastEvts,
            'total'         => $upcomingEvts + $pastEvts,
            'monthlyLabels' => $evLabels,
            'monthlyCounts' => $evCounts,
        ],
        'announcements' => [
            'published' => (int)($annStats['published'] ?? 0),
            'drafts'    => (int)($annStats['drafts']    ?? 0),
            'archived'  => (int)($annStats['archived']  ?? 0),
        ],
        'services' => $servicesList,
        'activity' => $activity,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}