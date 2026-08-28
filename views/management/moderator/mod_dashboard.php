<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

require_once __DIR__ . '/../../../backend/config/database.php';

$db   = new Database();
$conn = $db->getConnection();

$mod_id = (int)($_SESSION['user_id'] ?? 0);

// ── STAT WIDGETS ─────────────────────────────────────────────────────────────

// Pending reports: both thread_reports and comment_reports
$pendingThreadReports = (int)$conn->query(
    "SELECT COUNT(*) FROM thread_reports WHERE status = 'pending'"
)->fetchColumn();

$pendingCommentReports = (int)$conn->query(
    "SELECT COUNT(*) FROM comment_reports WHERE status = 'pending'"
)->fetchColumn();

$pendingReports = $pendingThreadReports + $pendingCommentReports;

// Active threads (not removed)
$activeThreads = (int)$conn->query(
    "SELECT COUNT(*) FROM threads WHERE is_removed = 0"
)->fetchColumn();

// New threads today
$newThreadsToday = (int)$conn->query(
    "SELECT COUNT(*) FROM threads WHERE is_removed = 0 AND DATE(created_at) = CURDATE()"
)->fetchColumn();

// Removed/hidden threads (locked equivalent — moderator-hidden)
$removedThreads = (int)$conn->query(
    "SELECT COUNT(*) FROM threads WHERE is_removed = 1 AND removed_by_user = 0"
)->fetchColumn();

// Warnings issued this month (level = 1 sanctions)
$warningsThisMonth = (int)$conn->query(
    "SELECT COUNT(*) FROM user_sanctions
     WHERE level = 1
       AND MONTH(created_at) = MONTH(CURDATE())
       AND YEAR(created_at)  = YEAR(CURDATE())"
)->fetchColumn();

// Total active sanctions (any level)
$activeSanctions = (int)$conn->query(
    "SELECT COUNT(*) FROM user_sanctions WHERE is_active = 1"
)->fetchColumn();

// ── PENDING REPORTS TABLE (up to 5, thread + comment mixed, newest first) ────

$stmt = $conn->query(
    "SELECT
        tr.id          AS report_id,
        'thread'       AS content_type,
        tr.category,
        tr.created_at,
        t.subject      AS content_label,
        t.id           AS ref_id,
        CONCAT(ru.first_name, ' ', ru.last_name) AS reporter_name
     FROM thread_reports tr
     JOIN threads t   ON t.id  = tr.thread_id
     JOIN users   ru  ON ru.id = tr.reporter_id
     WHERE tr.status = 'pending'

     UNION ALL

     SELECT
        cr.id          AS report_id,
        cr.target_type AS content_type,
        cr.category,
        cr.created_at,
        CONCAT(cr.target_type, ': \"',
            LEFT(COALESCE(tc.message, rep.message), 55),
            CASE WHEN LENGTH(COALESCE(tc.message, rep.message)) > 55 THEN '…' ELSE '' END,
            '\"')       AS content_label,
        COALESCE(tc.thread_id, rep_tc.thread_id) AS ref_id,
        CONCAT(ru.first_name, ' ', ru.last_name) AS reporter_name
     FROM comment_reports cr
     LEFT JOIN thread_comments tc   ON cr.target_type = 'comment' AND tc.id   = cr.target_id
     LEFT JOIN comment_replies rep  ON cr.target_type = 'reply'   AND rep.id  = cr.target_id
     LEFT JOIN thread_comments rep_tc ON cr.target_type = 'reply' AND rep_tc.id = rep.comment_id
     JOIN users ru ON ru.id = cr.reporter_id
     WHERE cr.status = 'pending'

     ORDER BY created_at DESC
     LIMIT 5"
);
$pendingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── REPORTS BY REASON (all-time, both tables) ─────────────────────────────────

$stmt = $conn->query(
    "SELECT category, COUNT(*) AS cnt
     FROM (
         SELECT category FROM thread_reports
         UNION ALL
         SELECT category FROM comment_reports
     ) combined
     GROUP BY category
     ORDER BY cnt DESC"
);
$reportsByReason = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$reasonOrder  = ['harassment', 'spam', 'inappropriate', 'misinformation'];
$reasonColors = [
    'harassment'    => ['bar' => 'bar-red',    'badge' => 'badge-red'],
    'spam'          => ['bar' => 'bar-orange',  'badge' => 'badge-orange'],
    'inappropriate' => ['bar' => 'bar-teal',    'badge' => 'badge-red'],
    'misinformation' => ['bar' => 'bar-indigo',  'badge' => 'badge-orange'],
];
$maxReasonCount = max(array_values($reportsByReason) ?: [1]);

