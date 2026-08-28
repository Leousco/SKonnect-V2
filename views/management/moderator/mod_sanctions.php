<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('moderator');

require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/models/CommentReportModel.php';
require_once __DIR__ . '/../../../backend/models/SanctionModel.php';

$db            = new Database();
$conn          = $db->getConnection();
$reportModel   = new CommentReportModel($conn);
$sanctionModel = new SanctionModel($conn);

$pendingReports   = $reportModel->getReports('pending');
$dismissedReports = $reportModel->getReports('dismissed');
$reviewedReports  = $reportModel->getReports('reviewed');

$stats = $sanctionModel->getStats();

$cat_labels = [
    'inappropriate'  => 'Inappropriate',
    'spam'           => 'Spam',
    'misinformation' => 'Misinformation',
    'harassment'     => 'Harassment',
];

$mod_id = (int)($_SESSION['user_id'] ?? 0);

function initials(string $name): string
{
    $parts = explode(' ', trim($name));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(substr(end($parts), 0, 1));
    return $i;
}

function reltime(string $dt): string
{
    $diff = time() - strtotime($dt);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | User Sanctions</title>
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_sanctions.css">
    <link rel="stylesheet" href="../../../styles/management/mod_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/moderator/mod_topbar.css">
</head>

<body>

    <div class="mod-layout">

        <?php include __DIR__ . '/../../../components/management/moderator/mod_sidebar.php'; ?>

        <main class="mod-content">

            <?php
            $pageTitle      = 'User Sanctions';
            $pageBreadcrumb = [['Home', '#'], ['User Sanctions', null]];
            $modName        = $_SESSION['user_name'] ?? 'Moderator';
            $modRole        = 'Moderator';
            $notifCount     = 5;
            include __DIR__ . '/../../../components/management/moderator/mod_topbar.php';
            ?>

            <!-- ── STAT WIDGETS ──────────────────────────────────────── -->
            <section class="mod-widgets">

                <div class="mod-widget-card widget-amber">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Pending Reports</span>
                        <p class="widget-number"><?= count($pendingReports) ?></p>
                        <span class="widget-trend warning">&#9654; Needs review</span>
                    </div>
                </div>

                <div class="mod-widget-card widget-red">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Active Bans</span>
                        <p class="widget-number"><?= (int)$stats['level2'] + (int)$stats['level3'] ?></p>
                        <span class="widget-trend danger">&#9650; Restricted users</span>
                    </div>
                </div>

                <div class="mod-widget-card widget-indigo">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Total Sanctions</span>
                        <p class="widget-number"><?= (int)$stats['total_active'] ?></p>
                        <span class="widget-trend neutral">&#9654; All time active</span>
                    </div>
                </div>

                <div class="mod-widget-card widget-teal">
                    <div class="widget-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <span class="widget-label">Issued Today</span>
                        <p class="widget-number"><?= (int)$stats['today'] ?></p>
                        <span class="widget-trend up">&#9654; This session</span>
                    </div>
                </div>

            </section>

            <!-- ── ISSUE DIRECT SANCTION FORM ───────────────────────── -->
            <section class="ms-form-panel">
                <div class="panel-header">
                    <div class="panel-header-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="panel-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <h2 class="section-label">Issue Direct Sanction</h2>
                    </div>
                    <button class="btn-mod-sm" id="ms-form-toggle">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        Collapse
                    </button>
                </div>

                <div class="ms-form-body" id="ms-form-body">
                    <p class="ms-form-hint">Use this form to issue a sanction directly to a user by their User ID. For report-based sanctions, use the <strong>Issue Sanction</strong> button on a report below.</p>
                    <div class="ms-form-grid">
                        <div class="ms-field-group">
                            <label class="ms-label" for="ms-user-id">User ID</label>
                            <input type="number" class="ms-input" id="ms-user-id" placeholder="e.g. 18" min="1">
                        </div>
                        <div class="ms-field-group">
                            <label class="ms-label" for="ms-level">Sanction Level</label>
                            <select class="ms-select" id="ms-level">
                                <option value="1">Level 1 — Warning (email only)</option>
                                <option value="2">Level 2 — 7-Day Posting Ban</option>
                                <option value="3">Level 3 — Permanent Feed Ban</option>
                            </select>
                        </div>
                        <div class="ms-field-group ms-field-full">
                            <label class="ms-label" for="ms-reason">Reason <span class="ms-label-optional">(optional)</span></label>
                            <textarea class="ms-input ms-textarea" id="ms-reason" placeholder="Describe the violation and context…"></textarea>
                        </div>
                    </div>
                    <div class="ms-form-actions">
                        <button type="button" class="ms-submit-btn" id="ms-submit">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                            Issue Sanction
                        </button>
                        <button type="button" class="ms-cancel-btn" id="ms-cancel">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            <!-- ── REPORT TABS ────────────────────────────────────────── -->
            <section class="ms-list-panel">

                <div class="panel-header">
                    <div class="panel-header-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="panel-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                        </svg>
                        <h2 class="section-label">Comment / Reply Reports</h2>
                    </div>
                </div>

                <!-- Tab nav -->
                <div class="ms-tab-nav">
                    <button class="ms-tab-btn active" data-tab="pending">
                        Pending
                        <?php if (count($pendingReports) > 0) : ?>
                            <span class="ms-tab-badge"><?= count($pendingReports) ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="ms-tab-btn" data-tab="reviewed">
                        Reviewed
                        <?php if (count($reviewedReports) > 0) : ?>
                            <span class="ms-tab-badge ms-tab-badge--reviewed"><?= count($reviewedReports) ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="ms-tab-btn" data-tab="dismissed">
                        Dismissed
                        <?php if (count($dismissedReports) > 0) : ?>
                            <span class="ms-tab-badge ms-tab-badge--dismissed"><?= count($dismissedReports) ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- Filter bar -->
                <div class="ms-filter-bar">
                    <div class="ms-filters-left">
                        <button class="ms-filter-btn active" data-filter="all">All Reports</button>
                        <button class="ms-filter-btn" data-filter="harassment">Harassment</button>
                        <button class="ms-filter-btn" data-filter="spam">Spam</button>
                        <button class="ms-filter-btn" data-filter="inappropriate">Inappropriate</button>
                        <button class="ms-filter-btn" data-filter="misinformation">Misinformation</button>
                    </div>
                    <div class="ms-filters-right">
                        <div class="ms-search-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input type="text" class="ms-search-input" id="ms-search" placeholder="Search by user or content…">
                        </div>
                    </div>
                </div>

                <!-- ── PENDING TAB ──────────────────────────────────── -->
                <div class="ms-tab-panel" id="tab-pending">

                    <?php if (empty($pendingReports)) : ?>
                        <div class="ms-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <p>No pending reports — all clear!</p>
                        </div>
                    <?php else : ?>
                        <div class="ms-list" id="ms-list-pending">
                            <?php foreach ($pendingReports as $r) : ?>
                                <?php
                                $lvl      = (int)$r['author_sanction_level'];
                                $nextLvl  = min($lvl + 1, 3);
                                $catKey   = $r['category'];
                                $catLabel = $cat_labels[$catKey] ?? ucfirst($catKey);
                                $init     = initials($r['author_name'] ?? 'U');
                                $snippet  = mb_substr($r['content_message'] ?? '', 0, 180);
                                ?>
                                <div class="ms-item" data-category="<?= htmlspecialchars($catKey) ?>" data-report-id="<?= (int)$r['report_id'] ?>" data-thread-id="<?= (int)($r['thread_id'] ?? 0) ?>" data-target-type="<?= htmlspecialchars($r['target_type']) ?>" data-target-id="<?= (int)$r['target_id'] ?>">
                                    <div class="ms-item-left">
                                        <?php if ($lvl > 0) : ?>
                                            <div class="ms-sanction-level level-<?= $lvl ?>" title="Current sanction level">Lvl <?= $lvl ?></div>
                                        <?php else : ?>
                                            <div class="ms-sanction-level level-0" title="No active sanctions">Clean</div>
                                        <?php endif; ?>
                                        <div class="ms-avatar"><?= $init ?></div>
                                    </div>
                                    <div class="ms-item-body">
                                        <div class="ms-item-header">
                                            <div class="ms-item-user">
                                                <span class="ms-username"><?= htmlspecialchars($r['author_name'] ?? 'Unknown') ?></span>
                                                <span class="ms-report-cat cat-<?= $catKey ?>"><?= $catLabel ?></span>
                                                <span class="ms-content-type"><?= ucfirst($r['target_type']) ?></span>
                                            </div>
                                            <span class="ms-item-time"><?= reltime($r['created_at']) ?> · Reported by <?= htmlspecialchars($r['reporter_name'] ?? 'User') ?></span>
                                        </div>

                                        <div class="ms-reported-content">
                                            <span class="ms-reported-label">Reported <?= $r['target_type'] ?>:</span>
                                            <p class="ms-reported-text"><?= htmlspecialchars($snippet) ?><?= mb_strlen($r['content_message'] ?? '') > 180 ? '…' : '' ?></p>
                                        </div>

                                        <div class="ms-thread-context">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                            </svg>
                                            <span>Thread: <strong><?= htmlspecialchars($r['thread_subject'] ?? '—') ?></strong></span>
                                        </div>

                                        <?php if (!empty($r['note'])) : ?>
                                            <div class="ms-reporter-note">
                                                <span class="ms-reported-label">Reporter note:</span>
                                                <span><?= htmlspecialchars($r['note']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="ms-item-actions">
                                            <button class="ms-action-btn ms-btn-view" data-report-id="<?= (int)$r['report_id'] ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                View Comment
                                            </button>

                                            <button class="ms-action-btn ms-btn-dismiss" data-report-id="<?= (int)$r['report_id'] ?>" data-action="dismiss">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                Dismiss
                                            </button>

                                            <button class="ms-action-btn ms-btn-sanction" data-report-id="<?= (int)$r['report_id'] ?>" data-user-id="<?= (int)$r['author_id'] ?>" data-author="<?= htmlspecialchars($r['author_name'] ?? '') ?>" data-current-level="<?= $lvl ?>" data-next-level="<?= $nextLvl ?>" data-category="<?= htmlspecialchars($catLabel) ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                </svg>
                                                Issue Sanction
                                                <?php if ($nextLvl > 0) : ?>
                                                    <span class="ms-next-level-hint">(Lvl <?= $nextLvl ?>)</span>
                                                <?php endif; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="ms-empty" id="ms-empty-pending" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p>No reports match your current filter.</p>
                    </div>
                </div>

                <!-- ── REVIEWED TAB ─────────────────────────────────── -->
                <div class="ms-tab-panel" id="tab-reviewed" style="display:none;">
                    <?php if (empty($reviewedReports)) : ?>
                        <div class="ms-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <p>No reviewed reports yet.</p>
                        </div>
                    <?php else : ?>
                        <div class="ms-list" id="ms-list-reviewed">
                            <?php foreach ($reviewedReports as $r) : ?>
                                <?php
                                $lvl      = (int)$r['author_sanction_level'];
                                $catKey   = $r['category'];
                                $catLabel = $cat_labels[$catKey] ?? ucfirst($catKey);
                                $init     = initials($r['author_name'] ?? 'U');
                                $snippet  = mb_substr($r['content_message'] ?? '', 0, 180);
                                ?>
                                <div class="ms-item ms-item--reviewed" data-category="<?= htmlspecialchars($catKey) ?>" data-report-id="<?= (int)$r['report_id'] ?>" data-thread-id="<?= (int)($r['thread_id'] ?? 0) ?>" data-target-type="<?= htmlspecialchars($r['target_type']) ?>" data-target-id="<?= (int)$r['target_id'] ?>">
                                    <div class="ms-item-left">
                                        <?php if ($lvl > 0) : ?>
                                            <div class="ms-sanction-level level-<?= $lvl ?>">Lvl <?= $lvl ?></div>
                                        <?php else : ?>
                                            <div class="ms-sanction-level level-0">Clean</div>
                                        <?php endif; ?>
                                        <div class="ms-avatar"><?= $init ?></div>
                                    </div>
                                    <div class="ms-item-body">
                                        <div class="ms-item-header">
                                            <div class="ms-item-user">
                                                <span class="ms-username"><?= htmlspecialchars($r['author_name'] ?? 'Unknown') ?></span>
                                                <span class="ms-report-cat cat-<?= $catKey ?>"><?= $catLabel ?></span>
                                                <span class="ms-content-type"><?= ucfirst($r['target_type']) ?></span>
                                                <span class="ms-status-tag status-reviewed">Reviewed</span>
                                            </div>
                                            <span class="ms-item-time"><?= reltime($r['created_at']) ?></span>
                                        </div>
                                        <div class="ms-reported-content">
                                            <span class="ms-reported-label">Reported <?= $r['target_type'] ?>:</span>
                                            <p class="ms-reported-text"><?= htmlspecialchars($snippet) ?><?= mb_strlen($r['content_message'] ?? '') > 180 ? '…' : '' ?></p>
                                        </div>
                                        <div class="ms-thread-context">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                            </svg>
                                            <span>Thread: <strong><?= htmlspecialchars($r['thread_subject'] ?? '—') ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── DISMISSED TAB ────────────────────────────────── -->
                <div class="ms-tab-panel" id="tab-dismissed" style="display:none;">
                    <?php if (empty($dismissedReports)) : ?>
                        <div class="ms-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <p>No dismissed reports.</p>
                        </div>
                    <?php else : ?>
                        <div class="ms-list" id="ms-list-dismissed">
                            <?php foreach ($dismissedReports as $r) : ?>
                                <?php
                                $catKey   = $r['category'];
                                $catLabel = $cat_labels[$catKey] ?? ucfirst($catKey);
                                $init     = initials($r['author_name'] ?? 'U');
                                $snippet  = mb_substr($r['content_message'] ?? '', 0, 180);
                                ?>
                                <div class="ms-item ms-item--dismissed" data-category="<?= htmlspecialchars($catKey) ?>" data-report-id="<?= (int)$r['report_id'] ?>" data-thread-id="<?= (int)($r['thread_id'] ?? 0) ?>" data-target-type="<?= htmlspecialchars($r['target_type']) ?>" data-target-id="<?= (int)$r['target_id'] ?>">
                                    <div class="ms-item-left">
                                        <div class="ms-sanction-level level-dismissed">Done</div>
                                        <div class="ms-avatar ms-avatar--muted"><?= $init ?></div>
                                    </div>
                                    <div class="ms-item-body">
                                        <div class="ms-item-header">
                                            <div class="ms-item-user">
                                                <span class="ms-username ms-username--muted"><?= htmlspecialchars($r['author_name'] ?? 'Unknown') ?></span>
                                                <span class="ms-report-cat cat-<?= $catKey ?>"><?= $catLabel ?></span>
                                                <span class="ms-content-type"><?= ucfirst($r['target_type']) ?></span>
                                                <span class="ms-status-tag status-dismissed">Dismissed</span>
                                            </div>
                                            <span class="ms-item-time"><?= reltime($r['created_at']) ?></span>
                                        </div>
                                        <div class="ms-reported-content ms-reported-content--muted">
                                            <span class="ms-reported-label">Reported <?= $r['target_type'] ?>:</span>
                                            <p class="ms-reported-text"><?= htmlspecialchars($snippet) ?><?= mb_strlen($r['content_message'] ?? '') > 180 ? '…' : '' ?></p>
                                        </div>
                                        <div class="ms-thread-context">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                            </svg>
                                            <span>Thread: <strong><?= htmlspecialchars($r['thread_subject'] ?? '—') ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </section><!-- /ms-list-panel -->

        </main>
    </div>

    <!-- ── SANCTION MODAL ──────────────────────────────────────────── -->
    <div class="ms-modal-overlay" id="sanction-modal" style="display:none;" aria-modal="true" role="dialog">
        <div class="ms-modal-box">
            <div class="ms-modal-header">
                <div class="ms-modal-title-row">
                    <div class="ms-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="ms-modal-title">Issue Sanction</h3>
                        <p class="ms-modal-subtitle">User: <strong id="modal-author-name">—</strong></p>
                    </div>
                </div>
                <button class="ms-modal-close" id="modal-close">&times;</button>
            </div>
            <div class="ms-modal-body">
                <div class="ms-modal-level-row">
                    <label class="ms-label">Sanction Level</label>
                    <div class="ms-level-picker">
                        <label class="ms-level-option">
                            <input type="radio" name="modal-level" value="1" checked>
                            <div class="ms-level-card level-card-1">
                                <strong>Level 1</strong>
                                <span>Warning</span>
                                <small>Email notification only</small>
                            </div>
                        </label>
                        <label class="ms-level-option">
                            <input type="radio" name="modal-level" value="2">
                            <div class="ms-level-card level-card-2">
                                <strong>Level 2</strong>
                                <span>7-Day Ban</span>
                                <small>Restricted from posting</small>
                            </div>
                        </label>
                        <label class="ms-level-option">
                            <input type="radio" name="modal-level" value="3">
                            <div class="ms-level-card level-card-3">
                                <strong>Level 3</strong>
                                <span>Permanent Ban</span>
                                <small>Full feed restriction</small>
                            </div>
                        </label>
                    </div>
                    <p class="ms-recommended-level" id="modal-recommended"></p>
                </div>

                <div class="ms-field-group" style="margin-top:18px;">
                    <label class="ms-label" for="modal-reason">
                        Reason <span class="ms-label-optional">(optional)</span>
                    </label>
                    <textarea class="ms-input ms-textarea" id="modal-reason" placeholder="Optionally describe the violation…" rows="4"></textarea>
                    <span class="ms-field-error" id="modal-reason-error"></span>
                </div>

                <div class="ms-modal-warn" id="modal-warn-lvl3" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <span>This is a <strong>permanent</strong> ban. This user will lose all community feed privileges indefinitely.</span>
                </div>
            </div>
            <div class="ms-modal-footer">
                <button class="ms-cancel-btn" id="modal-cancel-btn">Cancel</button>
                <button class="ms-submit-btn" id="modal-submit-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    <span id="modal-submit-label">Confirm Sanction</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
     COMMENT SLIDE-IN PANEL
══════════════════════════════════════════════════════════ -->
    <div class="ms-panel-backdrop" id="ms-panel-backdrop"></div>

    <aside class="ms-comment-panel" id="ms-comment-panel" aria-label="Comment detail panel">

        <div class="ms-panel-header">
            <div class="ms-panel-header-left">
                <div class="ms-panel-badges" id="ms-panel-badges"></div>
            </div>
            <button class="ms-panel-close" id="ms-panel-close" aria-label="Close panel">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="ms-panel-body" id="ms-panel-body-scroll">

            <div class="ms-panel-loading" id="ms-panel-loading">
                <div class="ms-panel-spinner"></div>
                <span>Loading thread…</span>
            </div>

            <div id="ms-panel-content" style="display:none;">

                <h2 class="ms-panel-title" id="ms-panel-title"></h2>
                <div class="ms-panel-meta" id="ms-panel-meta"></div>
                <div class="ms-panel-divider"></div>
                <div class="ms-panel-body-text" id="ms-panel-body-text"></div>
                <div class="ms-panel-images" id="ms-panel-images" style="display:none;"></div>
                <div class="ms-panel-divider"></div>

                <div class="ms-panel-comments-section">
                    <h3 class="ms-panel-comments-heading">
                        Comments
                        <span class="ms-panel-comments-count" id="ms-panel-comments-count">0</span>
                    </h3>
                    <p class="ms-panel-scroll-hint">
                        ↓ The reported comment/reply is highlighted below.
                    </p>
                    <div class="ms-panel-comment-list" id="ms-panel-comment-list"></div>
                </div>

            </div>
        </div>

    </aside>

    <!-- TOAST -->
    <div id="ms-toast" class="ms-toast" aria-live="polite"></div>

    <script>
        const MOD_ID = <?= $mod_id ?>;
    </script>
    <script src="../../../scripts/management/moderator/mod_sanctions.js"></script>

</body>

</html>