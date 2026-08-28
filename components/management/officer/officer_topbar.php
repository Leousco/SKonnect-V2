<?php
$initials = '';
if (!empty($officerName)) {
    $parts = explode(' ', trim($officerName));
    foreach ($parts as $p) $initials .= strtoupper($p[0]);
    $initials = substr($initials, 0, 2);
}
?>

<header class="off-topbar">

    <!-- LEFT: BREADCRUMB + TITLE -->
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

    <!-- RIGHT: DATETIME + NOTIF + USER -->
    <div class="off-topbar-right">

        <!-- Date/Time -->
        <div class="off-topbar-datetime">
            <span class="off-topbar-date" id="off-date"></span>
            <span class="off-topbar-time" id="off-time"></span>
        </div>

        <div class="off-topbar-divider"></div>

        <!-- Notifications -->
        <div class="off-topbar-notif" role="button" tabindex="0" aria-label="Notifications" aria-expanded="false" id="off-notif-btn">
            <svg class="off-notif-bell" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
            <?php if (!empty($notifCount) && $notifCount > 0): ?>
                <span class="off-notif-badge"><?= (int)$notifCount ?></span>
            <?php endif; ?>

            <!-- Dropdown -->
            <div class="off-notif-dropdown" id="off-notif-dropdown" role="menu">
                <div class="off-notif-dropdown-header">
                    <span>Notifications</span>
                    <a href="#" class="off-notif-view-all">View all</a>
                </div>
                <ul class="off-notif-list">
                    <li class="off-notif-item unread">
                        <div class="off-notif-dot"></div>
                        <div class="off-notif-content">
                            <p>New service request from <strong>Pedro Cruz</strong></p>
                            <span class="off-notif-time">1 hour ago</span>
                        </div>
                    </li>
                    <li class="off-notif-item unread">
                        <div class="off-notif-dot"></div>
                        <div class="off-notif-content">
                            <p>Residency certificate ready for approval</p>
                            <span class="off-notif-time">3 hours ago</span>
                        </div>
                    </li>
                    <li class="off-notif-item">
                        <div class="off-notif-dot"></div>
                        <div class="off-notif-content">
                            <p>Event <strong>Barangay Assembly</strong> is due in 2 days</p>
                            <span class="off-notif-time">Yesterday</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="off-topbar-divider"></div>

        <!-- User Profile -->
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

            <!-- User Dropdown -->
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
                    <!-- <li>
                        <a href="officer_profile.php" class="off-menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            My Profile
                        </a>
                    </li>
                    <li>
                        <a href="officer_settings.php" class="off-menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            Settings
                        </a>
                    </li>
                    <div class="off-menu-divider"></div> -->
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

<!-- LOGOUT CONFIRMATION MODAL -->
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