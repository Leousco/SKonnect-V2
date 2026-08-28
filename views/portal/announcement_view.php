<!-- PORTAL ANNOUNCEMENT VIEW -->

<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

require_once __DIR__ . '/../../backend/models/AnnouncementModel.php';

$annModel = new AnnouncementModel();

$id  = (int) ($_GET['id'] ?? 0);
$ann = $id ? $annModel->getById($id) : null;

// 404 if not found or archived
if (!$ann || $ann['status'] === 'archived') {
    header('Location: announcements_page.php');
    exit;
}

$files = $annModel->getFiles($id);

// Meta for topbar
$pageTitle      = htmlspecialchars($ann['title']);
$pageBreadcrumb = [
    ['Home',          '../../views/portal/dashboard.php'],
    ['Announcements', 'announcements_page.php'],
    [$ann['title'],   null],
];
$userName       = $_SESSION['user_name']  ?? 'Guest';
$userRole       = 'Resident';
$notifCount = 3;

// Helpers
$catColors = [
    'event'   => ['bg' => '#d1fae5', 'color' => '#065f46', 'border' => '#6ee7b7', 'accent' => '#059669'],
    'program' => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'border' => '#93c5fd', 'accent' => '#2563eb'],
    'meeting' => ['bg' => '#ede9fe', 'color' => '#5b21b6', 'border' => '#c4b5fd', 'accent' => '#7c3aed'],
    'notice'  => ['bg' => '#fef3c7', 'color' => '#92400e', 'border' => '#fcd34d', 'accent' => '#d97706'],
    'urgent'  => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'border' => '#fca5a5', 'accent' => '#dc2626'],
];
$cat       = $ann['category'];
$theme     = $catColors[$cat] ?? $catColors['notice'];
$catLabel  = ucfirst($cat);
$pubDate   = date('F j, Y', strtotime($ann['published_at']));
$pubDateDt = date('Y-m-d',  strtotime($ann['published_at']));
$updDate   = $ann['updated_at'] ? date('F j, Y', strtotime($ann['updated_at'])) : null;

function fileIcon(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match (true) {
        $ext === 'pdf'              => '📄',
        in_array($ext, ['doc', 'docx']) => '📝',
        in_array($ext, ['xls', 'xlsx']) => '📊',
        in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']) => '🖼️',
        default                     => '📎',
    };
}

function fileLabel(string $path): string
{
    return basename($path);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | <?= htmlspecialchars($ann['title']) ?></title>
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/announcement_view.css">
</head>

<body>

    <div class="dashboard-layout">

        <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

        <main class="dashboard-content">

            <?php include __DIR__ . '/../../components/portal/topbar.php'; ?>

            <div class="av-back-row">
                <a href="announcements_page.php" class="av-back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Back to Announcements
                </a>
            </div>

            <div class="av-layout">

                <!-- MAIN CONTENT -->
                <article class="av-main">

                    <!-- Banner -->
                    <?php if ($ann['banner_img']) : ?>
                        <div class="av-banner">
                            <img src="<?= htmlspecialchars($ann['banner_img']) ?>" alt="<?= htmlspecialchars($ann['title']) ?>">

                        </div>
                    <?php endif; ?>

                    <!-- Header block/Headline -->
                    <div class="av-header" style="--cat-bg: <?= $theme['bg'] ?>; --cat-border: <?= $theme['border'] ?>; --cat-accent: <?= $theme['accent'] ?>;">
                        <div class="av-badges">
                            <span class="av-cat-badge">
                                <?= $catLabel ?>
                            </span>
                            <?php if ($ann['featured']) : ?>
                                <span class="av-featured-badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 .975 6.323 4.793l-4.098.328c-.717.058-1.01.953-.462 1.423l3.121 2.673-.953 3.997c-.168.7.595 1.25 1.211.879L8 11.992l3.858 2.101c.616.371 1.379-.18 1.211-.879l-.953-3.997 3.121-2.673c.548-.47.255-1.365-.462-1.423L10.677 4.793 8 .975Z" clip-rule="evenodd" />
                                    </svg>
                                    Featured
                                </span>
                            <?php endif; ?>
                        </div>

                        <h1 class="av-title"><?= htmlspecialchars($ann['title']) ?></h1>

                        <div class="av-meta-row">
                            <div class="av-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                <span>Posted by <strong><?= htmlspecialchars($ann['author_name']) ?></strong></span>
                            </div>
                            <div class="av-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <time datetime="<?= $pubDateDt ?>"><?= $pubDate ?></time>
                            </div>
                            <?php if ($updDate) : ?>
                                <div class="av-meta-item av-meta-expiry">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>Updated <?= $updDate ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="av-divider" style="background: linear-gradient(90deg, <?= $theme['accent'] ?>, transparent);"></div>

                    <!-- Body content -->
                    <div class="av-body">
                        <?= $ann['content'] ?>
                    </div>

                    <!-- Attachments -->
                    <?php if (!empty($files)) : ?>
                        <div class="av-attachments">
                            <h3 class="av-attachments-title">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                </svg>
                                Attachments <span class="av-attach-count"><?= count($files) ?></span>
                            </h3>
                            <ul class="av-attach-list">
                                <?php foreach ($files as $f) : ?>
                                    <li class="av-attach-item">
                                        <span class="av-attach-icon"><?= fileIcon($f['file_path']) ?></span>
                                        <span class="av-attach-name"><?= htmlspecialchars(fileLabel($f['file_path'])) ?></span>
                                        <a href="<?= htmlspecialchars($f['file_path']) ?>" download class="av-attach-dl" title="Download">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </article>

                <!-- SIDEBAR -->
                <aside class="av-sidebar">

                    <!-- Info card -->
                    <div class="av-info-card">
                        <h3 class="av-info-title">Announcement Info</h3>
                        <dl class="av-info-list">
                            <div class="av-info-row">
                                <dt>Category</dt>
                                <dd>
                                    <span class="av-cat-pill" style="background:<?= $theme['bg'] ?>;color:<?= $theme['color'] ?>;border:1px solid <?= $theme['border'] ?>;">
                                        <?= $catLabel ?>
                                    </span>
                                </dd>
                            </div>
                            <div class="av-info-row">
                                <dt>Posted by</dt>
                                <dd><strong><?= htmlspecialchars($ann['author_name']) ?></strong></dd>
                            </div>
                            <div class="av-info-row">
                                <dt>Published</dt>
                                <dd><strong><?= $pubDate ?></strong></dd>
                            </div>
                            <?php if ($updDate) : ?>
                                <div class="av-info-row">
                                    <dt>Updated</dt>
                                    <dd><strong><?= $updDate ?></strong></dd>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($files)) : ?>
                                <div class="av-info-row">
                                    <dt>Attachments</dt>
                                    <dd><strong><?= count($files) ?></strong> file<?= count($files) > 1 ? 's' : '' ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <!-- Back button -->
                    <a href="announcements_page.php" class="av-back-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Back to All Announcements
                    </a>

                </aside>

            </div>

        </main>
    </div>

</body>

</html>