// ── MODERATOR ACTIVITY LOG (this mod, last 6 entries) ────────────────────────
// Uses the activity_logs table (action + description) filtered to the current mod
$stmt = $conn->prepare(
    "SELECT action, description, created_at
     FROM activity_logs
     WHERE user_id = :uid
     ORDER BY created_at DESC
     LIMIT 6"
);
$stmt->execute([':uid' => $mod_id]);
$activityLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── THREAD ACTIVITY SPARKLINE: threads created per month, last 6 months ──────

$stmt = $conn->query(
    "SELECT
        DATE_FORMAT(created_at, '%b') AS month_label,
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        COUNT(*) AS cnt
     FROM threads
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month_key, month_label
     ORDER BY month_key ASC
     LIMIT 6"
);
$sparkRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure exactly 6 slots (fill missing months with 0)
$sparkMonths = [];
for ($i = 5; $i >= 0; $i--) {
    $key   = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $sparkMonths[$key] = ['label' => $label, 'cnt' => 0];
}
foreach ($sparkRaw as $row) {
    if (isset($sparkMonths[$row['month_key']])) {
        $sparkMonths[$row['month_key']]['cnt'] = (int)$row['cnt'];
    }
}
$sparkData   = array_values($sparkMonths);
$sparkMax    = max(array_column($sparkData, 'cnt') ?: [1]);
$sparkMax    = max($sparkMax, 1);

// Build SVG polyline points (viewBox 560×120, y-axis inverted, 10px padding top/bottom)
$svgPoints = [];
$svgFill   = [];
$svgW = 560;
$svgH = 120;
$pad = 10;
foreach ($sparkData as $i => $s) {
    $x = $i === 0 ? 0 : round(($i / (count($sparkData) - 1)) * $svgW);
    $y = $svgH - $pad - round((($s['cnt'] / $sparkMax) * ($svgH - 2 * $pad)));
    $svgPoints[] = "{$x},{$y}";
    $svgFill[]   = "{$x},{$y}";
}
$svgFill[] = "{$svgW},{$svgH}";
$svgFill[] = "0,{$svgH}";
$polyline  = implode(' L', $svgPoints);
$areafill  = implode(' L', $svgFill);

// Stats for sparkline footer
$resolvedReports = (int)$conn->query(
    "SELECT COUNT(*) FROM (
        SELECT id FROM thread_reports  WHERE status = 'reviewed'
        UNION ALL
        SELECT id FROM comment_reports WHERE status = 'reviewed'
     ) r"
)->fetchColumn();

$removedThisMonth = (int)$conn->query(
    "SELECT COUNT(*) FROM threads
     WHERE is_removed = 1 AND removed_by_user = 0
       AND MONTH(updated_at) = MONTH(CURDATE())
       AND YEAR(updated_at)  = YEAR(CURDATE())"
)->fetchColumn();

// ── RECENT COMMUNITY POSTS (5 most recent active threads) ────────────────────

$stmt = $conn->query(
    "SELECT
        t.id,
        t.subject,
        t.created_at,
        CONCAT(u.first_name, ' ', u.last_name) AS author_name,
        (SELECT COUNT(*) FROM thread_comments tc WHERE tc.thread_id = t.id AND tc.is_removed = 0) AS comment_count
     FROM threads t
     JOIN users u ON u.id = t.author_id
     WHERE t.is_removed = 0
     ORDER BY t.created_at DESC
     LIMIT 5"
);
$recentThreads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── UNREAD NOTIFICATIONS for topbar ──────────────────────────────────────────
$notifCount = (int)$conn->prepare(
    "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0 AND is_dismissed = 0"
)->execute([':uid' => $mod_id]) ? (function () use ($conn, $mod_id) {
    $s = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0 AND is_dismissed = 0");
    $s->execute([':uid' => $mod_id]);
    return (int)$s->fetchColumn();
})() : 0;

// ── HELPERS ──────────────────────────────────────────────────────────────────

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 172800) return 'Yesterday';
    return date('M j, Y', strtotime($datetime));
}

function activityIconClass(string $action): string
{
    $map = [
        'thread_removed'   => 'icon-red',
        'thread_restored'  => 'icon-green',
        'thread_flagged'   => 'icon-amber',
        'thread_unflagged' => 'icon-teal',
        'thread_pinned'    => 'icon-teal',
        'thread_unpinned'  => 'icon-teal',
        'report_dismissed' => 'icon-teal',
        'report_resolved'  => 'icon-green',
        'warning_issued'   => 'icon-amber',
        'mute_issued'      => 'icon-amber',
        'ban_issued'       => 'icon-red',
        'deleted'          => 'icon-red',
        'flagged'          => 'icon-amber',
        'approved'         => 'icon-green',
    ];
    foreach ($map as $key => $class) {
        if (str_contains($action, $key) || $action === $key) return $class;
    }
    return 'icon-teal';
}

