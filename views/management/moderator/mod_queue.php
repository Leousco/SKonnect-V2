<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/models/ReportModel.php';

$db          = new Database();
$conn        = $db->getConnection();
$reportModel = new ReportModel($conn);

$reports = $reportModel->getThreadReports();
$counts  = $reportModel->getThreadReportCounts();

// ── Helpers ──────────────────────────────────────────────────────────────────
function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

$category_badge = [
    'harassment'    => ['label' => 'Harassment',    'class' => 'badge-red'],
    'spam'          => ['label' => 'Spam',           'class' => 'badge-orange'],
    'inappropriate' => ['label' => 'Inappropriate',  'class' => 'badge-purple'],
    'misinformation' => ['label' => 'Misinformation', 'class' => 'badge-blue'],
];

$status_badge = [
    'pending'   => ['label' => 'Pending',   'class' => 'status-pending'],
    'reviewed'  => ['label' => 'Reviewed',  'class' => 'status-reviewed'],
    'dismissed' => ['label' => 'Dismissed', 'class' => 'status-dismissed'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Moderation Queue</title>
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_queue.css">
    <link rel="stylesheet" href="../../../styles/management/mod_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_feed.css">
</head>

<body>

    <div class="mod-layout">

        <?php include __DIR__ . '/../../../components/management/moderator/mod_sidebar.php'; ?>

        <main class="mod-content">

            <?php
            $pageTitle      = 'Moderation Queue';
            $pageBreadcrumb = [['Home', '#'], ['Moderation Queue', null]];
            $modName        = $_SESSION['user_name'] ?? 'Moderator';
            $modRole        = 'Moderator';
            $notifCount     = 5;
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
                        <p class="widget-number"><?= $counts['pending'] ?></p>
                        <span class="widget-trend <?= $counts['pending'] > 0 ? 'danger' : 'up' ?>">
                            <?= $counts['pending'] > 0 ? '&#9650; Needs review' : '&#10003; All clear' ?>
                        </span>
                    </div>
                </div>

                <div class="mod-widget-card widget-teal">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Reviewed</span>
                        <p class="widget-number"><?= $counts['reviewed'] ?></p>
                        <span class="widget-trend up">&#10003; Action taken</span>
                    </div>
                </div>

                <div class="mod-widget-card widget-amber">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Harassment</span>
                        <p class="widget-number"><?= $counts['harassment'] ?></p>
                        <span class="widget-trend warning">&#9654; Total reports</span>
                    </div>
                </div>

                <div class="mod-widget-card widget-indigo">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Spam Reports</span>
                        <p class="widget-number"><?= $counts['spam'] ?></p>
                        <span class="widget-trend neutral">&#9654; Total reports</span>
                    </div>
                </div>

            </section>

            <!-- FILTERS BAR -->
            <section class="mq-filters-bar">

                <!-- Row 1: Category filters -->
                <div class="mq-filter-row">
                    <span class="mq-filter-group-label">Category</span>
                    <div class="mq-filters-left">
                        <button class="mq-filter-btn active" data-filter-type="category" data-filter="all">All</button>
                        <button class="mq-filter-btn" data-filter-type="category" data-filter="harassment">Harassment</button>
                        <button class="mq-filter-btn" data-filter-type="category" data-filter="spam">Spam</button>
                        <button class="mq-filter-btn" data-filter-type="category" data-filter="inappropriate">Inappropriate</button>
                        <button class="mq-filter-btn" data-filter-type="category" data-filter="misinformation">Misinformation</button>
                    </div>
                </div>

                <!-- Row 2: Status filters + search + sort -->
                <div class="mq-filter-row mq-filter-row-bottom">
                    <div class="mq-filters-left">
                        <span class="mq-filter-group-label">Status</span>
                        <button class="mq-filter-btn active" data-filter-type="status" data-filter="all">All</button>
                        <button class="mq-filter-btn mq-status-btn status-pending" data-filter-type="status" data-filter="pending">Pending</button>
                        <button class="mq-filter-btn mq-status-btn status-reviewed" data-filter-type="status" data-filter="reviewed">Reviewed</button>
                        <button class="mq-filter-btn mq-status-btn status-dismissed" data-filter-type="status" data-filter="dismissed">Dismissed</button>
                    </div>
                    <div class="mq-filters-right">
                        <div class="mq-search-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input type="text" id="mq-search" class="mq-search-input" placeholder="Search by thread, author…">
                        </div>
                        <select class="mq-select" id="mq-sort">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                        </select>
                    </div>
                </div>

            </section>

            <!-- REPORTS LIST -->
            <section class="mq-panel">

                <div class="panel-header">
                    <h2 class="section-label">Reports Queue</h2>
                    <span class="mq-count-label">Showing <strong id="mq-shown"><?= count($reports) ?></strong> of <strong><?= count($reports) ?></strong> reports</span>
                </div>

                <div class="mq-list" id="mq-list">

                    <?php if (empty($reports)) : ?>
                        <div class="mq-empty" id="mq-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <p>No reports yet. The queue is clear.</p>
                        </div>
                    <?php else : ?>

                        <?php foreach ($reports as $r) :
                            $cat    = $r['category'];
                            $badge  = $category_badge[$cat]  ?? ['label' => ucfirst($cat), 'class' => 'badge-orange'];
                            $sbadge = $status_badge[$r['report_status']] ?? ['label' => ucfirst($r['report_status']), 'class' => 'status-pending'];
                            $note   = htmlspecialchars($r['note'] ?? '');
                            $excerpt = mb_strimwidth(htmlspecialchars($r['thread_message']), 0, 140, '…');
                            $initials = strtoupper(substr($r['thread_author_name'], 0, 1));
                            $is_hidden = (int)$r['thread_is_removed'] === 1;
                        ?>
                            <div class="mq-item" data-category="<?= $cat ?>" data-status="<?= htmlspecialchars($r['report_status']) ?>" data-date="<?= $r['reported_at'] ?>" data-report-id="<?= (int)$r['report_id'] ?>" data-thread-id="<?= (int)$r['thread_id'] ?>" id="mq-item-<?= (int)$r['report_id'] ?>">

                                <!-- Accent bar (CSS colours it via data-category) -->
                                <div class="mq-item-left"></div>

                                <!-- BODY -->
                                <div class="mq-item-body">
                                    <div class="mq-item-header">
                                        <div class="mq-item-title-row">
                                            <!-- Category tag — inline, readable -->
                                            <span class="mq-category-tag <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                                            <span class="mq-item-title">
                                                "<?= htmlspecialchars($r['thread_subject']) ?>"
                                            </span>
                                            <?php if ($is_hidden) : ?>
                                                <span class="mq-hidden-tag">Hidden</span>
                                            <?php endif; ?>
                                            <span class="mq-report-status-badge <?= $sbadge['class'] ?>"><?= $sbadge['label'] ?></span>
                                        </div>
                                        <span class="mq-item-time"><?= time_ago($r['reported_at']) ?></span>
                                    </div>

                                    <div class="mq-item-meta">
                                        <span class="mq-meta-reporter">
                                            <strong>Reported by <?= htmlspecialchars($r['reporter_name']) ?></strong>
                                        </span>
                                        <span class="mq-meta-dot">&middot;</span>
                                        <span class="mq-meta-author">
                                            <strong>Author: <?= htmlspecialchars($r['thread_author_name']) ?></strong>
                                        </span>
                                    </div>

                                    <!-- Thread excerpt -->
                                    <p class="mq-item-excerpt"><?= $excerpt ?></p>

                                    <!-- Reporter's note (if any) -->
                                    <?php if ($note) : ?>
                                        <div class="mq-item-note">
                                            <span class="mq-note-label"></span>
                                            <?= $note ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- ACTION BUTTONS -->
                                    <div class="mq-item-actions">

                                        <!-- View thread (slide-in panel) -->
                                        <button class="mq-action-btn mq-btn-view mq-btn-view-panel" data-thread-id="<?= (int)$r['thread_id'] ?>" data-report-id="<?= (int)$r['report_id'] ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            View Thread
                                        </button>

                                        <?php if ($r['report_status'] === 'pending') : ?>

                                            <!-- Dismiss -->
                                            <button class="mq-action-btn mq-btn-dismiss" data-report-id="<?= (int)$r['report_id'] ?>" data-action="dismiss" title="Mark as dismissed — report was invalid">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                Dismiss
                                            </button>

                                            <!-- Resolve & Notify -->
                                            <button class="mq-action-btn mq-btn-resolve" data-report-id="<?= (int)$r['report_id'] ?>" data-action="resolve" data-thread-subject="<?= htmlspecialchars($r['thread_subject']) ?>" data-category="<?= $cat ?>" title="Hide the thread and notify the author via email">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                                </svg>
                                                Resolve &amp; Notify
                                            </button>

                                        <?php else : ?>
                                            <!-- Already actioned — show a muted label -->
                                            <span class="mq-actioned-label">
                                                <?= $r['report_status'] === 'dismissed' ? 'Dismissed — no action taken' : 'Reviewed — action taken' ?>
                                            </span>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div><!-- /mq-list -->

                <!-- Empty state (shown by JS when filters produce no results) -->
                <div class="mq-empty" id="mq-empty" style="<?= empty($reports) ? 'display:flex' : 'display:none' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p>No reports match your current filter.</p>
                </div>

            </section>

            <!-- PAGINATION -->
            <div class="mq-pagination">
                <button class="mq-page-btn" id="mq-prev" disabled>&#8249; Prev</button>
                <div class="mq-page-numbers" id="mq-page-numbers"></div>
                <button class="mq-page-btn" id="mq-next">Next &#8250;</button>
            </div>

        </main>
    </div>

    <!-- CONFIRM MODAL -->
    <div class="mq-confirm-overlay" id="mq-confirm-overlay" style="display:none;" aria-modal="true" role="dialog">
        <div class="mq-confirm-box">
            <div class="mq-confirm-icon" id="mq-confirm-icon">⚠️</div>
            <h3 class="mq-confirm-title" id="mq-confirm-title">Confirm Action</h3>
            <p class="mq-confirm-body" id="mq-confirm-body">Are you sure you want to perform this action?</p>
            <div class="mq-confirm-footer">
                <button class="mq-confirm-cancel" id="mq-confirm-cancel">Cancel</button>
                <button class="mq-confirm-ok mq-confirm-ok--danger" id="mq-confirm-ok">Confirm</button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="mq-toast" class="mq-toast" aria-live="polite"></div>

    <!-- ══════════════════════════════════════════════════════════
     THREAD SLIDE-IN PANEL (Queue)
══════════════════════════════════════════════════════════ -->
    <div class="mod-panel-backdrop" id="mq-panel-backdrop"></div>

    <aside class="mod-thread-panel" id="mq-thread-panel" aria-label="Thread detail panel">

        <!-- PANEL HEADER -->
        <div class="mod-panel-header">
            <div class="mod-panel-header-left">
                <div class="mod-panel-badges" id="mq-panel-badges"></div>
            </div>
            <button class="mod-panel-close" id="mq-panel-close" aria-label="Close panel">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- PANEL BODY (scrollable) -->
        <div class="mod-panel-body" id="mq-panel-body">

            <!-- Loading state -->
            <div class="mod-panel-loading" id="mq-panel-loading">
                <div class="mod-panel-spinner"></div>
                <span>Loading thread…</span>
            </div>

            <!-- Thread content (injected by JS) -->
            <div id="mq-panel-content" style="display:none;">

                <h2 class="mod-panel-title" id="mq-panel-title"></h2>

                <div class="mod-panel-meta" id="mq-panel-meta"></div>

                <div class="mod-panel-divider"></div>

                <div class="mod-panel-body-text" id="mq-panel-body-text"></div>

                <!-- Attached images -->
                <div class="mod-panel-images" id="mq-panel-images"></div>

                <div class="mod-panel-divider"></div>

                <!-- Report context banner -->
                <div class="mq-panel-report-context" id="mq-panel-report-context"></div>

                <!-- Moderator action strip — Resolve & Notify only -->
                <div class="mod-panel-actions mq-panel-actions-strip" id="mq-panel-actions">
                    <div class="mq-panel-resolve-wrap">
                        <button class="mq-panel-resolve-btn" id="mq-panel-resolve-btn" data-report-id="" data-thread-id="" data-thread-subject="" data-category="">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            Resolve &amp; Notify
                        </button>
                        <p class="mq-panel-resolve-hint">Hides this thread from the resident feed and sends an email notification to the author.</p>
                    </div>
                </div>

                <div class="mod-panel-divider"></div>

                <!-- Comments (read-only view) -->
                <div class="mod-panel-comments-section">
                    <h3 class="mod-panel-comments-heading">
                        Comments
                        <span class="mod-panel-comments-count" id="mq-panel-comments-count">0</span>
                    </h3>
                    <div class="mod-panel-comment-list" id="mq-panel-comment-list"></div>
                </div>

            </div><!-- /#mq-panel-content -->

        </div><!-- /.mod-panel-body -->

    </aside>

    <!-- LIGHTBOX -->
    <div class="mod-lightbox-overlay" id="mq-lightbox" style="display:none;">
        <button class="mod-lightbox-close" id="mq-lightbox-close">&times;</button>
        <img class="mod-lightbox-img" id="mq-lightbox-img" src="" alt="Image preview">
    </div>

    <script src="../../../scripts/management/moderator/mod_queue.js"></script>

</body>

</html>