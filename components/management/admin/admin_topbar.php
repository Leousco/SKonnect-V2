<?php

require_once __DIR__ . '/../../../backend/config/database.php';

$_topbarUser = null;
try {
    $_topbarDb   = new Database();
    $_topbarConn = $_topbarDb->getConnection();
    $_topbarStmt = $_topbarConn->prepare("
        SELECT first_name, last_name, email, role
        FROM users WHERE id = :id
    ");
    $_topbarStmt->execute([':id' => $_SESSION['user_id'] ?? 0]);
    $_topbarUser = $_topbarStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {  }

$_adminFullName = $_topbarUser
    ? trim(($_topbarUser['first_name'] ?? '') . ' ' . ($_topbarUser['last_name'] ?? ''))
    : ($adminName ?? 'Admin');

$_adminRoleLabel = match($_topbarUser['role'] ?? '') {
    'admin'      => 'System Admin',
    'sk_officer' => 'SK Officer',
    'moderator'  => 'Moderator',
    default      => $adminRole ?? 'Administrator',
};


$_initials = '';
foreach (explode(' ', trim($_adminFullName)) as $p) {
    if ($p) $_initials .= strtoupper($p[0]);
}
$_initials = substr($_initials, 0, 2);


$_unreadCount = 0;
try {
    $_nStmt = $_topbarConn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $_nStmt->execute([':id' => $_SESSION['user_id'] ?? 0, ':uid' => $_SESSION['user_id'] ?? 0]);
    $_unreadCount = (int)$_nStmt->fetchColumn();
} catch (Exception $e) {  }
?>

<header class="admin-topbar">

    
    <div class="admin-topbar-left">
        <?php if (!empty($pageBreadcrumb)): ?>
        <nav class="admin-topbar-breadcrumb" aria-label="Breadcrumb">
            <?php foreach ($pageBreadcrumb as $i => [$label, $href]): ?>
                <?php if ($i > 0): ?><span class="admin-breadcrumb-sep">/</span><?php endif; ?>
                <?php if ($href): ?>
                    <a href="<?= htmlspecialchars($href) ?>" class="admin-breadcrumb-link"><?= htmlspecialchars($label) ?></a>
                <?php else: ?>
                    <span class="admin-breadcrumb-current"
                          title="<?= htmlspecialchars($label) ?>"><?= htmlspecialchars($label) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <?php if (!empty($pageTitle)): ?>
        <h1 class="admin-topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <?php endif; ?>
    </div>

    
    <div class="admin-topbar-right">

        
        <div class="admin-topbar-datetime">
            <span class="admin-topbar-date" id="admin-date"></span>
            <span class="admin-topbar-time" id="admin-time"></span>
        </div>

        <div class="admin-topbar-divider"></div>

        
        <div class="admin-topbar-notif" role="button" tabindex="0"
             aria-label="Notifications" aria-expanded="false" id="admin-notif-btn">
            <svg class="admin-notif-bell" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
            <span class="admin-notif-badge" id="admin-notif-badge"
                  style="<?= $_unreadCount === 0 ? 'display:none;' : '' ?>">
                <?= $_unreadCount ?>
            </span>

            
            <div class="admin-notif-dropdown" id="admin-notif-dropdown" role="menu">
                <div class="admin-notif-dropdown-header">
                    <span>Notifications</span>
                    <a href="#" class="admin-notif-view-all" id="admin-notif-mark-all">Mark all read</a>
                </div>
                <ul class="admin-notif-list" id="admin-notif-list">
                    <li style="padding:20px;text-align:center;color:#6b7280;font-size:12px;">Loading…</li>
                </ul>
            </div>
        </div>

        <div class="admin-topbar-divider"></div>

        
        <div class="admin-topbar-user" role="button" tabindex="0"
             aria-expanded="false" id="admin-user-btn">
            <div class="admin-user-avatar">
                <span class="admin-user-initials"><?= htmlspecialchars($_initials) ?></span>
            </div>
            <div class="admin-user-text">
                <span class="admin-user-name"><?= htmlspecialchars($_adminFullName) ?></span>
                <span class="admin-user-role"><?= htmlspecialchars($_adminRoleLabel) ?></span>
            </div>
            <svg class="admin-user-chevron" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>

            
            <div class="admin-user-dropdown" id="admin-user-dropdown" role="menu">
                <div class="admin-user-dropdown-header">
                    <div class="admin-user-avatar admin-user-avatar--lg">
                        <span class="admin-user-initials"><?= htmlspecialchars($_initials) ?></span>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($_adminFullName) ?></strong>
                        <span><?= htmlspecialchars($_adminRoleLabel) ?></span>
                    </div>
                </div>
                <ul class="admin-menu-list">
                    






                    <li>
                        <a href="../../../backend/routes/logout.php" class="admin-menu-item admin-menu-item--danger">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                            Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</header>

<script src="../../../scripts/management/admin/admin_topbar.js?v=<?= time() ?>"></script>