function activitySvg(string $action): string
{
    if (str_contains($action, 'ban') || str_contains($action, 'removed') || str_contains($action, 'deleted')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>';
    }
    if (str_contains($action, 'warning') || str_contains($action, 'mute') || str_contains($action, 'flagged')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>';
    }
    if (str_contains($action, 'resolved') || str_contains($action, 'restored') || str_contains($action, 'approved')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>';
}

function activityLabel(string $action, string $rawDescription): string
{
    $d = json_decode($rawDescription, true);
    if (!is_array($d)) {
        // Plain-text description — return as-is
        return htmlspecialchars($rawDescription);
    }

    $name    = isset($d['target_name']) ? '<strong>' . htmlspecialchars($d['target_name']) . '</strong>' : '';
    $user    = isset($d['target_user']) && $d['target_user'] !== ''
        ? '<strong>' . htmlspecialchars($d['target_user']) . '</strong>' : '';
    $type    = $d['target_type'] ?? '';

    switch ($action) {
        case 'thread_flagged':
            return "Flagged {$type} {$name}" . ($user ? " by {$user}" : '') . " for review.";
        case 'thread_unflagged':
            return "Removed flag from {$type} {$name}.";
        case 'thread_removed':
            return "Hidden {$type} {$name}" . ($user ? " (by {$user})" : '') . " from residents.";
        case 'thread_restored':
            return "Restored {$type} {$name}" . ($user ? " by {$user}" : '') . " to the feed.";
        case 'thread_pinned':
            return "Pinned {$type} {$name}" . ($user ? " by {$user}" : '') . " to top of feed.";
        case 'thread_unpinned':
            return "Unpinned {$type} {$name}" . ($user ? " by {$user}" : '') . ".";
        case 'report_dismissed':
            return "Dismissed report on " . ($user ? "{$user}'s " : '') . "{$type} {$name}.";
        case 'report_resolved':
            return "Resolved report — " . ($user ? "{$user}'s " : '') . "{$type} {$name} hidden.";
        case 'warning_issued':
            return "Issued a warning to {$user}" . ($name ? " regarding {$name}" : '') . ".";
        case 'mute_issued':
            return "Issued a 7-day mute to {$user}" . ($name ? " regarding {$name}" : '') . ".";
        case 'ban_issued':
            return "Issued a permanent ban to {$user}" . ($name ? " regarding {$name}" : '') . ".";
        default:
            // Fall back to the 'notes' field if present, otherwise humanise the action key
            if (!empty($d['notes'])) return htmlspecialchars($d['notes']);
            return htmlspecialchars(ucfirst(str_replace('_', ' ', $action)) . '.');
    }
}

function badgeClass(string $category): string
{
    return in_array($category, ['harassment', 'inappropriate']) ? 'badge-red' : 'badge-orange';
}

function initials(string $name): string
{
    $parts = explode(' ', trim($name));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(substr(end($parts), 0, 1));
    return $i;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Moderator Dashboard</title>
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_dashboard.css">
    <link rel="stylesheet" href="../../../styles/management/mod_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_topbar.css">
</head>

<body>

    <div class="mod-layout">

        <?php include __DIR__ . '/../../../components/management/moderator/mod_sidebar.php'; ?>

        <main class="mod-content">

            <?php
            $pageTitle      = 'Dashboard';
            $pageBreadcrumb = [['Home', '#'], ['Dashboard', null]];
            $modName        = $_SESSION['user_name'] ?? 'Moderator';
            $modRole        = 'Moderator';
            include __DIR__ . '/../../../components/management/moderator/mod_topbar.php';
            ?>

            <!-- STAT WIDGETS -->
            <section class="mod-widgets">

                <div class="mod-widget-card widget-red">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 1.664M21 21l-1.5-1.5m-5.485-1.242L12 17.25 4.5 21V8.742m.164-4.078a2.15 2.15 0 0 1 1.743-1.342 48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185V19.5M4.664 4.664 19.5 19.5" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Pending Reports</span>
                        <p class="widget-number"><?= $pendingReports ?></p>
                        <?php if ($pendingReports > 0) : ?>
                            <span class="widget-trend danger">&#9650; Needs review</span>
                        <?php else : ?>
                            <span class="widget-trend neutral">All clear</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mod-widget-card widget-teal">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Active Threads</span>
                        <p class="widget-number"><?= $activeThreads ?></p>
                        <?php if ($newThreadsToday > 0) : ?>
                            <span class="widget-trend up">&#9650; <?= $newThreadsToday ?> new today</span>
                        <?php else : ?>
                            <span class="widget-trend neutral">None new today</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mod-widget-card widget-amber">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Removed Threads</span>
                        <p class="widget-number"><?= $removedThreads ?></p>
                        <span class="widget-trend neutral">Mod-hidden</span>
                    </div>
                </div>

                <div class="mod-widget-card widget-indigo">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Warnings Issued</span>
                        <p class="widget-number"><?= $warningsThisMonth ?></p>
                        <span class="widget-trend warning">&#9654; This month</span>
                    </div>
                </div>

            </section>

            <div class="mod-lower">

                <!-- COL A — PENDING REPORTS TABLE -->
                <section class="mod-reports-panel mod-col-reports">
                    <div class="panel-header">
                        <h2 class="section-label">Pending Reports</h2>
                        <a href="mod_queue.php" class="btn-mod-sm">View All &rsaquo;</a>
                    </div>
                    <div class="requests-table-wrap">
                        <table class="requests-table">
                            <thead>
                                <tr>
                                    <th>Reporter</th>
                                    <th>Content</th>
                                    <th>Reason</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingRows)) : ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:var(--mod-text-muted); padding:24px;">
                                            No pending reports — queue is clear.
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($pendingRows as $row) : ?>
                                        <tr>
                                            <td>
                                                <div class="req-name">
                                                    <div class="req-avatar"><?= htmlspecialchars(initials($row['reporter_name'])) ?></div>
                                                    <?= htmlspecialchars($row['reporter_name']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($row['content_label']) ?></td>
                                            <td>
                                                <span class="req-badge <?= badgeClass($row['category']) ?>">
                                                    <?= ucfirst(htmlspecialchars($row['category'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <?php if ($row['content_type'] === 'thread') : ?>
                                                    <a href="mod_queue.php?id=<?= $row['report_id'] ?>" class="action-link">Review</a>
                                                <?php else : ?>
                                                    <a href="mod_reports.php?id=<?= $row['report_id'] ?>" class="action-link">Review</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- COL B — QUICK ACTIONS + REPORTS BY REASON -->
                <div class="mod-col-mid">

                    <section class="quick-actions-panel">
                        <h2 class="section-label">Quick Actions</h2>
                        <div class="quick-actions-grid">
                            <a href="mod_queue.php" class="quick-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 1.664M21 21l-1.5-1.5m-5.485-1.242L12 17.25 4.5 21V8.742m.164-4.078a2.15 2.15 0 0 1 1.743-1.342 48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185V19.5M4.664 4.664 19.5 19.5" />
                                </svg>
                                Review Reports
                            </a>
                            <a href="mod_feed.php" class="quick-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                                </svg>
                                Browse Threads
                            </a>
                            <a href="mod_sanctions.php" class="quick-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                Issue Warning
                            </a>
                            <a href="mod_activity_logs.php" class="quick-action-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="18" y1="20" x2="18" y2="10" />
                                    <line x1="12" y1="20" x2="12" y2="4" />
                                    <line x1="6" y1="20" x2="6" y2="14" />
                                </svg> 
                                View Logs
                            </a>
                        </div>
                    </section>

                    <section class="chart-panel chart-panel--fill">
                        <div class="panel-header">
                            <h2 class="section-label">Reports by Reason</h2>
                            <span class="chart-period">All time</span>
                        </div>
                        <div class="bar-chart-wrap">
                            <?php
                            $totalReasonCount = array_sum($reportsByReason) ?: 1;
                            foreach ($reasonOrder as $reason) :
                                $cnt   = (int)($reportsByReason[$reason] ?? 0);
                                $pct   = $maxReasonCount > 0 ? round(($cnt / $maxReasonCount) * 100) : 0;
                                $color = $reasonColors[$reason]['bar'] ?? 'bar-muted';
                            ?>
                                <div class="bar-row">
                                    <span class="bar-label"><?= ucfirst($reason) ?></span>
                                    <div class="bar-track">
                                        <div class="bar-fill <?= $color ?>" style="width:<?= $pct ?>%"></div>
                                    </div>
                                    <span class="bar-count"><?= $cnt ?></span>
                                </div>
                            <?php endforeach;

                            // "Others" = categories not in the main four
                            $knownTotal  = array_sum(array_intersect_key($reportsByReason, array_flip($reasonOrder)));
                            $othersCount = array_sum($reportsByReason) - $knownTotal;
                            $othersPct   = $maxReasonCount > 0 ? round(($othersCount / $maxReasonCount) * 100) : 0;
                            ?>
                            <div class="bar-row">
                                <span class="bar-label">Others</span>
                                <div class="bar-track">
                                    <div class="bar-fill bar-muted" style="width:<?= $othersPct ?>%"></div>
                                </div>
                                <span class="bar-count"><?= $othersCount ?></span>
                            </div>
                        </div>
                    </section>

                </div>

                <!-- COL C — ACTIVITY LOG -->
                <aside class="mod-col-activity">

                    <section class="mod-activity-panel mod-activity-panel--full">
                        <h2 class="section-label">Moderator Activity Log</h2>
                        <div class="activity-feed">
                            <?php if (empty($activityLog)) : ?>
                                <p style="font-size:13px; color:var(--mod-text-muted); padding:12px 0;">No recent activity recorded.</p>
                            <?php else : ?>
                                <?php foreach ($activityLog as $entry) : ?>
                                    <div class="activity-entry">
                                        <div class="activity-icon <?= activityIconClass($entry['action']) ?>">
                                            <?= activitySvg($entry['action']) ?>
                                        </div>
                                        <div class="activity-info">
                                            <p><?= activityLabel($entry['action'], $entry['description']) ?></p>
                                            <span><?= date('M j, Y · g:i A', strtotime($entry['created_at'])) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                </aside>
            </div>

            <div class="mod-bottom-row">

                <!-- THREAD ACTIVITY CHART -->
                <section class="chart-panel chart-panel--stretch">
                    <div class="panel-header">
                        <h2 class="section-label">Thread Activity</h2>
                        <span class="chart-period">Last 6 months</span>
                    </div>
                    <div class="sparkline-wrap sparkline-wrap--grow">
                        <svg class="sparkline-svg sparkline-svg--tall" viewBox="0 0 <?= $svgW ?> <?= $svgH ?>" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="modSparkGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#0d9488" stop-opacity="0.25" />
                                    <stop offset="100%" stop-color="#0d9488" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="M<?= $polyline ?>" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M<?= $areafill ?> Z" fill="url(#modSparkGrad)" />
                        </svg>
                        <div class="sparkline-labels">
                            <?php foreach ($sparkData as $s) : ?>
                                <span><?= htmlspecialchars($s['label']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="sparkline-stats sparkline-stats--quad">
                            <div class="spark-stat">
                                <span class="spark-val"><?= $newThreadsToday > 0 ? '+' . $newThreadsToday : '0' ?></span>
                                <span class="spark-lbl">New today</span>
                            </div>
                            <div class="spark-stat">
                                <span class="spark-val"><?= $activeThreads ?></span>
                                <span class="spark-lbl">Active threads</span>
                            </div>
                            <div class="spark-stat">
                                <span class="spark-val"><?= $resolvedReports ?></span>
                                <span class="spark-lbl">Reports resolved</span>
                            </div>
                            <div class="spark-stat">
                                <span class="spark-val"><?= $removedThisMonth ?></span>
                                <span class="spark-lbl">Removed this month</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- RECENT COMMUNITY POSTS -->
                <section class="chart-panel chart-panel--stretch">
                    <div class="panel-header">
                        <h2 class="section-label">Recent Community Posts</h2>
                        <a href="mod_feed.php" class="btn-mod-sm">Manage &rsaquo;</a>
                    </div>
                    <ul class="mod-threads-list mod-threads-list--grow">
                        <?php if (empty($recentThreads)) : ?>
                            <li style="font-size:13px; color:var(--mod-text-muted); padding:16px 0;">No threads yet.</li>
                        <?php else : ?>
                            <?php foreach ($recentThreads as $thread) : ?>
                                <li class="mod-thread-item">
                                    <div class="thread-meta-badge">
                                        <span class="thread-replies"><?= (int)$thread['comment_count'] ?></span>
                                        <span class="thread-replies-lbl">replies</span>
                                    </div>
                                    <div class="thread-info">
                                        <strong><?= htmlspecialchars($thread['subject']) ?></strong>
                                        <span>Posted by <?= htmlspecialchars($thread['author_name']) ?> &middot; <?= timeAgo($thread['created_at']) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </section>

            </div>

        </main>
    </div>

    <script src="../../../scripts/management/moderator/mod_dashboard.js"></script>

</body>

</html>