<?php
$current = basename($_SERVER['PHP_SELF']);

function isActive(string $page): string
{
    global $current;
    return $current === $page ? 'active' : '';
}

function isGroupOpen(array $pages): string
{
    global $current;
    return in_array($current, $pages) ? 'open' : '';
}
?>

<aside class="admin-sidebar">

    <!-- Sidebar Header -->
    <div class="admin-sidebar-header">
        <div class="admin-sidebar-badge"> System Admin</div>
        <h2>SKonnect</h2>
        <p>Admin Panel</p>
    </div>

    <!-- Navigation -->
    <nav class="admin-sidebar-nav" aria-label="Admin navigation">
        <ul>

            <!-- Dashboard -->
            <li class="<?= isActive('admin_dashboard.php') ?>">
                <a href="admin_dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Operational Modules (Optional Access) -->
            <div class="sidebar-section-title">Operations</div>
            <ul>
                <!-- Announcements -->
                <li class="<?= isActive('admin_announcements.php') ?>">
                    <a href="admin_announcements.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                        <span>Announcements</span>
                    </a>
                </li>

                <!-- Services -->
                <li class="has-submenu <?= isGroupOpen(['admin_manage_services.php', 'admin_service_requests.php']) ?>">
                    <button class="submenu-toggle" aria-expanded="<?= isGroupOpen(['admin_manage_services.php', 'admin_service_requests.php']) === 'open' ? 'true' : 'false' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="12" y1="18" x2="12" y2="12" />
                            <line x1="9" y1="15" x2="15" y2="15" />
                        </svg>
                        <span>Services</span>
                        <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                    <ul class="submenu">
                        <li class="<?= isActive('admin_manage_services.php') ?>">
                            <a href="admin_manage_services.php">Manage Services</a>
                        </li>
                        <li class="<?= isActive('admin_service_requests.php') ?>">
                            <a href="admin_service_requests.php">Service Requests</a>
                        </li>
                    </ul>
                </li>

                <!-- Community -->
                <li class="has-submenu <?= isGroupOpen(['admin_threads.php', 'admin_reports.php']) ?>">
                    <button class="submenu-toggle" aria-expanded="<?= isGroupOpen(['admin_threads.php', 'admin_reports.php']) === 'open' ? 'true' : 'false' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <span>Community</span>
                        <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                    <ul class="submenu">
                        <li class="<?= isActive('admin_threads.php') ?>">
                            <a href="admin_threads.php">Threads</a>
                        </li>
                        <li class="<?= isActive('admin_reports.php') ?>">
                            <a href="admin_reports.php">Reports</a>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- System Management -->
            <div class="sidebar-section-title">System</div>
            <ul>

                <!-- Users -->
                <li class="<?= isActive('admin_manage_users.php') ?>">
                    <a href="admin_manage_users.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                        </svg>
                        <span>User Management</span>
                    </a>
                </li>

                <!-- Reports & Logs -->
                <li class="has-submenu <?= isGroupOpen(['admin_analytics.php', 'admin_activity_logs.php']) ?>">
                    <button class="submenu-toggle" aria-expanded="<?= isGroupOpen(['admin_analytics.php', 'admin_activity_logs.php']) === 'open' ? 'true' : 'false' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="20" x2="18" y2="10" />
                            <line x1="12" y1="20" x2="12" y2="4" />
                            <line x1="6" y1="20" x2="6" y2="14" />
                        </svg>
                        <span>Reports &amp; Logs</span>
                        <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                    <ul class="submenu">
                        <li class="<?= isActive('admin_analytics.php') ?>">
                            <a href="admin_analytics.php">Analytics</a>
                        </li>
                        <li class="<?= isActive('admin_activity_logs.php') ?>">
                            <a href="admin_activity_logs.php">Activity Logs</a>
                        </li>
                    </ul>
                </li>

                <!-- Settings -->
                <!-- <li class="<?= isActive('admin_settings.php') ?>">
                    <a href="admin_settings.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33 1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82 1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <span>Settings</span>
                    </a>
                </li> -->

            </ul>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="admin-sidebar-footer">
        <div class="admin-sidebar-footer-text">Sangguniang Kabataan</div>
        <div class="admin-sidebar-footer-sub">© <?= date('Y') ?> SKonnect</div>
    </div>

</aside>

<script src="../../../scripts/management/admin/admin_sidebar.js"></script>