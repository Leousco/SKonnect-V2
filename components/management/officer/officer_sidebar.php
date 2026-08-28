<?php
$current = basename($_SERVER['PHP_SELF']);

function offIsActive(string $page): string {
    global $current;
    return $current === $page ? 'active' : '';
}

function offIsGroupOpen(array $pages): string {
    global $current;
    return in_array($current, $pages) ? 'open' : '';
}
?>

<aside class="off-sidebar">

    <!-- Sidebar Header -->
    <div class="off-sidebar-header">
        <div class="off-sidebar-badge">SK Officer</div>
        <h2>SKonnect</h2>
        <p>Officer Panel</p>
    </div>

    <!-- Navigation -->
    <nav class="off-sidebar-nav" aria-label="SK Officer navigation">
        <ul>

            <!-- Dashboard -->
            <li class="<?= offIsActive('officer_dashboard.php') ?>">
                <a href="officer_dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>

        </ul>

        <!-- MANAGEMENT -->
        <div class="sidebar-section-title">Operations</div>
        <ul>

            <!-- Announcements -->
            <li class="<?= offIsActive('officer_announcements.php') ?>">
                <a href="officer_announcements.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                    <span>Announcements</span>
                </a>
            </li>

            <!-- Services -->
            <li class="<?= offIsActive('officer_services.php') ?>">
                <a href="officer_services.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                    <span>Services</span>
                </a>
            </li>

            <!-- Requests -->
            <li class="<?= offIsActive('officer_requests.php') ?>">
                <a href="officer_requests.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    <span>Requests</span>
                </a>
            </li>

            <!-- Events -->
            <li class="<?= offIsActive('officer_events.php') ?>">
                <a href="officer_events.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                    </svg>
                    <span>Events</span>
                </a>
            </li>

        </ul>

        <!-- INSIGHTS -->
        <div class="sidebar-section-title">Insights</div>
        <ul>

            <!-- Analytics -->
            <li class="<?= offIsActive('officer_analytics.php') ?>">
                <a href="officer_analytics.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/>
                    </svg>
                    <span>Analytics</span>
                </a>
            </li>

        </ul>

        <!-- ACCOUNT -->
        <div class="sidebar-section-title">User Management</div>
        <ul>

            <!-- Profile -->
            <li class="<?= offIsActive('officer_users.php') ?>">
                <a href="officer_users.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    <span>Users</span>
                </a>
            </li>

        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="off-sidebar-footer">
        <div class="off-sidebar-footer-text">Sangguniang Kabataan</div>
        <div class="off-sidebar-footer-sub">&copy; <?= date('Y') ?> SKonnect</div>
    </div>

</aside>

<script src="../../../scripts/management/officer/officer_sidebar.js"></script>