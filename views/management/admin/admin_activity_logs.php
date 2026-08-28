<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect Admin | Activity Logs</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_activity_logs.css">
</head>
<body>
<div class="admin-layout">

    <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

    <main class="admin-content">

        <?php
        $pageTitle      = 'Activity Logs';
        $pageBreadcrumb = [['Home', '#'], ['Reports & Logs', '#'], ['Activity Logs', null]];
        $adminName      = $_SESSION['user_name'] ?? 'Admin';
        $adminRole      = 'System Admin';
        $notifCount     = 7;
        include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
        ?>

        <!-- FILTER BAR -->
        <section class="log-filter-bar">
            <div class="log-search-wrap">
                <svg class="log-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input type="text" id="logSearch" class="log-search-input" placeholder="Search by user, action, or description…">
            </div>
            <div class="log-filters">
                <select id="filterAction" class="log-select">
                    <option value="">All Action Types</option>
                    <option value="approved">Approved</option>
                    <option value="declined">Declined</option>
                    <option value="published">Published</option>
                    <option value="flagged">Flagged</option>
                    <option value="deleted">Deleted</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="login">Login</option>
                </select>
                <input type="date" id="filterDateFrom" class="log-select" title="From date">
                <input type="date" id="filterDateTo" class="log-select" title="To date">
                <button class="log-btn-reset" id="resetFilters">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Reset
                </button>
                <button class="log-btn-export" id="exportCSV">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Export CSV
                </button>
            </div>
        </section>

        <!-- SUMMARY CHIPS -->
        <div class="log-summary-row">
            <div class="log-summary-chip chip-total">
                <strong id="totalCount">0</strong>
                <span>Total entries</span>
            </div>
            <div class="log-summary-chip chip-filtered">
                <strong id="filteredCount">0</strong>
                <span>Showing</span>
            </div>
        </div>

        <!-- LOG TABLE -->
        <section class="log-panel">
            <div class="log-table-wrap">
                <table class="log-table" id="logTable">
                    <thead>
                        <tr>
                            <th class="col-time">Timestamp</th>
                            <th class="col-user">User</th>
                            <th class="col-role">Role</th>
                            <th class="col-action">Action</th>
                            <th class="col-desc">Description</th>
                        </tr>
                    </thead>
                    <tbody id="logBody"></tbody>
                </table>
                <div class="log-empty" id="logEmpty" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    <p>No log entries match your filters.</p>
                </div>
            </div>

            <!-- PAGINATION -->
            <div class="log-pagination">
                <span class="log-page-info" id="pageInfo">Page 1 of 1</span>
                <div class="log-page-btns">
                    <button class="log-page-btn" id="prevPage" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <div class="log-page-numbers" id="pageNumbers"></div>
                    <button class="log-page-btn" id="nextPage">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                </div>
            </div>
        </section>

    </main>
</div>

<script src="../../../scripts/management/admin/admin_activity_logs.js"></script>
</body>
</html>