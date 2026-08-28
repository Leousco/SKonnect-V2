<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/models/ActivityLogModel.php';

$db        = new Database();
$conn      = $db->getConnection();
$logModel  = new ActivityLogModel($conn);

$filters = [
    'action'       => $_GET['action']       ?? 'all',
    'moderator_id' => $_GET['moderator_id'] ?? 'all',
    'date_from'    => $_GET['date_from']    ?? '',
    'date_to'      => $_GET['date_to']      ?? '',
];

$logs        = $logModel->getLogs($filters);
$stats       = $logModel->getStats();
$moderators  = $logModel->getModerators();

/* ── helpers ── */
function actionBadge(string $action): string
{
    $map = [
        'thread_flagged'   => ['badge-flag',              'flag-icon',     'Flag Thread'],
        'thread_unflagged' => ['badge-unflag',            'unflag-icon',   'Unflag Thread'],
        'thread_removed'   => ['badge-remove',            'remove-icon',   'Remove Thread'],
        'thread_restored'  => ['badge-unlock',            'restore-icon',  'Restore Thread'],
        'thread_pinned'    => ['badge-lock',              'pin-icon',      'Pin Thread'],
        'thread_unpinned'  => ['badge-unflag',            'unpin-icon',    'Unpin Thread'],
        'comment_removed'  => ['badge-remove',            'remove-icon',   'Remove Comment'],
        'warning_issued'   => ['badge-warning',           'warning-icon',  'Warning Issued'],
        'mute_issued'      => ['badge-mute',              'mute-icon',     'User Muted'],
        'ban_issued'       => ['badge-ban',               'ban-icon',      'User Banned'],
        'sanction_cleared' => ['badge-sanction-lifted',   'lifted-icon',   'Sanction Lifted'],
        'report_resolved'  => ['badge-report-reviewed',   'check-icon',    'Report Reviewed'],
        'report_dismissed' => ['badge-report-dismissed',  'dismiss-icon',  'Report Dismissed'],
    ];

    $icons = [
        'flag-icon'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/>',
        'unflag-icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/>',
        'remove-icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>',
        'restore-icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>',
        'pin-icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>',
        'unpin-icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>',
        'warning-icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>',
        'mute-icon'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6 4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z"/>',
        'ban-icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>',
        'lifted-icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>',
        'check-icon'   => '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>',
        'dismiss-icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>',
    ];

    [$cls, $iconKey, $label] = $map[$action] ?? ['badge-unflag', 'dismiss-icon', ucfirst(str_replace('_', ' ', $action))];
    $path = $icons[$iconKey] ?? $icons['dismiss-icon'];

    return '<span class="log-action-badge ' . $cls . '">'
         . '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' . $path . '</svg>'
         . htmlspecialchars($label)
         . '</span>';
}

function targetTypeClass(string $type): string
{
    return match($type) {
        'thread'  => 'type-thread',
        'comment', 'reply' => 'type-comment',
        default   => 'type-user',
    };
}

