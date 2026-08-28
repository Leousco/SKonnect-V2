<?php
$current = basename($_SERVER['PHP_SELF']);

function modIsActive(string $page): string {
    global $current;
    return $current === $page ? 'active' : '';
}

function modIsGroupOpen(array $pages): string {
    global $current;
    return in_array($current, $pages) ? 'open' : '';
}
?>

<aside class="mod-sidebar">

    <!-- Sidebar Header -->
    <div class="mod-sidebar-header">
        <div class="mod-sidebar-badge">Moderator</div>
        <h2>SKonnect</h2>
        <p>Moderator Panel</p>
    </div>

    <!-- Navigation -->
    <nav class="mod-sidebar-nav" aria-label="Moderator navigation">
        <ul>

            <!-- Dashboard -->
            <li class="<?= modIsActive('mod_dashboard.php') ?>">
                <a href="mod_dashboard.php">
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

        <!-- MODERATION -->
        <div class="sidebar-section-title">Moderation</div>
        <ul>

            <!-- Community -->
            <li class="<?= modIsActive('mod_feed.php') ?>">
                <a href="mod_feed.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>Community Feed</span>
                </a>
            </li>

            <!-- Reports -->
            <li class="<?= modIsActive('mod_queue.php') ?>">
                <a href="mod_queue.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 3l1.664 1.664M21 21l-1.5-1.5m-5.485-1.242L12 17.25 4.5 21V8.742m.164-4.078a2.15 2.15 0 0 1 1.743-1.342 48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185V19.5M4.664 4.664 19.5 19.5"/>
                    </svg>
                    <span>Moderation Queue</span>
                </a>
            </li>

            <!-- Warnings -->
            <li class="<?= modIsActive('mod_sanctions.php') ?>">
                <a href="mod_sanctions.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <span>User Sanctions</span>
                </a>
            </li>

        </ul>

        <!-- SYSTEM (Read-only) -->
        <div class="sidebar-section-title">System</div>
        <ul>

            <!-- Activity Logs -->
            <li class="<?= modIsActive('mod_activity_logs.php') ?>">
                <a href="mod_activity_logs.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6"  y1="20" x2="6"  y2="14"/>
                    </svg>
                    <span>Activity Logs</span>
                </a>
            </li>

        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="mod-sidebar-footer">
        <div class="mod-sidebar-footer-text">Sangguniang Kabataan</div>
        <div class="mod-sidebar-footer-sub">&copy; <?= date('Y') ?> SKonnect</div>
    </div>

</aside>

<script src="../../../scripts/management/moderator/mod_sidebar.js"></script>