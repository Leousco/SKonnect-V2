<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Reports</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_reports.css">
</head>
<body>

<div class="admin-layout">

    <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

    <main class="admin-content">

        <?php
        $pageTitle      = 'Reports';
        $pageBreadcrumb = [['Home', '#'], ['Community', '#'], ['Reports', null]];
        $adminName      = $_SESSION['user_name'] ?? 'Admin';
        $adminRole      = 'System Admin';
        $notifCount     = 7;
        include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
        ?>

        <section class="rpt-widgets">
            <div class="rpt-widget-card widget-indigo">
                <div class="rpt-widget-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l1.664 1.664M21 21l-1.5-1.5m-5.485-1.242L12 17.25 4.5 21V8.742m.164-4.078a2.15 2.15 0 0 1 1.743-1.342 48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185V19.5M4.664 4.664 19.5 19.5" />
                    </svg>
                </div>
                <div class="rpt-widget-body">
                    <span class="rpt-widget-label">Total Reports</span>
                    <p class="rpt-widget-number" id="stat-total">—</p>
                </div>
            </div>
            <div class="rpt-widget-card widget-amber">
                <div class="rpt-widget-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="rpt-widget-body">
                    <span class="rpt-widget-label">Pending</span>
                    <p class="rpt-widget-number" id="stat-pending">—</p>
                </div>
            </div>
            <div class="rpt-widget-card widget-teal">
                <div class="rpt-widget-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="rpt-widget-body">
                    <span class="rpt-widget-label">Reviewed</span>
                    <p class="rpt-widget-number" id="stat-reviewed">—</p>
                </div>
            </div>
            <div class="rpt-widget-card widget-slate">
                <div class="rpt-widget-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="rpt-widget-body">
                    <span class="rpt-widget-label">Dismissed</span>
                    <p class="rpt-widget-number" id="stat-dismissed">—</p>
                </div>
            </div>
        </section>

        <section class="rpt-filters-bar">
            <div class="rpt-filter-row rpt-filter-row-bottom">
                <div class="rpt-filters-left">
                    <span class="rpt-filter-group-label">Status</span>
                    <button class="rpt-filter-btn active" data-filter-type="status" data-filter="all">All</button>
                    <button class="rpt-filter-btn rpt-status-btn status-pending" data-filter-type="status" data-filter="pending">Pending</button>
                    <button class="rpt-filter-btn rpt-status-btn status-reviewed" data-filter-type="status" data-filter="reviewed">Reviewed</button>
                    <button class="rpt-filter-btn rpt-status-btn status-dismissed" data-filter-type="status" data-filter="dismissed">Dismissed</button>
                </div>
                <div class="rpt-select-filters">
                    <label class="rpt-select-filter">
                        <span class="rpt-filter-group-label">Type</span>
                        <select class="rpt-filter-select" data-filter-type="type" aria-label="Filter by report type">
                            <option value="all">All</option>
                            <option value="thread">Thread</option>
                            <option value="comment">Comment</option>
                            <option value="reply">Reply</option>
                        </select>
                    </label>
                    <label class="rpt-select-filter">
                        <span class="rpt-filter-group-label">Reason</span>
                        <select class="rpt-filter-select" data-filter-type="reason" aria-label="Filter by report reason">
                            <option value="all">All</option>
                            <option value="spam">Spam</option>
                            <option value="inappropriate">Inappropriate</option>
                            <option value="harassment">Harassment</option>
                            <option value="misinformation">Misinformation</option>
                        </select>
                    </label>
                </div>
                <div class="rpt-search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text" id="report-search" class="rpt-search-input" placeholder="Search reports…">
                </div>
            </div>
        </section>

        <p class="rpt-section-label">All Reports</p>

        <div class="rpt-table-wrap">
            <table class="rpt-table" id="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Content / Subject</th>
                        <th>Reported By</th>
                        <th>Author</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <tr>
                        <td colspan="9" style="text-align:center; padding:2rem; color:var(--ap-text-muted); font-family:'Poppins',sans-serif; font-size:13px;">
                            Loading reports…
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="rpt-no-results" id="no-results" style="display:none;">
                <p>No reports found matching your search.</p>
            </div>
        </div>

    </main>
</div>

<div class="arp-panel-backdrop" id="arp-panel-backdrop"></div>

