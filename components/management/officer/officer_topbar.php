<?php
$officerRole = 'OFFICER';
$initials = '';
if (!empty($officerName)) {
    $parts = explode(' ', trim($officerName));
    foreach ($parts as $p) $initials .= strtoupper($p[0]);
    $initials = substr($initials, 0, 2);
}
?>

<header class="off-topbar">

    
    <div class="off-topbar-left">
        <?php if (!empty($pageBreadcrumb)): ?>
        <nav class="off-topbar-breadcrumb" aria-label="Breadcrumb">
            <?php foreach ($pageBreadcrumb as $i => [$label, $href]): ?>
                <?php if ($i > 0): ?>
                    <span class="off-breadcrumb-sep">/</span>
                <?php endif; ?>
                <?php if ($href): ?>
                    <a href="<?= htmlspecialchars($href) ?>" class="off-breadcrumb-link"><?= htmlspecialchars($label) ?></a>
                <?php else: ?>
                    <span class="off-breadcrumb-current"
                          title="<?= htmlspecialchars($label) ?>"><?= htmlspecialchars($label) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php if (!empty($pageTitle)): ?>
        <h1 class="off-topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <?php endif; ?>
    </div>

    
    <div class="off-topbar-right">

        
        <div class="off-topbar-datetime">
            <span class="off-topbar-date" id="off-date"></span>
            <span class="off-topbar-time" id="off-time"></span>
        </div>

        <div class="off-topbar-divider"></div>

        
        <div class="off-topbar-notif-wrap">
        <button class="off-topbar-notif" type="button" aria-label="Notifications" aria-expanded="false" id="off-notif-btn">
            <svg class="off-notif-bell" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
            <span class="off-notif-badge" id="off-notif-badge" hidden></span>

            
        </button>

        <div class="off-notif-dropdown" id="off-notif-dropdown">
                <div class="off-notif-dropdown-header">
                    <span>Notifications</span>
                    <button class="off-notif-view-all" id="off-notif-view-all" type="button">View all</button>
                </div>
                <ul class="off-notif-list" id="off-notif-preview" aria-live="polite">
                    <li class="off-notif-empty">Loading notifications...</li>
                </ul>
                <p class="off-notif-error off-notif-preview-error" id="off-notif-preview-error" hidden></p>
        </div>
        </div>

        <div class="off-topbar-divider"></div>

        
        <div class="off-topbar-user" role="button" tabindex="0" aria-expanded="false" id="off-user-btn">
            <div class="off-user-avatar">
                <span class="off-user-initials"><?= htmlspecialchars($initials) ?></span>
            </div>
            <div class="off-user-text">
                <span class="off-user-name"><?= htmlspecialchars($officerName ?? 'SK Officer') ?></span>
                <span class="off-user-role"><?= htmlspecialchars($officerRole ?? 'SK Officer') ?></span>
            </div>
            <svg class="off-user-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>

            
            <div class="off-user-dropdown" id="off-user-dropdown" role="menu">
                <div class="off-user-dropdown-header">
                    <div class="off-user-avatar off-user-avatar--lg">
                        <span class="off-user-initials"><?= htmlspecialchars($initials) ?></span>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($officerName ?? 'SK Officer') ?></strong>
                        <span><?= htmlspecialchars($officerRole ?? 'SK Officer') ?></span>
                    </div>
                </div>
                <ul class="off-menu-list">
                    












                    <li>
                        <a href="../../../backend/routes/logout.php" id="off-signout-link" class="off-menu-item off-menu-item--danger">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                            Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</header>

<div class="off-notif-modal-overlay" id="off-notif-modal" aria-hidden="true">
    <section class="off-notif-modal" role="dialog" aria-modal="true" aria-labelledby="off-notif-modal-title">
        <header class="off-notif-modal-header">
            <div>
                <h2 id="off-notif-modal-title">All notifications</h2>
                <span id="off-notif-modal-count"></span>
            </div>
            <button class="off-notif-modal-close" id="off-notif-modal-close" type="button" aria-label="Close notifications">&times;</button>
        </header>
        <div class="off-notif-controls">
            <label class="off-notif-search-label">
                <span class="sr-only">Search notifications</span>
                <svg class="off-notif-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path stroke-linecap="round" d="m16 16 4 4"></path>
                </svg>
                <input id="off-notif-search" type="search" placeholder="Search notifications">
            </label>
            <select id="off-notif-type" aria-label="Filter by notification type">
                <option value="">All types</option>
                <option value="requests">Service requests</option>
                <option value="attention">Needs attention</option>
                <option value="admin_updates">Admin changes</option>
                <option value="events">Events</option>
                <option value="announcements">Announcements</option>
            </select>
            <select id="off-notif-read" aria-label="Filter by read status">
                <option value="">All notifications</option>
                <option value="0">Unread</option>
                <option value="1">Read</option>
            </select>
            <button id="off-notif-mark-all" type="button">Mark all read</button>
        </div>
        <p class="off-notif-modal-error" id="off-notif-modal-error" role="alert" hidden></p>
        <div class="off-notif-modal-list" id="off-notif-modal-list" aria-live="polite">
            <p class="off-notif-empty">Loading notifications...</p>
        </div>
        <button class="off-notif-load-more" id="off-notif-load-more" type="button" hidden>Load more</button>
    </section>
</div>


<div class="off-logout-overlay" id="off-logout-overlay" role="dialog" aria-modal="true"
     aria-labelledby="off-logout-title">
    <div class="off-logout-modal">
        <div class="off-logout-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
            </svg>
        </div>
        <h2 class="off-logout-title" id="off-logout-title">Sign out?</h2>
        <p class="off-logout-body">Are you sure you want to sign out of your account?</p>
        <div class="off-logout-actions">
            <button class="off-logout-cancel" id="off-logout-cancel" type="button">Cancel</button>
            <a class="off-logout-confirm" id="off-logout-confirm" href="../../../backend/routes/logout.php">Sign Out</a>
        </div>
    </div>
</div>

<script src="../../../scripts/management/officer/officer_topbar.js"></script>