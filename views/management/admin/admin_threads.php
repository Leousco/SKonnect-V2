<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Threads</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_threads.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_manage_services.css">
</head>
<body>

<div class="admin-layout">

    <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

    <main class="admin-content">

        <?php
        $pageTitle      = 'Threads';
        $pageBreadcrumb = [['Home', '#'], ['Community', '#'], ['Threads', null]];
        $adminName      = $_SESSION['user_name'] ?? 'Admin';
        $adminRole      = 'System Admin';
        $notifCount     = 7;
        include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
        ?>

        <!-- Controls -->
        <div class="svc-controls">
            <div class="svc-controls-left">
                <div class="svc-search-wrap">
                    <span class="svc-search-icon">🔍</span>
                    <input type="text" id="thread-search" class="svc-search-input" placeholder="Search threads...">
                </div>
                <select id="thread-category" class="svc-select">
                    <option value="all">All Categories</option>
                    <option value="inquiry">Inquiry</option>
                    <option value="complaint">Complaint</option>
                    <option value="suggestion">Suggestion</option>
                    <option value="event_question">Event Question</option>
                    <option value="other">Other</option>
                </select>
                <select id="thread-status" class="svc-select">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="responded">Responded</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
        </div>

        <!-- Stats Strip -->
        <div class="svc-stats-strip">
            <div class="svc-stat-pill">
                <span class="svc-stat-num" id="stat-total">—</span>
                <span>Total</span>
            </div>
            <div class="svc-stat-pill stat-pending">
                <span class="svc-stat-num" id="stat-pending">—</span>
                <span>Pending</span>
            </div>
            <div class="svc-stat-pill stat-approved">
                <span class="svc-stat-num" id="stat-responded">—</span>
                <span>Responded</span>
            </div>
            <div class="svc-stat-pill stat-completed">
                <span class="svc-stat-num" id="stat-resolved">—</span>
                <span>Resolved</span>
            </div>
            <div class="svc-stat-pill">
                <span class="svc-stat-num" id="stat-pinned">—</span>
                <span>📌 Pinned</span>
            </div>
        </div>

        <!-- Threads Grid -->
        <p class="svc-section-label">All Threads</p>
        <div class="svc-grid" id="thread-grid">
            <div class="svc-loading">Loading threads…</div>
        </div>

        <div class="svc-no-results" id="no-results" style="display:none;">
            <p>No threads found matching your search.</p>
        </div>

    </main>
</div>

<!-- THREAD ACTION MODAL -->
<div class="svc-modal-overlay" id="thread-modal-overlay">
    <div class="svc-modal-box">

        <div class="svc-modal-header">
            <div class="svc-modal-header-left">
                <div class="svc-modal-icon">💬</div>
                <div>
                    <h3 class="svc-modal-title" id="thread-modal-title">Thread Details</h3>
                    <p class="svc-modal-subtitle" id="thread-modal-subtitle">Community Thread</p>
                </div>
            </div>
            <button class="svc-modal-close" onclick="closeThreadModal()">×</button>
        </div>

        <div class="svc-modal-summary">
            <div class="svc-summary-item">
                <span class="svc-summary-label">Author</span>
                <span class="svc-summary-value" id="thread-modal-author">—</span>
            </div>
            <div class="svc-summary-item">
                <span class="svc-summary-label">Category</span>
                <span class="svc-summary-value" id="thread-modal-category">—</span>
            </div>
            <div class="svc-summary-item">
                <span class="svc-summary-label">Status</span>
                <span class="svc-summary-value" id="thread-modal-status">—</span>
            </div>
        </div>

        <div class="svc-modal-body">
            <div class="svc-form-group">
                <label class="svc-label">Thread Content</label>
                <p id="thread-modal-excerpt"></p>
            </div>
            <div class="svc-form-group">
                <label class="svc-label">Date Posted &amp; Comments</label>
                <p id="thread-modal-meta"></p>
            </div>
        </div>

        <div class="svc-modal-footer">
            <button class="btn-svc-secondary" onclick="closeThreadModal()">Close</button>
            <button class="btn-svc-primary" id="btn-pin"    onclick="threadAction('pin')">📌 Pin</button>
            <button class="btn-svc-danger"  id="btn-delete" onclick="threadAction('delete')">🗑️ Delete</button>
        </div>

    </div>
</div>

<script>
    const THREAD_API = '../../../backend/routes/admin_threads.php';
</script>
<script src="../../../scripts/management/admin/admin_threads.js"></script>
</body>
</html>