<?php
// views/portal/notifications_page.php

require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

require_once __DIR__ . '/../../backend/models/NotificationModel.php';

$notifModel = new NotificationModel();
$userId     = (int) $_SESSION['user_id'];

$stats         = $notifModel->getStats($userId);
$notifications = $notifModel->getByUser($userId);

// ── Type config map ────────────────────────────────────────────────────────
$typeMap = [
    'service'      => ['ind' => 'type-service',      'icon' => 'icon-service',      'emoji' => '✅',  'tag' => 'tag-service',      'label' => 'Service Update'],
    'announcement' => ['ind' => 'type-announcement', 'icon' => 'icon-announcement', 'emoji' => '📣', 'tag' => 'tag-announcement', 'label' => 'Announcement'],
    'new_service'  => ['ind' => 'type-new-service',  'icon' => 'icon-new-service',  'emoji' => '🆕', 'tag' => 'tag-new-service',  'label' => 'New Service'],
    'thread'       => ['ind' => 'type-thread',       'icon' => 'icon-thread',       'emoji' => '💬', 'tag' => 'tag-thread',       'label' => 'Community Thread'],
    'system'       => ['ind' => 'type-system',       'icon' => 'icon-system',       'emoji' => '🔔', 'tag' => 'tag-system',       'label' => 'System'],
];

