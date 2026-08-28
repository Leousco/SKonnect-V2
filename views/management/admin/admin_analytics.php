<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect Admin | Analytics</title>
    <link rel="stylesheet" href="../../../styles/management/mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/admin/admin_analytics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="admin-layout">

        <?php include __DIR__ . '/../../../components/management/admin/admin_sidebar.php'; ?>

        <main class="admin-content">

            <?php
            $pageTitle      = 'Analytics';
            $pageBreadcrumb = [['Home', '#'], ['Reports & Logs', '#'], ['Analytics', null]];
            $adminName      = $_SESSION['user_name'] ?? 'Admin';
            $adminRole      = 'System Admin';
            $notifCount     = 7;
            include __DIR__ . '/../../../components/management/admin/admin_topbar.php';
            ?>

            <!-- ── EXPORT BUTTON ────────────────────────────────── -->
            <div class="an-toolbar">
                <p class="an-toolbar-label">Live data · refreshes on load</p>
                <button class="an-export-btn" id="exportPdfBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export PDF Report
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════════
             SECTION 1 · STAT CARDS
        ════════════════════════════════════════════════════ -->
            <section class="an-stats">

                <div class="an-stat-card">
                    <div class="an-stat-icon icon-violet">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div class="an-stat-body">
                        <span class="an-stat-label">Total Users</span>
                        <p class="an-stat-value" id="stat-total-users">—</p>
                        <span class="an-stat-sub trend-up" id="stat-new-this-month">Loading…</span>
                    </div>
                </div>

                <div class="an-stat-card">
                    <div class="an-stat-icon icon-amber">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div class="an-stat-body">
                        <span class="an-stat-label">Total Service Requests</span>
                        <p class="an-stat-value" id="stat-total-requests">—</p>
                        <span class="an-stat-sub trend-up" id="stat-requests-month">Loading…</span>
                    </div>
                </div>

                <div class="an-stat-card">
                    <div class="an-stat-icon icon-green">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                    </div>
                    <div class="an-stat-body">
                        <span class="an-stat-label">Most Requested Service</span>
                        <p class="an-stat-value an-stat-value--text" id="stat-top-service">—</p>
                        <span class="an-stat-sub" id="stat-top-service-cnt">Loading…</span>
                    </div>
                </div>

                <div class="an-stat-card">
                    <div class="an-stat-icon icon-indigo">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                        </svg>
                    </div>
                    <div class="an-stat-body">
                        <span class="an-stat-label">Active Users</span>
                        <p class="an-stat-value" id="stat-active-users">—</p>
                        <span class="an-stat-sub" id="stat-inactive-users">Loading…</span>
                    </div>
                </div>

            </section>

            <!-- ═══════════════════════════════════════════════════
             SECTION 2 · CHARTS ROW A — Bar + Service Donut
        ════════════════════════════════════════════════════ -->
            <div class="an-charts-row">

                <section class="an-chart-panel an-chart-panel--wide">
                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">Service Requests Per Month</h2>
                            <p class="an-panel-sub">Monthly volume across all service types</p>
                        </div>
                        <select class="an-select" id="reqYearFilter"></select>
                    </div>
                    <div class="an-chart-wrap">
                        <canvas id="requestsBarChart"></canvas>
                    </div>
                </section>

                <section class="an-chart-panel">
                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">Requests by Category</h2>
                            <p class="an-panel-sub">Current month breakdown</p>
                        </div>
                    </div>
                    <div class="an-chart-wrap an-chart-wrap--donut">
                        <canvas id="serviceDonutChart"></canvas>
                    </div>
                    <ul class="an-legend" id="service-legend"></ul>
                </section>

            </div>

            <!-- ═══════════════════════════════════════════════════
             SECTION 3 · CHARTS ROW B — Growth + Active Donut
        ════════════════════════════════════════════════════ -->
            <div class="an-charts-row">

                <section class="an-chart-panel an-chart-panel--wide">
                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">User Growth</h2>
                            <p class="an-panel-sub">Cumulative registered users — last 12 months</p>
                        </div>
                    </div>
                    <div class="an-chart-wrap">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </section>

                <section class="an-chart-panel">
                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">Active vs Inactive</h2>
                            <p class="an-panel-sub">Current user status breakdown</p>
                        </div>
                    </div>
                    <div class="an-active-display">
                        <div class="an-active-ring-wrap">
                            <canvas id="activeDonutChart"></canvas>
                            <div class="an-active-center">
                                <span class="an-active-pct" id="active-pct">—</span>
                                <span class="an-active-lbl">Active</span>
                            </div>
                        </div>
                    </div>
                    <div class="an-active-stats">
                        <div class="an-active-stat">
                            <span class="an-active-dot dot-green"></span>
                            <div>
                                <strong id="active-count">—</strong>
                                <span>Active users</span>
                            </div>
                        </div>
                        <div class="an-active-stat">
                            <span class="an-active-dot dot-red"></span>
                            <div>
                                <strong id="inactive-count">—</strong>
                                <span>Inactive / Banned</span>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- ═══════════════════════════════════════════════════
             SECTION 4 · INFO PANELS — Roles · Announcements · Events · Threads
        ════════════════════════════════════════════════════ -->
            <div class="an-info-row">

                <!-- User Roles -->
                <section class="an-info-panel">
                    <div class="an-info-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="an-info-icon icon-violet">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                        <h2 class="an-info-title">Registered Users</h2>
                    </div>
                    <div class="an-role-grid">
                        <div class="an-role-item">
                            <span class="an-role-count" id="role-resident">—</span>
                            <span class="an-role-label">Residents</span>
                        </div>
                        <div class="an-role-item">
                            <span class="an-role-count" id="role-sk_officer">—</span>
                            <span class="an-role-label">SK Officers</span>
                        </div>
                        <div class="an-role-item">
                            <span class="an-role-count" id="role-moderator">—</span>
                            <span class="an-role-label">Moderators</span>
                        </div>
                        <div class="an-role-item">
                            <span class="an-role-count" id="role-admin">—</span>
                            <span class="an-role-label">Admins</span>
                        </div>
                    </div>
                </section>

                <!-- Announcements -->
                <section class="an-info-panel">
                    <div class="an-info-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="an-info-icon icon-amber">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                        </svg>
                        <h2 class="an-info-title">Announcements</h2>
                    </div>
                    <div class="an-kpi-list">
                        <div class="an-kpi-row">
                            <span class="an-kpi-label">Total</span>
                            <strong class="an-kpi-val" id="ann-total">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-green"></span>
                            <span class="an-kpi-label">Published</span>
                            <strong class="an-kpi-val" id="ann-published">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-amber"></span>
                            <span class="an-kpi-label">Drafts</span>
                            <strong class="an-kpi-val" id="ann-drafts">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-slate"></span>
                            <span class="an-kpi-label">Archived</span>
                            <strong class="an-kpi-val" id="ann-archived">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-red"></span>
                            <span class="an-kpi-label">Urgent (active)</span>
                            <strong class="an-kpi-val" id="ann-urgent">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-violet"></span>
                            <span class="an-kpi-label">Featured (active)</span>
                            <strong class="an-kpi-val" id="ann-featured">—</strong>
                        </div>
                    </div>
                </section>

                <!-- Events -->
                <section class="an-info-panel">
                    <div class="an-info-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="an-info-icon icon-green">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                        </svg>
                        <h2 class="an-info-title">Events</h2>
                    </div>
                    <div class="an-kpi-list">
                        <div class="an-kpi-row">
                            <span class="an-kpi-label">Total</span>
                            <strong class="an-kpi-val" id="evt-total">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-green"></span>
                            <span class="an-kpi-label">Upcoming</span>
                            <strong class="an-kpi-val" id="evt-upcoming">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-slate"></span>
                            <span class="an-kpi-label">Past</span>
                            <strong class="an-kpi-val" id="evt-past">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-violet"></span>
                            <span class="an-kpi-label">This Month</span>
                            <strong class="an-kpi-val" id="evt-this-month">—</strong>
                        </div>
                    </div>
                </section>

                <!-- Threads -->
                <section class="an-info-panel">
                    <div class="an-info-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="an-info-icon icon-indigo">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                        <h2 class="an-info-title">Threads</h2>
                    </div>
                    <div class="an-kpi-list">
                        <div class="an-kpi-row">
                            <span class="an-kpi-label">Total</span>
                            <strong class="an-kpi-val" id="thr-total">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-green"></span>
                            <span class="an-kpi-label">Published</span>
                            <strong class="an-kpi-val" id="thr-published">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-red"></span>
                            <span class="an-kpi-label">Removed</span>
                            <strong class="an-kpi-val" id="thr-removed">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-amber"></span>
                            <span class="an-kpi-label">Pending</span>
                            <strong class="an-kpi-val" id="thr-pending">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-indigo"></span>
                            <span class="an-kpi-label">Responded</span>
                            <strong class="an-kpi-val" id="thr-responded">—</strong>
                        </div>
                        <div class="an-kpi-row">
                            <span class="an-kpi-dot dot-green"></span>
                            <span class="an-kpi-label">Resolved</span>
                            <strong class="an-kpi-val" id="thr-resolved">—</strong>
                        </div>
                    </div>
                </section>

            </div>

            <!-- ═══════════════════════════════════════════════════
             SECTION 5 · SERVICES TABLE + REQUEST STATUS + TYPE
        ════════════════════════════════════════════════════ -->
            <div class="an-service-row">

                <!-- Top Services table -->
                <section class="an-chart-panel an-service-table-panel">
                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">Top Services by Requests</h2>
                            <p class="an-panel-sub">All-time, ranked by volume</p>
                        </div>
                    </div>
                    <div class="an-table-wrap">
                        <table class="an-table" id="servicesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Service</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Requests</th>
                                </tr>
                            </thead>
                            <tbody id="servicesTableBody">
                                <tr>
                                    <td colspan="5" class="an-table-empty">Loading…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Request Status + Type -->
                <section class="an-chart-panel an-status-panel">

                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">Request Status</h2>
                            <p class="an-panel-sub">All-time counts</p>
                        </div>
                    </div>
                    <div class="an-status-grid" id="statusGrid">
                        <!-- filled by JS -->
                    </div>

                    <div class="an-divider"></div>

                    <div class="an-panel-header" style="margin-top:14px;">
                        <div>
                            <h2 class="an-panel-title">By Service Type</h2>
                            <p class="an-panel-sub">All-time counts</p>
                        </div>
                    </div>
                    <div class="an-type-grid" id="typeGrid">
                        <!-- filled by JS -->
                    </div>

                </section>

            </div>

            <!-- ═══════════════════════════════════════════════════
             SECTION 6 · REPORTS — Thread Reports + Comment Reports
        ════════════════════════════════════════════════════ -->
            <div class="an-reports-row">

                <section class="an-chart-panel an-report-panel">
                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">Thread Reports</h2>
                            <p class="an-panel-sub">All-time moderation queue</p>
                        </div>
                    </div>
                    <div class="an-report-stats">
                        <div class="an-report-stat">
                            <span class="an-report-label">Total Reports</span>
                            <span class="an-report-number" id="thread-report-total">—</span>
                        </div>
                        <div class="an-report-stat">
                            <span class="an-report-label">Pending Review</span>
                            <span class="an-report-number pending" id="thread-report-pending">—</span>
                        </div>
                        <div class="an-report-stat">
                            <span class="an-report-label">Reviewed</span>
                            <span class="an-report-number reviewed" id="thread-report-reviewed">—</span>
                        </div>
                        <div class="an-report-stat">
                            <span class="an-report-label">Dismissed</span>
                            <span class="an-report-number dismissed" id="thread-report-dismissed">—</span>
                        </div>
                    </div>
                </section>

                <section class="an-chart-panel an-report-panel">
                    <div class="an-panel-header">
                        <div>
                            <h2 class="an-panel-title">Comment Reports</h2>
                            <p class="an-panel-sub">All-time moderation queue</p>
                        </div>
                    </div>
                    <div class="an-report-stats">
                        <div class="an-report-stat">
                            <span class="an-report-label">Total Reports</span>
                            <span class="an-report-number" id="comment-report-total">—</span>
                        </div>
                        <div class="an-report-stat">
                            <span class="an-report-label">Pending Review</span>
                            <span class="an-report-number pending" id="comment-report-pending">—</span>
                        </div>
                        <div class="an-report-stat">
                            <span class="an-report-label">Reviewed</span>
                            <span class="an-report-number reviewed" id="comment-report-reviewed">—</span>
                        </div>
                        <div class="an-report-stat">
                            <span class="an-report-label">Dismissed</span>
                            <span class="an-report-number dismissed" id="comment-report-dismissed">—</span>
                        </div>
                    </div>
                </section>

            </div>

        </main>
    </div>

    <script src="../../../scripts/management/admin/admin_analytics.js?v=<?= time() ?>"></script>
</body>

</html>