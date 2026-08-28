<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$sidebarActiveMap = [
    'announcement_view.php' => 'announcements_page.php',
    'bookmark_page.php' => 'announcements_page.php',
    'thread_view.php' => 'feed_page.php',
    'bookmarks_page.php' => 'feed_page.php'
];
$activePage = $sidebarActiveMap[$currentPage] ?? $currentPage;
?>
<aside class="sidebar">

    <div class="sidebar-header">
        <div class="sidebar-logo-mark" aria-hidden="true"></div>
        <h2>SKonnect</h2>
        <p>Community Portal</p>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
        <ul>
            <li class="<?= $activePage == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <div class="sidebar-section-title">Community</div>

            <li class="<?= $activePage == 'announcements_page.php' ? 'active' : '' ?>">
                <a href="announcements_page.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="<?= $activePage == 'feed_page.php' ? 'active' : '' ?>">
                <a href="feed_page.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>Community Feed</span>
                </a>
            </li>

            <li class="<?= $activePage == 'services_page.php' ? 'active' : '' ?>">
                <a href="services_page.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    <span>Services</span>
                </a>
            </li>

            <div class="sidebar-section-title">Account</div>

            <li class="<?= $activePage == 'my_requests_page.php' ? 'active' : '' ?>">
                <a href="my_requests_page.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span>My Requests</span>
                </a>
            </li>

            <li class="<?= $activePage == 'notifications_page.php' ? 'active' : '' ?>">
                <a href="notifications_page.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span>Notifications</span>
                </a>
            </li>

            <li class="<?= $activePage == 'profile_page.php' ? 'active' : '' ?>">
                <a href="profile_page.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-footer-text">Sangguniang Kabataan</div>
        <div class="sidebar-footer-sub">© <?= date('Y') ?> SKonnect</div>
    </div>

</aside>