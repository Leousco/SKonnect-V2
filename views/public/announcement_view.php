<!-- PUBLIC ANNOUNCEMENT VIEW -->

<?php
require_once __DIR__ . '/../../backend/models/AnnouncementModel.php';

$annModel = new AnnouncementModel();

$id  = (int) ($_GET['id'] ?? 0);
$ann = $id ? $annModel->getById($id) : null;

if (!$ann || $ann['status'] === 'archived') {
    header('Location: announcements.php');
    exit;
}

$files = $annModel->getFiles($id);

// Helpers

$catColors = [
    'event'   => ['bg' => '#d1fae5', 'color' => '#065f46', 'border' => '#6ee7b7', 'accent' => '#059669', 'bar' => '#059669'],
    'program' => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'border' => '#93c5fd', 'accent' => '#2563eb', 'bar' => '#2563eb'],
    'meeting' => ['bg' => '#ede9fe', 'color' => '#5b21b6', 'border' => '#c4b5fd', 'accent' => '#7c3aed', 'bar' => '#7c3aed'],
    'notice'  => ['bg' => '#fef3c7', 'color' => '#92400e', 'border' => '#fcd34d', 'accent' => '#d97706', 'bar' => '#d97706'],
    'urgent'  => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'border' => '#fca5a5', 'accent' => '#dc2626', 'bar' => '#dc2626'],
];
$cat      = $ann['category'];
$theme    = $catColors[$cat] ?? $catColors['notice'];
$catLabel = ucfirst($cat);
$pubDate  = date('F j, Y', strtotime($ann['published_at']));
$pubDateDt = date('Y-m-d', strtotime($ann['published_at']));
$updDate  = $ann['updated_at'] ? date('F j, Y', strtotime($ann['updated_at'])) : null;

function fileIcon(string $path): string {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match(true) {
        $ext === 'pdf'                                     => '📄',
        in_array($ext, ['doc','docx'])                     => '📝',
        in_array($ext, ['xls','xlsx'])                     => '📊',
        in_array($ext, ['png','jpg','jpeg','webp','gif']) => '🖼️',
        default                                            => '📎',
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | <?= htmlspecialchars($ann['title']) ?></title>
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/public/header.css">
    <link rel="stylesheet" href="../../styles/public/footer.css">
    <link rel="stylesheet" href="../../styles/public/announcement_view.css">
</head>
<body>

<?php include __DIR__ . '/../../components/public/navbar.php'; ?>

<main class="pub-av-page">

    <div class="pub-av-layout">

        <!-- MAIN CONTENT -->
        <article class="pub-av-main">

            <!-- Banner -->
            <?php if ($ann['banner_img']): ?>
            <div class="pub-av-banner">
                <img src="<?= htmlspecialchars($ann['banner_img']) ?>" alt="<?= htmlspecialchars($ann['title']) ?>">

                <!-- Temporarily removed -->
                <!-- <?php if ($ann['featured']): ?>
                <div class="pub-av-ribbon">⭐ Featured</div>
                <?php endif; ?> -->

            </div>
            <?php endif; ?>

            <!-- Header/Headline -->
            <div class="pub-av-header" style="--cat-bg: <?= $theme['bg'] ?>; --cat-border: <?= $theme['border'] ?>;">

                <div class="pub-av-badges">
                    <span class="pub-av-cat-badge" style="background:<?= $theme['bg'] ?>;color:<?= $theme['color'] ?>;border:1px solid <?= $theme['border'] ?>;">
                        <?= $catLabel ?>
                    </span>
                    <?php if ($ann['featured']): ?>
                    <span class="pub-av-feat-badge">⭐ Featured</span>
                    <?php endif; ?>
                </div>

                <h1 class="pub-av-title"><?= htmlspecialchars($ann['title']) ?></h1>

                <div class="pub-av-meta">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        Posted by <strong><?= htmlspecialchars($ann['author_name']) ?></strong>
                    </span>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                        <time datetime="<?= $pubDateDt ?>"><?= $pubDate ?></time>
                    </span>
                </div>
            </div>

            <!-- Divider -->
            <div class="pub-av-accent-line"></div>

            <!-- Body -->
            <div class="pub-av-body">
                <?= $ann['content'] ?>
            </div>
            
            <!-- Attachments -->
            <?php if (!empty($files)): ?>
            <div class="pub-av-attachments">
                <h3 class="pub-av-attach-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                    Attachments <span class="pub-av-attach-count"><?= count($files) ?></span>
                </h3>
                <ul class="pub-av-attach-list">
                    <?php foreach ($files as $f): ?>
                    <li class="pub-av-attach-item">
                        <span class="pub-av-attach-icon"><?= fileIcon($f['file_path']) ?></span>
                        <span class="pub-av-attach-name"><?= htmlspecialchars(basename($f['file_path'])) ?></span>
                        <a href="<?= htmlspecialchars($f['file_path']) ?>" download class="pub-av-dl-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            Download
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Footer nav -->
            <div class="pub-av-footer-nav">
                <a href="announcements.php" class="pub-av-back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                    Back to Announcements
                </a>
            </div>

        </article>

        <!-- SIDEBAR -->
        <aside class="pub-av-sidebar">

            <div class="pub-av-info-card">
                <h3 class="pub-av-info-heading">Announcement Info</h3>
                <dl class="pub-av-info-list">
                    <div class="pub-av-info-row">
                        <dt>Category</dt>
                        <dd>
                            <span style="display:inline-flex;align-items:center;padding:3px 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-radius:20px;background:<?= $theme['bg'] ?>;color:<?= $theme['color'] ?>;border:1px solid <?= $theme['border'] ?>;">
                                <?= $catLabel ?>
                            </span>
                        </dd>
                    </div>
                    <div class="pub-av-info-row">
                        <dt>Posted by</dt>
                        <dd><strong><?= htmlspecialchars($ann['author_name']) ?></strong></dd>
                    </div>
                    <div class="pub-av-info-row">
                        <dt>Published</dt>
                        <dd><strong><?= $pubDate ?></strong></dd>
                    </div>
                    <?php if ($updDate): ?>
                    <div class="pub-av-info-row">
                        <dt>Updated</dt>
                        <dd><strong><?= $updDate ?></strong></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($files)): ?>
                    <div class="pub-av-info-row">
                        <dt>Attachments</dt>
                        <dd><strong><?= count($files) ?></strong> file<?= count($files) > 1 ? 's' : '' ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <a href="announcements.php" class="pub-av-sidebar-back">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                All Announcements
            </a>

        </aside>

    </div>

</main>

<?php include __DIR__ . '/../../components/public/footer.php'; ?>

</body>
</html>