<aside class="arp-panel" id="arp-panel" aria-label="Report detail panel">
    <div class="arp-panel-header">
        <div class="arp-panel-badges" id="arp-panel-badges"></div>
        <button class="arp-panel-close" id="arp-panel-close" aria-label="Close panel">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="arp-panel-body" id="arp-panel-body-scroll">

        <div class="arp-panel-loading" id="arp-panel-loading">
            <div class="arp-panel-spinner"></div>
            <span>Loading thread…</span>
        </div>

        <div id="arp-panel-content" style="display:none;">

            <h2 class="arp-panel-title" id="arp-panel-title"></h2>

            <div class="arp-panel-meta" id="arp-panel-meta"></div>

            <div class="arp-panel-divider"></div>

            <div class="arp-panel-body-text" id="arp-panel-body-text"></div>

            <div class="arp-panel-images" id="arp-panel-images" style="display:none;"></div>

            <div class="arp-panel-divider"></div>

            <div id="arp-report-banner"></div>

            <div class="arp-panel-actions" id="arp-panel-actions"></div>

            <div class="arp-panel-divider"></div>

            <div class="arp-panel-comments-section">
                <h3 class="arp-panel-comments-heading">
                    Comments
                    <span class="arp-panel-comments-count" id="arp-panel-comments-count">0</span>
                </h3>
                <div class="arp-panel-comment-list" id="arp-panel-comment-list"></div>
            </div>

        </div>
    </div>
</aside>

<div class="arp-lightbox-overlay" id="arp-lightbox">
    <button class="arp-lightbox-close" id="arp-lightbox-close">&times;</button>
    <img class="arp-lightbox-img" id="arp-lightbox-img" src="" alt="Image preview">
</div>

<div class="arp-modal-overlay" id="arp-sanction-modal">
    <div class="arp-modal-box">
        <div class="arp-modal-header">
            <div class="arp-modal-title-row">
                <div class="arp-modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="arp-modal-title">Issue Sanction</h3>
                    <p class="arp-modal-subtitle">User: <strong id="arp-modal-author-name">—</strong></p>
                </div>
            </div>
            <button class="arp-modal-close" id="arp-modal-close">&times;</button>
        </div>
        <div class="arp-modal-body">
            <label class="arp-label">Sanction Level</label>
            <div class="arp-level-picker">
                <label class="arp-level-option">
                    <input type="radio" name="arp-level" value="1" checked>
                    <div class="arp-level-card level-card-1">
                        <strong>Level 1</strong>
                        <span>Warning</span>
                        <small>Email notification only</small>
                    </div>
                </label>
                <label class="arp-level-option">
                    <input type="radio" name="arp-level" value="2">
                    <div class="arp-level-card level-card-2">
                        <strong>Level 2</strong>
                        <span>7-Day Ban</span>
                        <small>Content removed + restricted</small>
                    </div>
                </label>
                <label class="arp-level-option">
                    <input type="radio" name="arp-level" value="3">
                    <div class="arp-level-card level-card-3">
                        <strong>Level 3</strong>
                        <span>Permanent Ban</span>
                        <small>Content removed + full ban</small>
                    </div>
                </label>
            </div>
            <p class="arp-recommended-level" id="arp-modal-recommended"></p>

            <div class="arp-field-group">
                <label class="arp-label" for="arp-modal-reason">
                    Reason <span class="arp-label-optional">(optional)</span>
                </label>
                <textarea class="arp-textarea" id="arp-modal-reason" placeholder="Optionally describe the violation…"></textarea>
            </div>

            <div class="arp-modal-warn" id="arp-modal-warn-lvl3" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span>This is a <strong>permanent</strong> ban. The user loses all community feed privileges indefinitely.</span>
            </div>
        </div>
        <div class="arp-modal-footer">
            <button class="arp-btn-secondary" id="arp-modal-cancel">Cancel</button>
            <button class="arp-btn-submit" id="arp-modal-submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <span id="arp-modal-submit-label">Confirm Sanction</span>
            </button>
        </div>
    </div>
</div>

<div class="rpt-confirm-overlay" id="rpt-confirm-overlay">
    <div class="rpt-confirm-box">
        <div class="rpt-confirm-icon" id="rpt-confirm-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <h3 class="rpt-confirm-title" id="rpt-confirm-title">Confirm Action</h3>
        <p class="rpt-confirm-body" id="rpt-confirm-body">Are you sure you want to perform this action?</p>
        <div class="rpt-confirm-footer">
            <button class="rpt-confirm-cancel" id="rpt-confirm-cancel">Cancel</button>
            <button class="rpt-confirm-ok" id="rpt-confirm-ok">Confirm</button>
        </div>
    </div>
</div>

<div id="rpt-toast" class="rpt-toast" aria-live="polite"></div>

<script>
    const REPORT_API = '../../../backend/routes/admin_reports.php';
    const THREAD_API  = '../../../backend/controllers/AdminGetThreadController.php';
</script>
<script src="../../../scripts/management/admin/admin_reports.js"></script>
</body>
</html>