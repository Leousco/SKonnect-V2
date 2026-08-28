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
    <link rel="stylesheet" href="../../../styles/management/officer/officer_sidebar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_topbar.css">
    <link rel="stylesheet" href="../../../styles/management/officer/officer_analytics.css">
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

        <!-- ── PERIOD SELECTOR ──────────────────────────────── -->
        <div class="an-period-bar">
            <div class="an-period-tabs">
                <button class="an-period-tab active" data-period="month">This Month</button>
                <button class="an-period-tab" data-period="quarter">This Quarter</button>
                <button class="an-period-tab" data-period="year">This Year</button>
            </div>
            <div class="an-period-right">
                <span class="an-last-updated">Last updated: <strong>Mar 27, 2026 — 8:00 AM</strong></span>
                <button class="an-export-btn" id="an-export-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Export Report
                </button>
            </div>
        </div>

        <!-- ── KPI WIDGETS ──────────────────────────────────── -->
        <section class="off-widgets">

            <div class="off-widget-card widget-cyan">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Total Requests</span>
                    <p class="widget-number" data-kpi="total-requests">42</p>
                    <span class="widget-trend up">&#9650; 12% vs last period</span>
                </div>
            </div>

            <div class="off-widget-card widget-green">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Approval Rate</span>
                    <p class="widget-number" data-kpi="approval-rate">91%</p>
                    <span class="widget-trend up">&#9650; Up from 87%</span>
                </div>
            </div>

            <div class="off-widget-card widget-amber">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Avg. Processing Time</span>
                    <p class="widget-number" data-kpi="avg-days">3.4<span class="widget-unit">d</span></p>
                    <span class="widget-trend up">&#9650; Faster by 0.6d</span>
                </div>
            </div>

            <div class="off-widget-card widget-indigo">
                <div class="widget-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div class="widget-body">
                    <span class="widget-label">Announcements</span>
                    <p class="widget-number" data-kpi="announcements">7</p>
                    <span class="widget-trend neutral">3 this month</span>
                </div>
            </div>

        </section>

        <!-- ── ROW 1: REQUEST VOLUME + REQUEST BREAKDOWN ────── -->
        <div class="an-row">

            <!-- Request volume sparkline -->
            <section class="an-panel an-panel--grow">
                <div class="panel-header">
                    <h2 class="section-label">Request Volume</h2>
                    <span class="an-chart-period" id="an-volume-period">Mar 2026</span>
                </div>
                <div class="an-sparkline-wrap">
                    <canvas id="chart-volume" height="130"></canvas>
                </div>
                <div class="an-spark-stats">
                    <div class="an-spark-stat">
                        <span class="an-spark-val" data-kpi="spark-pending">8</span>
                        <span class="an-spark-lbl">Pending</span>
                    </div>
                    <div class="an-spark-stat">
                        <span class="an-spark-val" data-kpi="spark-processing">6</span>
                        <span class="an-spark-lbl">Processing</span>
                    </div>
                    <div class="an-spark-stat">
                        <span class="an-spark-val" data-kpi="spark-approved">24</span>
                        <span class="an-spark-lbl">Approved</span>
                    </div>
                    <div class="an-spark-stat">
                        <span class="an-spark-val" data-kpi="spark-declined">4</span>
                        <span class="an-spark-lbl">Declined</span>
                    </div>
                </div>
            </section>

            <!-- Requests by service type — horizontal bars -->
            <section class="an-panel an-panel--side">
                <div class="panel-header">
                    <h2 class="section-label">By Service Type</h2>
                </div>
                <div class="an-bar-list" id="an-bar-service">
                    <!-- Injected by JS -->
                </div>
            </section>

        </div>

        <!-- ── ROW 2: EVENTS SUMMARY + ANNOUNCEMENTS BREAKDOWN -->
        <div class="an-row">

            <!-- Events summary -->
            <section class="an-panel">
                <div class="panel-header">
                    <h2 class="section-label">Events Overview</h2>
                    <a href="officer_events.php" class="btn-off-sm">Manage &rsaquo;</a>
                </div>
                <div class="an-events-grid" id="an-events-grid">
                    <!-- Injected by JS -->
                </div>
                <div class="an-events-chart-wrap">
                    <canvas id="chart-events"></canvas>
                </div>
            </section>

            <!-- Announcements breakdown -->
            <section class="an-panel">
                <div class="panel-header">
                    <h2 class="section-label">Announcements</h2>
                    <a href="officer_announcements.php" class="btn-off-sm">Manage &rsaquo;</a>
                </div>
                <div class="an-donut-wrap">
                    <canvas id="chart-announcements" height="180"></canvas>
                    <div class="an-donut-legend" id="an-ann-legend">
                        <!-- Injected by JS -->
                    </div>
                </div>
            </section>

        </div>

        <!-- ── ROW 3: SERVICES STATUS + RECENT ACTIVITY ─────── -->
        <div class="an-row">

            <!-- Services status -->
            <section class="an-panel">
                <div class="panel-header">
                    <h2 class="section-label">Services Status</h2>
                    <a href="officer_services.php" class="btn-off-sm">Manage &rsaquo;</a>
                </div>
                <div class="an-services-list" id="an-services-list">
                    <!-- Injected by JS -->
                </div>
            </section>

            <!-- Recent activity feed -->
            <section class="an-panel an-panel--grow">
                <div class="panel-header">
                    <h2 class="section-label">Recent Activity</h2>
                </div>
                <ul class="an-activity-feed" id="an-activity-feed">
                    <!-- Injected by JS -->
                </ul>
            </section>

        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="../../../scripts/management/officer/officer_analytics.js"></script>

</body>
</html>