function modInitials(string $name): string
{
    $parts = explode(' ', trim($name));
    $init  = '';
    foreach ($parts as $p) {
        if ($p !== '') $init .= strtoupper($p[0]);
        if (strlen($init) >= 2) break;
    }
    return $init ?: '??';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Activity Logs — Moderator</title>
    <link rel="stylesheet" href="../../../styles/management/mod_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_activity_logs.css">
</head>
<body>

<div class="mod-layout">

    <?php include __DIR__ . '/../../../components/management/moderator/mod_sidebar.php'; ?>

    <main class="mod-content">

    <?php
    $pageTitle      = 'Activity Logs';
    $pageBreadcrumb = [['Home', '#'], ['System', null], ['Activity Logs', null]];
    $modName        = $_SESSION['user_name'] ?? 'Moderator';
    $modRole        = 'Moderator';
    $notifCount     = 5;
    include __DIR__ . '/../../../components/management/moderator/mod_topbar.php';
    ?>

        <!-- STAT WIDGETS -->
        <section class="mod-widgets">

            <div class="mod-widget-card widget-teal">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Total Actions</span>
                    <p class="widget-number"><?= $stats['total'] ?></p>
                    <span class="widget-trend up">&#9650; All time</span>
                </div>
            </div>

            <div class="mod-widget-card widget-red">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 1.664M21 21l-1.5-1.5m-5.485-1.242L12 17.25 4.5 21V8.742m.164-4.078a2.15 2.15 0 0 1 1.743-1.342 48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185V19.5M4.664 4.664 19.5 19.5"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Reports Handled</span>
                    <p class="widget-number"><?= $stats['reports_handled'] ?></p>
                    <span class="widget-trend neutral">Resolved + dismissed</span>
                </div>
            </div>

            <div class="mod-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Sanctions Issued</span>
                    <p class="widget-number"><?= $stats['sanctions_issued'] ?></p>
                    <span class="widget-trend warning">&#9654; Warnings, mutes &amp; bans</span>
                </div>
            </div>

            <div class="mod-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Threads Hidden</span>
                    <p class="widget-number"><?= $stats['threads_hidden'] ?></p>
                    <span class="widget-trend neutral">Removed from feed</span>
                </div>
            </div>

        </section>

        <!-- FILTERS -->
        <section class="log-filters-bar">

            <div class="log-search-wrap">
                <svg class="log-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" id="log-search" placeholder="Search by target or moderator…" class="log-search-input">
            </div>

            <select id="log-action-type" class="log-select">
                <option value="all">All Action Types</option>
                <optgroup label="Thread Actions">
                    <option value="thread_flagged"   <?= $filters['action'] === 'thread_flagged'   ? 'selected' : '' ?>>Flag Thread</option>
                    <option value="thread_unflagged" <?= $filters['action'] === 'thread_unflagged' ? 'selected' : '' ?>>Unflag Thread</option>
                    <option value="thread_removed"   <?= $filters['action'] === 'thread_removed'   ? 'selected' : '' ?>>Remove Thread</option>
                    <option value="thread_restored"  <?= $filters['action'] === 'thread_restored'  ? 'selected' : '' ?>>Restore Thread</option>
                    <option value="thread_pinned"    <?= $filters['action'] === 'thread_pinned'    ? 'selected' : '' ?>>Pin Thread</option>
                    <option value="thread_unpinned"  <?= $filters['action'] === 'thread_unpinned'  ? 'selected' : '' ?>>Unpin Thread</option>
                    <option value="comment_removed"  <?= $filters['action'] === 'comment_removed'  ? 'selected' : '' ?>>Remove Comment</option>
                </optgroup>
                <optgroup label="Sanctions">
                    <option value="warning_issued"   <?= $filters['action'] === 'warning_issued'   ? 'selected' : '' ?>>Warning Issued</option>
                    <option value="mute_issued"      <?= $filters['action'] === 'mute_issued'      ? 'selected' : '' ?>>User Muted</option>
                    <option value="ban_issued"       <?= $filters['action'] === 'ban_issued'       ? 'selected' : '' ?>>User Banned</option>
                    <option value="sanction_cleared" <?= $filters['action'] === 'sanction_cleared' ? 'selected' : '' ?>>Sanction Lifted</option>
                </optgroup>
                <optgroup label="Reports">
                    <option value="report_resolved"  <?= $filters['action'] === 'report_resolved'  ? 'selected' : '' ?>>Report Reviewed</option>
                    <option value="report_dismissed" <?= $filters['action'] === 'report_dismissed' ? 'selected' : '' ?>>Report Dismissed</option>
                </optgroup>
            </select>

            <select id="log-moderator" class="log-select">
                <option value="all">All Moderators</option>
                <?php foreach ($moderators as $m): ?>
                    <option value="<?= $m['user_id'] ?>"
                        <?= $filters['moderator_id'] == $m['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="log-date-range">
                <div class="log-date-field">
                    <label for="log-date-from" class="log-date-label">From</label>
                    <input type="date" id="log-date-from" class="log-date-input" value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <span class="log-date-sep">—</span>
                <div class="log-date-field">
                    <label for="log-date-to" class="log-date-label">To</label>
                    <input type="date" id="log-date-to" class="log-date-input" value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
            </div>

            <button class="log-clear-btn" id="log-clear-btn" title="Clear all filters">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                Clear
            </button>

        </section>

        <!-- LOG TABLE -->
        <div class="log-table-panel">

            <div class="panel-header">
                <h2 class="section-label">Log Entries</h2>
                <div class="log-table-meta">
                    <span class="log-count" id="log-count">Showing <?= count($logs) ?> entr<?= count($logs) !== 1 ? 'ies' : 'y' ?></span>
                    <button class="btn-mod-sm" id="log-export-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Export PDF
                    </button>
                </div>
            </div>

            <div class="log-table-wrap">
                <table class="log-table" id="log-table">
                    <thead>
                        <tr>
                            <th class="col-id sortable" data-col="id"># <span class="sort-icon">↕</span></th>
                            <th class="col-datetime sortable" data-col="datetime">Date &amp; Time <span class="sort-icon">↕</span></th>
                            <th class="col-moderator sortable" data-col="moderator">Moderator <span class="sort-icon">↕</span></th>
                            <th class="col-action">Action</th>
                            <th class="col-target">Target</th>
                            <th class="col-notes">Notes</th>
                        </tr>
                    </thead>
                    <tbody id="log-tbody">

                        <?php if (empty($logs)): ?>
                        <tr id="log-empty-row">
                            <td colspan="6" style="text-align:center;padding:48px;color:var(--mod-text-muted);">No activity logged yet.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $i => $log):
                            $meta      = json_decode($log['description'] ?? '{}', true) ?: [];
                            $tType     = $meta['target_type'] ?? '';
                            $tName     = $meta['target_name'] ?? '';
                            $tUser     = $meta['target_user'] ?? '';
                            $notes     = $meta['notes']       ?? '';
                            $modName2  = $log['moderator_name'] ?? 'Unknown';
                            $initials  = modInitials($modName2);
                            $dt        = new DateTime($log['created_at']);
                            $isoStr    = $dt->format('Y-m-d\TH:i:s');
                            $dateStr   = $dt->format('M j, Y');
                            $timeStr   = $dt->format('g:i A');
                            $rowNum    = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                        ?>
                        <tr data-action="<?= htmlspecialchars($log['action']) ?>"
                            data-moderator="<?= (int)$log['user_id'] ?>"
                            data-datetime="<?= $isoStr ?>"
                            data-target="<?= htmlspecialchars($tType) ?>">

                            <td class="col-id"><?= $rowNum ?></td>

                            <td class="col-datetime">
                                <span class="log-date"><?= $dateStr ?></span>
                                <span class="log-time"><?= $timeStr ?></span>
                            </td>

                            <td class="col-moderator">
                                <div class="log-mod-cell">
                                    <div class="log-mod-avatar"><?= htmlspecialchars($initials) ?></div>
                                    <span><?= htmlspecialchars($modName2) ?></span>
                                </div>
                            </td>

                            <td class="col-action"><?= actionBadge($log['action']) ?></td>

                            <td class="col-target">
                                <?php if ($tType): ?>
                                    <span class="log-target-type <?= targetTypeClass($tType) ?>"><?= ucfirst($tType) ?></span>
                                <?php endif; ?>
                                <?php if ($tName): ?>
                                    <span class="log-target-name"><?= htmlspecialchars($tName) ?></span>
                                <?php endif; ?>
                                <?php if ($tUser): ?>
                                    <span class="log-target-user">by <?= htmlspecialchars($tUser) ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="col-notes">
                                <span class="log-notes-text"><?= htmlspecialchars($notes) ?></span>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="log-no-results" id="log-no-results" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <p>No log entries match your current filters.</p>
            </div>

        </div>

        <!-- PAGINATION -->
        <section class="mod-pagination">
            <button class="mod-page-btn" id="log-prev-btn" disabled>&#8249; Previous</button>
            <div class="mod-page-numbers" id="log-page-numbers"></div>
            <button class="mod-page-btn" id="log-next-btn" disabled>Next &#8250;</button>
        </section>

    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="../../../scripts/management/moderator/mod_activity_logs.js"></script>

</body>
</html>