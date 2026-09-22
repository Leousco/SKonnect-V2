<?php
$initials = '';
if (!empty($modName)) {
    $parts = explode(' ', trim($modName));
    foreach ($parts as $p) $initials .= strtoupper($p[0]);
    $initials = substr($initials, 0, 2);
}
?>

<header class="mod-topbar">

    
    <div class="mod-topbar-left">
        <?php if (!empty($pageBreadcrumb)): ?>
        <nav class="mod-topbar-breadcrumb" aria-label="Breadcrumb">
            <?php foreach ($pageBreadcrumb as $i => [$label, $href]): ?>
                <?php if ($i > 0): ?>
                    <span class="mod-breadcrumb-sep">/</span>
                <?php endif; ?>
                <?php if ($href): ?>
                    <a href="<?= htmlspecialchars($href) ?>" class="mod-breadcrumb-link"><?= htmlspecialchars($label) ?></a>
                <?php else: ?>
                    <span class="mod-breadcrumb-current"
                          title="<?= htmlspecialchars($label) ?>"><?= htmlspecialchars($label) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php if (!empty($pageTitle)): ?>
        <h1 class="mod-topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <?php endif; ?>
    </div>

    
    <div class="mod-topbar-right">

        
        <div class="mod-topbar-datetime">
            <span class="mod-topbar-date" id="mod-date"></span>
            <span class="mod-topbar-time" id="mod-time"></span>
        </div>

        <div class="mod-topbar-divider"></div>

        
        <div class="mod-topbar-notif" role="button" tabindex="0" aria-label="Notifications" aria-expanded="false" id="mod-notif-btn">
            <svg class="mod-notif-bell" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
            <?php if (!empty($notifCount) && $notifCount > 0): ?>
                <span class="mod-notif-badge"><?= (int)$notifCount ?></span>
            <?php endif; ?>

            
            <div class="mod-notif-dropdown" id="mod-notif-dropdown" role="menu">
                <div class="mod-notif-dropdown-header">
                    <span>Notifications</span>
                    <a href="#" class="mod-notif-view-all">View all</a>
                </div>
                <ul class="mod-notif-list">
                    <li class="mod-notif-item unread">
                        <div class="mod-notif-dot"></div>
                        <div class="mod-notif-content">
                            <p>New report submitted by <strong>juan_d</strong></p>
                            <span class="mod-notif-time">1 hour ago</span>
                        </div>
                    </li>
                    <li class="mod-notif-item unread">
                        <div class="mod-notif-dot"></div>
                        <div class="mod-notif-content">
                            <p>Thread flagged for spam content</p>
                            <span class="mod-notif-time">3 hours ago</span>
                        </div>
                    </li>
                    <li class="mod-notif-item">
                        <div class="mod-notif-dot"></div>
                        <div class="mod-notif-content">
                            <p>Warning acknowledged by <strong>pedro_c</strong></p>
                            <span class="mod-notif-time">Yesterday</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mod-topbar-divider"></div>

        
        <div class="mod-topbar-user" role="button" tabindex="0" aria-expanded="false" id="mod-user-btn">
            <div class="mod-user-avatar">
                <span class="mod-user-initials"><?= htmlspecialchars($initials) ?></span>
            </div>
            <div class="mod-user-text">
                <span class="mod-user-name"><?= htmlspecialchars($modName ?? 'Moderator') ?></span>
                <span class="mod-user-role"><?= htmlspecialchars($modRole ?? 'Moderator') ?></span>
            </div>
            <svg class="mod-user-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>

            
            <div class="mod-user-dropdown" id="mod-user-dropdown" role="menu">
                <div class="mod-user-dropdown-header">
                    <div class="mod-user-avatar mod-user-avatar--lg">
                        <span class="mod-user-initials"><?= htmlspecialchars($initials) ?></span>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($modName ?? 'Moderator') ?></strong>
                        <span><?= htmlspecialchars($modRole ?? 'Moderator') ?></span>
                    </div>
                </div>
                <ul class="mod-menu-list">
                    












                    <li>
                        <a href="../../../backend/routes/logout.php" id="mod-signout-link" class="mod-menu-item mod-menu-item--danger">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                            Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</header>

<div class="mod-logout-overlay" id="mod-logout-overlay" role="dialog" aria-modal="true" aria-labelledby="mod-logout-title">
    <div class="mod-logout-modal">
        <div class="mod-logout-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
            </svg>
        </div>
        <h2 class="mod-logout-title" id="mod-logout-title">Sign out?</h2>
        <p class="mod-logout-body">Are you sure you want to sign out of your account?</p>
        <div class="mod-logout-actions">
            <button class="mod-logout-cancel" id="mod-logout-cancel" type="button">Cancel</button>
            <a class="mod-logout-confirm" id="mod-logout-confirm" href="../../../backend/routes/logout.php">Sign Out</a>
        </div>
    </div>
</div>

<script src="../../../scripts/management/moderator/mod_topbar.js"></script>