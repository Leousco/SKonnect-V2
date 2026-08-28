<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Reports</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_manage_services.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_threads.css">
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

        <!-- Controls -->
        <div class="rpt-controls">
            <div class="rpt-controls-left">
                <div class="rpt-search-wrap">
                    <span class="rpt-search-icon">🔍</span>
                    <input type="text" id="report-search" class="rpt-search-input" placeholder="Search reports…">
                </div>
                <select id="report-type" class="rpt-select">
                    <option value="all">All Types</option>
                    <option value="thread">Thread</option>
                    <option value="comment">Comment</option>
                    <option value="reply">Reply</option>
                </select>
                <select id="report-reason" class="rpt-select">
                    <option value="all">All Reasons</option>
                    <option value="spam">Spam</option>
                    <option value="inappropriate">Inappropriate</option>
                    <option value="harassment">Harassment</option>
                    <option value="misinformation">Misinformation</option>
                </select>
                <select id="report-status" class="rpt-select">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="dismissed">Dismissed</option>
                </select>
            </div>
        </div>

        <!-- Stats Strip -->
        <div class="rpt-stats-strip">
            <div class="rpt-stat-pill">
                <span class="rpt-stat-num" id="stat-total">—</span>
                <span>Total Reports</span>
            </div>
            <div class="rpt-stat-pill stat-pending">
                <span class="rpt-stat-num" id="stat-pending">—</span>
                <span>Pending</span>
            </div>
            <div class="rpt-stat-pill stat-warned">
                <span class="rpt-stat-num" id="stat-reviewed">—</span>
                <span>Reviewed</span>
            </div>
            <div class="rpt-stat-pill stat-ignored">
                <span class="rpt-stat-num" id="stat-dismissed">—</span>
                <span>Dismissed</span>
            </div>
        </div>

        <!-- Reports Table -->
        <p class="rpt-section-label">All Reports</p>

        <div class="rpt-table-wrap">
            <table class="rpt-table" id="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Content / Subject</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <tr>
                        <td colspan="7" style="text-align:center; padding:2rem; color:var(--ap-text-muted); font-family:'Poppins',sans-serif; font-size:13px;">
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

<!-- REPORT ACTION MODAL -->
<div class="rpt-modal-overlay" id="report-modal-overlay">
    <div class="rpt-modal-box">

        <div class="rpt-modal-header">
            <div class="rpt-modal-header-left">
                <div class="rpt-modal-icon" id="report-modal-icon">⚠️</div>
                <div>
                    <h3 class="rpt-modal-title" id="report-modal-title">Report Details</h3>
                    <p class="rpt-modal-subtitle" id="report-modal-subtitle">Reported Content</p>
                </div>
            </div>
            <button class="rpt-modal-close" onclick="closeReportModal()">×</button>
        </div>

        <div class="rpt-modal-summary">
            <div class="rpt-summary-item">
                <span class="rpt-summary-label">Reported By</span>
                <span class="rpt-summary-value" id="report-modal-reporter">—</span>
            </div>
            <div class="rpt-summary-item">
                <span class="rpt-summary-label">Author</span>
                <span class="rpt-summary-value" id="report-modal-author">—</span>
            </div>
            <div class="rpt-summary-item">
                <span class="rpt-summary-label">Reason</span>
                <span class="rpt-summary-value" id="report-modal-reason">—</span>
            </div>
            <div class="rpt-summary-item">
                <span class="rpt-summary-label">Date Reported</span>
                <span class="rpt-summary-value" id="report-modal-date">—</span>
            </div>
        </div>

        <div class="rpt-modal-body">
            <div class="rpt-form-group">
                <label class="rpt-label">Reported Content</label>
                <p id="report-modal-excerpt" class="rpt-content-block rpt-content-block--flagged"></p>
            </div>
            <div class="rpt-form-group">
                <label class="rpt-label">Report Details</label>
                <p id="report-modal-details" class="rpt-content-block"></p>
            </div>
            <div class="rpt-form-group">
                <label class="rpt-label">
                    Admin Note
                    <span class="rpt-label-optional">(optional)</span>
                </label>
                <textarea class="rpt-textarea" id="report-admin-note"
                    placeholder="Add an internal note or reason for action…"></textarea>
            </div>
        </div>

        <div class="rpt-modal-footer">
            <button class="btn-rpt-secondary" onclick="closeReportModal()">Close</button>
            <button class="btn-rpt-ignore"    onclick="reportAction('ignore')">✅ Ignore</button>
            <button class="btn-rpt-warn"      onclick="reportAction('warn')">⚠️ Warn User</button>
            <button class="btn-rpt-delete"    onclick="reportAction('delete_content')">❌ Delete Post</button>
            <button class="btn-rpt-ban"       onclick="reportAction('ban')">🚫 Ban User</button>
        </div>

    </div>
</div>

<script>
    const REPORT_API = '../../../backend/routes/admin_reports.php';
</script>
<script src="../../../scripts/management/admin/admin_reports.js"></script>
</body>
</html>