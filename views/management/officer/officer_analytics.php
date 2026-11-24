<?php
require_once __DIR__ . '/../../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('sk_officer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Analytics — SK Officer</title>
    <link rel="stylesheet" href="../../../styles/management/officer_mgmt.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css?v=2">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_analytics.css">
    <link rel="icon" type="image/svg+xml" href="../../../assets/img/favicon.svg">
</head>
<body>

<div class="off-layout">

    <?php include __DIR__ . '/../../../components/management/officer/officer_sidebar.php'; ?>

    <main class="off-content">

    <?php
    $pageTitle      = 'Analytics';
    $pageBreadcrumb = [['Home', '#'], ['Insights', null], ['Analytics', null]];
    $officerName    = $_SESSION['user_name'] ?? 'SK Officer';
    $officerRole    = 'SK Officer';
    $notifCount     = 3;
    include __DIR__ . '/../../../components/management/officer/officer_topbar.php';
    ?>

        <!-- Filter bar -->
        <div class="an-filter-bar">

            <!-- preset period tabs -->
            <div class="an-period-tabs">
                <button class="an-period-tab active" data-period="month">This Month</button>
                <button class="an-period-tab" data-period="quarter">This Quarter</button>
                <button class="an-period-tab" data-period="year">This Year</button>
            </div>

            <!-- custom date-range -->
            <div class="an-date-range">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;color:var(--off-text-muted);flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5"/></svg>
                <input type="date" id="an-date-from" aria-label="From date">
                <span class="an-date-sep">–</span>
                <input type="date" id="an-date-to" aria-label="To date (optional — leave blank for a single day)">
                <span class="an-date-range-hint" id="an-date-hint">or single date</span>
                <button class="an-apply-btn" id="an-apply-range">Apply</button>
            </div>
            <button class="an-reset-btn" id="an-reset-range" hidden>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                Reset
            </button>

            <!-- right: error + export -->
            <div class="an-filter-right">
                <p class="an-error" id="an-error" role="alert" hidden></p>
                <button class="an-export-btn" id="an-export-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- KPI widgets -->
        <section class="off-widgets" id="an-widgets-real" style="display:none">

            <div class="off-widget-card widget-cyan">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Total Requests</span>
                    <p class="widget-number" data-kpi="total-requests">—</p>
                    <span class="widget-caption">Selected period</span>
                </div>
            </div>

            <div class="off-widget-card widget-green">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Approval Rate</span>
                    <p class="widget-number" data-kpi="approval-rate">—</p>
                    <span class="widget-caption">Of decided requests</span>
                </div>
            </div>

            <div class="off-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Avg. Processing Time</span>
                    <p class="widget-number" data-kpi="avg-days">—<span class="widget-unit">d</span></p>
                    <span class="widget-caption">Completed requests</span>
                </div>
            </div>

            <div class="off-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Announcements</span>
                    <p class="widget-number" data-kpi="announcements">—</p>
                    <span class="widget-caption">Active right now</span>
                </div>
            </div>

        </section>

        <!-- Skeleton: KPI widgets (shown on initial load) -->
        <section class="off-widgets an-skeleton-layer" id="an-skel-widgets">
            <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="an-skel-widget">
                <div class="an-skel an-skel-widget-icon"></div>
                <div class="an-skel-widget-body">
                    <div class="an-skel an-skel-line" style="width:55%"></div>
                    <div class="an-skel an-skel-num"></div>
                    <div class="an-skel an-skel-line" style="width:40%"></div>
                </div>
            </div>
            <?php endfor; ?>
        </section>

        <!-- Row 1: Volume chart + Service breakdown -->
        <div class="an-grid an-row-62" style="margin-bottom:12px;display:none" id="an-row-1">

            <!-- Request volume -->
            <section class="an-panel an-panel--volume">
                <div class="panel-header">
                    <h2 class="section-label">Request Volume</h2>
                    <span class="an-chart-period" id="an-volume-period">—</span>
                </div>
                <div class="an-panel-body">
                    <div class="an-sparkline-wrap">
                        <canvas id="chart-volume"></canvas>
                    </div>
                    <div class="an-spark-stats">
                        <div class="an-spark-stat">
                            <span class="an-spark-val" data-kpi="spark-pending">—</span>
                            <span class="an-spark-lbl">Pending</span>
                        </div>
                        <div class="an-spark-stat">
                            <span class="an-spark-val" data-kpi="spark-processing">—</span>
                            <span class="an-spark-lbl">Processing</span>
                        </div>
                        <div class="an-spark-stat">
                            <span class="an-spark-val" data-kpi="spark-approved">—</span>
                            <span class="an-spark-lbl">Approved</span>
                        </div>
                        <div class="an-spark-stat">
                            <span class="an-spark-val" data-kpi="spark-declined">—</span>
                            <span class="an-spark-lbl">Declined</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- By service type -->
            <section class="an-panel an-panel--service">
                <div class="panel-header">
                    <h2 class="section-label">By Service Type</h2>
                </div>
                <div class="an-panel-body">
                    <div class="an-bar-list" id="an-bar-service"></div>
                </div>
            </section>

        </div>

        <!-- Skeleton: Row 1 -->
        <div class="an-grid an-row-62 an-skeleton-layer" id="an-skel-row-1" style="margin-bottom:12px">
            <div class="an-skel-panel an-panel--volume">
                <div class="an-skel an-skel-header"></div>
                <div class="an-skel an-skel-chart" style="height:160px"></div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:8px">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:6px">
                        <div class="an-skel" style="height:24px;width:50%;border-radius:4px"></div>
                        <div class="an-skel" style="height:9px;width:70%;border-radius:4px"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="an-skel-panel an-panel--service">
                <div class="an-skel an-skel-header"></div>
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="an-skel-row">
                    <div class="an-skel" style="height:10px;width:35%;border-radius:4px"></div>
                    <div class="an-skel" style="flex:1;height:7px;border-radius:4px"></div>
                    <div class="an-skel" style="height:10px;width:18px;border-radius:4px"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Row 2: Events + Announcements -->
        <div class="an-grid an-row-2" style="margin-bottom:12px;display:none" id="an-row-2">

            <!-- Events overview -->
            <section class="an-panel an-panel--events">
                <div class="panel-header">
                    <h2 class="section-label">Events Overview</h2>
                    <a href="officer_events.php" class="btn-off-sm">Manage ›</a>
                </div>
                <div class="an-events-panel-body">
                    <div class="an-events-grid" id="an-events-grid"></div>
                    <div class="an-events-chart-wrap">
                        <canvas id="chart-events"></canvas>
                    </div>
                </div>
            </section>

            <!-- Announcements donut -->
            <section class="an-panel an-panel--announce">
                <div class="panel-header">
                    <h2 class="section-label">Announcements</h2>
                    <a href="officer_announcements.php" class="btn-off-sm">Manage ›</a>
                </div>
                <div class="an-panel-body">
                    <div class="an-donut-wrap">
                        <div class="an-donut-chart">
                            <canvas id="chart-announcements" aria-label="Announcements by status"></canvas>
                        </div>
                        <div class="an-donut-legend" id="an-ann-legend"></div>
                    </div>
                </div>
            </section>

        </div>

        <!-- Skeleton: Row 2 -->
        <div class="an-grid an-row-2 an-skeleton-layer" id="an-skel-row-2" style="margin-bottom:12px">
            <div class="an-skel-panel an-panel--events">
                <div class="an-skel an-skel-header"></div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="an-skel" style="height:60px;border-radius:8px"></div>
                    <?php endfor; ?>
                </div>
                <div class="an-skel" style="flex:1;height:100px;border-radius:6px"></div>
            </div>
            <div class="an-skel-panel an-panel--announce">
                <div class="an-skel an-skel-header"></div>
                <div style="display:flex;align-items:center;gap:20px;padding-top:8px">
                    <div class="an-skel an-skel-circle" style="width:148px;height:148px;flex-shrink:0"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:14px">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="an-skel-row">
                            <div class="an-skel an-skel-circle" style="width:9px;height:9px"></div>
                            <div class="an-skel" style="flex:1;height:10px;border-radius:4px"></div>
                            <div class="an-skel" style="width:20px;height:10px;border-radius:4px"></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Services list + Recent activity -->
        <div class="an-grid an-row-26" id="an-row-3" style="display:none">

            <!-- Services status -->
            <section class="an-panel an-panel--svclist">
                <div class="panel-header">
                    <h2 class="section-label">Services Status</h2>
                    <a href="officer_services.php" class="btn-off-sm">Manage ›</a>
                </div>
                <div class="an-panel-scroll-wrap">
                    <div class="an-panel-body">
                        <div class="an-services-list" id="an-services-list"></div>
                    </div>
                </div>
            </section>

            <!-- Recent activity -->
            <section class="an-panel an-panel--activity">
                <div class="panel-header">
                    <h2 class="section-label">Recent Activity</h2>
                </div>
                <div class="an-panel-scroll-wrap">
                    <div class="an-panel-body">
                        <ul class="an-activity-feed" id="an-activity-feed"></ul>
                    </div>
                </div>
            </section>

        </div>

        <!-- Skeleton: Row 3 -->
        <div class="an-grid an-row-26 an-skeleton-layer" id="an-skel-row-3">
            <div class="an-skel-panel an-panel--svclist">
                <div class="an-skel an-skel-header"></div>
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="an-skel-row">
                    <div class="an-skel an-skel-circle" style="width:32px;height:32px;border-radius:8px"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:5px">
                        <div class="an-skel" style="height:11px;width:65%;border-radius:4px"></div>
                        <div class="an-skel" style="height:9px;width:40%;border-radius:4px"></div>
                    </div>
                    <div class="an-skel" style="width:52px;height:20px;border-radius:20px"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="an-skel-panel an-panel--activity">
                <div class="an-skel an-skel-header"></div>
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="an-skel-row">
                    <div class="an-skel an-skel-circle" style="width:28px;height:28px;border-radius:7px"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:5px">
                        <div class="an-skel" style="height:11px;width:85%;border-radius:4px"></div>
                        <div class="an-skel" style="height:9px;width:30%;border-radius:4px"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="../../../scripts/management/officer/officer_analytics.js"></script>

</body>
</html>
