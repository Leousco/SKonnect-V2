<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireRole('resident');

$pageTitle      = 'Dashboard';
$pageBreadcrumb = [['Home', '#'], ['Dashboard', null]];
$userName       = $_SESSION['user_name'] ?? 'Guest';
$userRole       = 'Resident';
$notifCount     = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Dashboard</title>
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/dashboard.css">
</head>

<body>

    <div class="dashboard-layout">

        <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

        <main class="dashboard-content">

            <?php include __DIR__ . '/../../components/portal/topbar.php'; ?>

            <!-- WIDGETS -->
            <section class="dashboard-widgets">

                <div class="widget-card" id="widget-requests">
                    <div class="widget-icon-wrap requests">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="12" y1="18" x2="12" y2="12" />
                            <line x1="9" y1="15" x2="15" y2="15" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <h3>Active Requests</h3>
                        <span class="widget-number" id="stat-requests">—</span>
                        <span class="widget-sub">Pending or action required</span>
                    </div>
                </div>

                <div class="widget-card" id="widget-posts">
                    <div class="widget-icon-wrap posts">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <h3>Community Posts</h3>
                        <span class="widget-number" id="stat-posts">—</span>
                        <span class="widget-sub">Threads you've created</span>
                    </div>
                </div>

                <div class="widget-card" id="widget-events">
                    <div class="widget-icon-wrap events">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <h3>Upcoming Events</h3>
                        <span class="widget-number" id="stat-events">—</span>
                        <span class="widget-sub">Scheduled from today</span>
                    </div>
                </div>

                <div class="widget-card" id="widget-notifs">
                    <div class="widget-icon-wrap notifs">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                    </div>
                    <div class="widget-body">
                        <h3>Notifications</h3>
                        <span class="widget-number" id="stat-notifs">—</span>
                        <span class="widget-sub">Unread alerts</span>
                    </div>
                </div>

            </section>

            <!-- CALENDAR -->
            <section class="calendar-section">
                <h2 class="section-label">Upcoming Events</h2>

                <div class="calendar">
                    <div class="calendar-header">
                        <button class="calendar-nav-btn prev-month" aria-label="Previous month">&#8249;</button>
                        <span class="month-year"></span>
                        <button class="calendar-nav-btn next-month" aria-label="Next month">&#8250;</button>
                    </div>
                    <div class="calendar-days">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>
                    <div class="calendar-dates"></div>
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <div class="legend-dot today"></div>
                            <span>Today</span>
                        </div>
                        <div id="legend-events"></div>
                    </div>
                </div>

                <div class="events-list-wrap">
                    <h3 class="events-list-title">Events This Month</h3>
                    <ul class="events-list" id="events-list"></ul>
                    <p class="events-empty" id="events-empty" style="display:none;">No events scheduled for this month.</p>
                </div>
            </section>

            <!-- LOWER: ACTIVITY + ANNOUNCEMENTS -->
            <div class="dashboard-lower">

                <section class="recent-activity">
                    <h2 class="section-label">Recent Activity</h2>
                    <div class="activity-wrapper"> 
                        <div class="activity-list" id="activity-list">
                            <div class="activity-skeleton"></div>
                            <div class="activity-skeleton"></div>
                            <div class="activity-skeleton"></div>
                        </div>
                    </div>
                </section>

                <section class="mini-announcements">
                    <h2 class="section-label">Latest Announcements</h2>
                    <ul class="announcement-list" id="announcement-list">
                        <li class="activity-skeleton"></li>
                        <li class="activity-skeleton"></li>
                        <li class="activity-skeleton"></li>
                    </ul>
                    <a href="announcements_page.php" class="btn-small">View All Announcements &rsaquo;</a>
                </section>

            </div>

        </main>
    </div>

    <script src="../../scripts/portal/dashboard.js"></script>
</body>

</html>