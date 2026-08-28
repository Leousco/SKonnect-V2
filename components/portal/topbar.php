<?php
/**
 * Topbar Component — SKonnect Dashboard
 * Usage: include __DIR__ . '/../components/topbar.php';
 *
 * Expected variables (set before including, or defaults are used):
 *   $pageTitle       — e.g. "Dashboard"
 *   $pageBreadcrumb  — e.g. [['Home', '#'], ['Dashboard', null]]
 *   $userName        — e.g. "Juan Dela Cruz"
 *   $userRole        — e.g. "SK Member"
 *   $userAvatar      — path to avatar image (optional)
 */

$pageTitle      = $pageTitle      ?? 'Dashboard';
$pageBreadcrumb = $pageBreadcrumb ?? [['Home', '#'], [$pageTitle, null]];
$userName       = $userName       ?? 'Juan Dela Cruz';
$userRole       = $userRole       ?? 'SK Member';
$userAvatar     = $userAvatar     ?? null;

// ── Fetch live unread count for the badge ─────────────────────────────────────
// $notifCount can be pre-set by the host page; if not, query it now.
if (!isset($notifCount)) {
    $notifCount = 0;
    if (!empty($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../../backend/config/database.php';
            require_once __DIR__ . '/../../backend/models/NotificationModel.php';
            $notifModel = new NotificationModel();
            $stats      = $notifModel->getStats((int) $_SESSION['user_id']);
            $notifCount = (int) ($stats['unread'] ?? 0);
        } catch (Throwable $e) {
            // Silently fall back to 0 — never crash the topbar
            $notifCount = 0;
        }
    }
}

// Generate initials for avatar fallback
$nameParts = explode(' ', trim($userName));
$initials   = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
?>

<!-- TOPBAR COMPONENT -->
<div class="topbar" role="banner">

    <!-- LEFT: Page identity -->
    <div class="topbar-left">
        <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
            <?php foreach ($pageBreadcrumb as $i => [$label, $href]): ?>
                <?php if ($i > 0): ?><span class="breadcrumb-sep" aria-hidden="true">›</span><?php endif; ?>
                <?php if ($href): ?>
                    <a href="<?= htmlspecialchars($href) ?>" class="breadcrumb-link"><?= htmlspecialchars($label) ?></a>
                <?php else: ?>
                    <span class="breadcrumb-current" aria-current="page"
                          title="<?= htmlspecialchars($label) ?>"><?= htmlspecialchars($label) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <h1 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>

    <!-- RIGHT: Actions + user -->
    <div class="topbar-right">

        <!-- Live date/time -->
        <div class="topbar-datetime" id="topbar-datetime" aria-live="polite">
            <span class="topbar-date" id="topbar-date"></span>
            <span class="topbar-time" id="topbar-time"></span>
        </div>

        <!-- Divider -->
        <div class="topbar-divider" aria-hidden="true"></div>

        <!-- Notifications -->
        <div class="topbar-notif" id="topbar-notif-btn" role="button" tabindex="0"
             aria-label="<?= $notifCount ?> unread notifications" aria-haspopup="true" aria-expanded="false">
            <svg class="notif-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notif-badge<?= $notifCount > 0 ? '' : ' notif-badge--hidden' ?>"
                  id="notif-badge"
                  aria-hidden="true"><?= $notifCount > 99 ? '99+' : $notifCount ?></span>

            <!-- Dropdown — list is intentionally empty; topbar.js populates it -->
            <div class="notif-dropdown" id="notif-dropdown" role="menu" aria-label="Notifications">
                <div class="notif-dropdown-header">
                    <span>Notifications</span>
                    <div class="notif-header-actions">
                        <button class="notif-mark-all-btn" id="notif-mark-all-btn"
                                type="button" title="Mark all as read" aria-label="Mark all as read">
                            Mark all read
                        </button>
                        <a href="notifications_page.php" class="notif-view-all">View all</a>
                    </div>
                </div>

                <!-- JS renders into this list -->
                <ul class="notif-list" id="notif-list" role="list"></ul>
            </div>
        </div>

        <!-- Divider -->
        <div class="topbar-divider" aria-hidden="true"></div>

        <!-- User profile chip -->
        <div class="topbar-user" id="topbar-user-btn" role="button" tabindex="0"
             aria-haspopup="true" aria-expanded="false" aria-label="User menu">
            <div class="user-avatar" aria-hidden="true">
                <?php if ($userAvatar): ?>
                    <img src="<?= htmlspecialchars($userAvatar) ?>" alt="<?= htmlspecialchars($userName) ?>">
                <?php else: ?>
                    <span class="user-initials"><?= htmlspecialchars($initials) ?></span>
                <?php endif; ?>
            </div>
            <div class="user-text">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                <span class="user-role"><?= htmlspecialchars($userRole) ?></span>
            </div>
            <svg class="user-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>

            <!-- User dropdown -->
            <div class="user-dropdown" id="user-dropdown" role="menu" aria-label="User options">
                <div class="user-dropdown-header">
                    <div class="user-avatar user-avatar--lg" aria-hidden="true">
                        <?php if ($userAvatar): ?>
                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="">
                        <?php else: ?>
                            <span class="user-initials"><?= htmlspecialchars($initials) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($userName) ?></strong>
                        <span><?= htmlspecialchars($userRole) ?></span>
                    </div>
                </div>
                <ul class="user-menu-list">
                    <!-- <li><a href="profile_page.php" class="user-menu-item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        My Profile
                    </a></li> -->

                    <!-- <li><a href="settings.php" class="user-menu-item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Settings
                    </a></li> -->

                    <!-- <li class="user-menu-divider" aria-hidden="true"></li> -->
                    <li><a href="../../views/public/main.php" id="signout-link" class="user-menu-item user-menu-item--danger" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Sign Out
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- LOGOUT CONFIRMATION MODAL -->
<div class="logout-modal-overlay" id="logout-modal-overlay" role="dialog" aria-modal="true"
     aria-labelledby="logout-modal-title">
    <div class="logout-modal">
        <div class="logout-modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </div>
        <h2 class="logout-modal-title" id="logout-modal-title">Sign out?</h2>
        <p class="logout-modal-body">Are you sure you want to sign out of your account?</p>
        <div class="logout-modal-actions">
            <button class="logout-btn-cancel" id="logout-cancel-btn" type="button">Cancel</button>
            <a class="logout-btn-confirm" id="logout-confirm-btn" href="../../views/public/main.php">Sign Out</a>
        </div>
    </div>
</div>

<script src="../../scripts/portal/topbar.js"></script>