function getTypeCfg(array $typeMap, string $type, string $title): array {
    $cfg = $typeMap[$type] ?? $typeMap['system'];
    // Visually distinguish rejected service notifications
    if ($type === 'service' && stripos($title, 'Declined') !== false) {
        $cfg['ind']   = 'type-service type-rejected';
        $cfg['icon']  = 'icon-rejected';
        $cfg['emoji'] = '❌';
    }
    return $cfg;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Notifications</title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/notifications_page.css">
</head>
<body>

<div class="dashboard-layout">

    <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

    <main class="dashboard-content">

    <?php
    $pageTitle      = 'Notifications';
    $pageBreadcrumb = [['Home', '#'], ['Notifications', null]];
    $userName       = $_SESSION['user_name'] ?? 'Guest';
    $userRole       = 'Resident';
    $notifCount     = $stats['unread'];
    include __DIR__ . '/../../components/portal/topbar.php';
    ?>

        <!-- STAT WIDGETS -->
        <section class="dashboard-widgets">
            <div class="widget-card">
                <h3>Total</h3>
                <p class="widget-number"><?= $stats['total'] ?></p>
                <span class="widget-sub">All notifications</span>
            </div>
            <div class="widget-card">
                <h3>Unread</h3>
                <p class="widget-number"><?= $stats['unread'] ?></p>
                <span class="widget-sub">Needs your attention</span>
            </div>
            <div class="widget-card">
                <h3>This Week</h3>
                <p class="widget-number"><?= $stats['this_week'] ?></p>
                <span class="widget-sub">Last 7 days</span>
            </div>
            <div class="widget-card">
                <h3>Dismissed</h3>
                <p class="widget-number"><?= $stats['dismissed'] ?></p>
                <span class="widget-sub">Hidden notifications</span>
            </div>
        </section>

        <!-- CONTROLS -->
        <section class="announcements-controls">
            <div class="controls-left">
                <div class="search-wrap">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="notif-search" placeholder="Search notifications..." class="ann-search-input">
                </div>
            </div>
            <div class="controls-right">
                <select id="notif-type" class="ann-select">
                    <option value="all">All Types</option>
                    <option value="service">Service Updates</option>
                    <option value="announcement">Announcements</option>
                    <option value="new_service">New Services</option>
                    <option value="thread">Community Threads</option>
                    <option value="system">System</option>
                </select>
                <select id="notif-read" class="ann-select">
                    <option value="all">All</option>
                    <option value="unread">Unread Only</option>
                    <option value="read">Read Only</option>
                </select>
                <button class="btn-secondary-portal" id="mark-all-btn" title="Mark all as read">✓ Mark All Read</button>
            </div>
        </section>

        <!-- NOTIFICATIONS LIST -->
        <section class="announcements-section">
            <div class="notif-list-header">
                <h2 class="section-label">All Notifications</h2>
                <span class="notif-unread-count <?= $stats['unread'] === 0 ? 'is-zero' : '' ?>" id="unread-count-label">
                    <?= $stats['unread'] === 0 ? 'All read' : $stats['unread'] . ' unread' ?>
                </span>
            </div>

            <?php if (!empty($notifications)): ?>
            <div class="notif-list-wrap" id="notif-list">

                <?php foreach ($notifications as $notif):
                    $type      = $notif['type'];
                    $isUnread  = !(bool) $notif['is_read'];
                    $isOfficial = (bool) $notif['is_official'];
                    $cfg       = getTypeCfg($typeMap, $type, $notif['title']);

                    $titleSafe   = htmlspecialchars($notif['title'],   ENT_QUOTES);
                    $messageSafe = htmlspecialchars($notif['message'],  ENT_QUOTES);
                    $preview     = htmlspecialchars(mb_strimwidth($notif['message'], 0, 130, '…'), ENT_QUOTES);
                    $timeIso     = htmlspecialchars($notif['created_at']);
                    $timeDisplay = date('M j, Y · g:i A', strtotime($notif['created_at']));
                    $link        = htmlspecialchars($notif['link'] ?? '', ENT_QUOTES);
                    $rowClass    = 'notif-item-row' . ($isUnread ? ' notif-unread' : '');
                ?>
                <div class="<?= $rowClass ?>"
                     data-type="<?= htmlspecialchars($type) ?>"
                     data-id="<?= (int) $notif['id'] ?>"
                     data-title="<?= $titleSafe ?>"
                     data-body="<?= $messageSafe ?>"
                     data-time="<?= $timeIso ?>"
                     data-link="<?= $link ?>">

                    <div class="notif-type-indicator <?= $cfg['ind'] ?>"></div>

                    <div class="notif-icon-wrap <?= $cfg['icon'] ?>"><?= $cfg['emoji'] ?></div>

                    <div class="notif-content-wrap">
                        <div class="notif-row-top">
                            <span class="notif-type-tag <?= $cfg['tag'] ?>"><?= $cfg['label'] ?></span>
                            <?php if ($isOfficial): ?>
                            <span class="notif-official-badge">⭐ Official Response</span>
                            <?php endif; ?>
                            <?php if ($isUnread): ?>
                            <span class="notif-unread-dot" aria-label="Unread"></span>
                            <?php endif; ?>
                        </div>
                        <p class="notif-title"><?= $titleSafe ?></p>
                        <p class="notif-preview"><?= $preview ?></p>
                        <div class="notif-meta">
                            <time class="notif-time" datetime="<?= $timeIso ?>"><?= $timeDisplay ?></time>
                            <?php if ($link): ?>
                            <a href="<?= $link ?>" class="notif-link">View →</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="notif-actions">
                        <?php if ($isUnread): ?>
                        <button class="notif-action-btn mark-read-btn" title="Mark as read" data-id="<?= (int) $notif['id'] ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                        <?php endif; ?>
                        <button class="notif-action-btn dismiss-btn" title="Dismiss" data-id="<?= (int) $notif['id'] ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
            <?php endif; ?>

            <!-- EMPTY STATE -->
            <div class="notif-empty" id="notif-empty" <?= !empty($notifications) ? 'style="display:none;"' : '' ?>>
                <div class="notif-empty-icon">🔔</div>
                <p class="notif-empty-title">No notifications found</p>
                <p class="notif-empty-sub">Try adjusting your filters or check back later.</p>
            </div>

            <!-- PAGINATION -->
            <?php if (!empty($notifications)): ?>
            <div class="pagination-section" style="margin-top: 24px;">
                <button class="page-btn" id="prev-btn" disabled>&#8249; Previous</button>
                <div class="page-numbers" id="page-numbers"></div>
                <button class="page-btn" id="next-btn">Next &#8250;</button>
            </div>
            <?php endif; ?>
        </section>

    </main>
</div>

<!-- NOTIFICATION DETAIL MODAL -->
<div class="modal-overlay" id="modal-overlay" style="display:none;" aria-modal="true" role="dialog" aria-labelledby="modal-notif-title">
    <div class="modal-box">

        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon" id="modal-notif-icon">🔔</div>
                <div>
                    <h3 id="modal-notif-title">Notification</h3>
                    <p class="modal-subtitle" id="modal-notif-time">—</p>
                </div>
            </div>
            <button class="modal-close" id="modal-close" aria-label="Close">&times;</button>
        </div>

        <div class="modal-body" style="padding: 24px;">
            <div class="notif-modal-tag-row">
                <span class="notif-type-tag" id="modal-type-tag">—</span>
                <span class="notif-official-badge" id="modal-official-badge" style="display:none; margin-left:8px;">⭐ Official Response</span>
            </div>
            <p class="notif-modal-body-text" id="modal-body-text">—</p>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary-portal" id="modal-close-btn">Close</button>
            <a href="#" class="btn-primary-portal" id="modal-action-link">Go to Page →</a>
        </div>

    </div>
</div>

<script src="../../scripts/portal/notifications_page.js"></script>

</body>
</html>