<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

require_once __DIR__ . '/../../backend/models/BookmarkModel.php';
require_once __DIR__ . '/../../backend/models/AnnouncementModel.php';

// Auto-archive expired before we fetch bookmarks
$annModel = new AnnouncementModel();
$annModel->archiveExpired();

$bmModel  = new BookmarkModel();
$userId   = (int) ($_SESSION['user_id'] ?? 0);
$bookmarks = $bmModel->getByUser($userId);
$bookmarkedIds = array_column($bookmarks, 'id');

// Meta
$pageTitle      = 'My Bookmarks';
$pageBreadcrumb = [
    ['Home',          '../../views/portal/dashboard.php'],
    ['Announcements', 'announcements_page.php'],
    ['My Bookmarks',  null],
];
$userName       = $_SESSION['user_name']  ?? 'Guest';
$userRole       = 'Resident';
$notifCount = 3;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | My Bookmarks</title>
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/announcements_page.css">
    <link rel="stylesheet" href="../../styles/portal/bookmark_page.css">
</head>

<body>

    <div class="dashboard-layout">

        <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

        <main class="dashboard-content">

            <?php include __DIR__ . '/../../components/portal/topbar.php'; ?>

            <!-- PAGE HEADER -->
            <div class="bmp-header">
                <div class="bmp-header-left">
                    <div class="bmp-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="bmp-title">My Bookmarked Announcements</h1>
                        <p class="bmp-subtitle">
                            <?php if (count($bookmarks) > 0) : ?>
                                You have <strong><?= count($bookmarks) ?></strong> saved announcement<?= count($bookmarks) !== 1 ? 's' : '' ?>.
                            <?php else : ?>
                                You have no saved announcements yet.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <a href="announcements_page.php" class="bmp-back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Back to Announcements
                </a>
            </div>

            <!-- CONTROLS -->
            <?php if (!empty($bookmarks)) : ?>
                <section class="announcements-controls bmp-controls">
                    <div class="controls-left">
                        <div class="search-wrap">
                            <span class="search-icon">🔍</span>
                            <input type="text" id="bm-search" placeholder="Search saved announcements..." class="ann-search-input">
                        </div>
                    </div>
                    <div class="controls-right">
                        <select id="bm-category" class="ann-select">
                            <option value="all">All Categories</option>
                            <option value="event">Events</option>
                            <option value="program">Programs</option>
                            <option value="meeting">Meetings</option>
                            <option value="notice">Notices</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <select id="bm-sort" class="ann-select">
                            <option value="newest_bm">Recently Saved</option>
                            <option value="oldest_bm">Oldest Saved</option>
                            <option value="newest_pub">Newest Published</option>
                            <option value="oldest_pub">Oldest Published</option>
                        </select>
                    </div>
                </section>
            <?php endif; ?>

            <!-- BOOKMARKS GRID -->
            <section class="announcements-section">

                <div class="announcements-grid" id="bm-grid">

                    <?php if (empty($bookmarks)) : ?>

                        <!-- EMPTY STATE -->
                        <div class="bmp-empty-state">
                            <div class="bmp-empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                </svg>
                            </div>
                            <h3 class="bmp-empty-title">No saved announcements</h3>
                            <p class="bmp-empty-desc">Browse announcements and tap the bookmark icon to save them here for quick access.</p>
                            <a href="announcements_page.php" class="btn-primary-portal">Browse Announcements</a>
                        </div>

                    <?php else : ?>

                        <?php foreach ($bookmarks as $ann) : ?>
                            <article class="ann-card bmp-card" data-id="<?= (int)$ann['id'] ?>" data-category="<?= htmlspecialchars($ann['category']) ?>" data-title="<?= htmlspecialchars(strtolower($ann['title'])) ?>" data-date-pub="<?= htmlspecialchars($ann['published_at']) ?>" data-date-bm="<?= htmlspecialchars($ann['bookmarked_at']) ?>">

                                <div class="ann-card-image">
                                    <?php if ($ann['banner_img']) : ?>
                                        <img src="<?= htmlspecialchars($ann['banner_img']) ?>" alt="<?= htmlspecialchars($ann['title']) ?>">
                                    <?php else : ?>
                                        <div class="ann-card-placeholder-img"></div>
                                    <?php endif; ?>
                                </div>

                                <div class="ann-card-body">
                                    <div class="ann-card-badges">
                                        <span class="ann-badge category-<?= htmlspecialchars($ann['category']) ?>">
                                            <?= ucfirst(htmlspecialchars($ann['category'])) ?>
                                        </span>
                                        <?php if ($ann['featured']) : ?>
                                            <span class="ann-badge ann-badge-featured">⭐ Featured</span>
                                        <?php endif; ?>
                                    </div>

                                    <h3 class="ann-card-title"><?= htmlspecialchars($ann['title']) ?></h3>
                                    <p class="ann-card-excerpt">
                                        <?= htmlspecialchars(mb_substr(strip_tags($ann['content']), 0, 160)) ?>…
                                    </p>
                                    <div class="ann-card-meta">
                                        <span>By: <?= htmlspecialchars($ann['author_name']) ?></span>
                                        <time datetime="<?= date('Y-m-d', strtotime($ann['published_at'])) ?>">
                                            <?= date('M j, Y', strtotime($ann['published_at'])) ?>
                                        </time>
                                    </div>

                                    <!-- Saved-on label -->
                                    <div class="bmp-saved-on">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M8 .975 6.323 4.793l-4.098.328c-.717.058-1.01.953-.462 1.423l3.121 2.673-.953 3.997c-.168.7.595 1.25 1.211.879L8 11.992l3.858 2.101c.616.371 1.379-.18 1.211-.879l-.953-3.997 3.121-2.673c.548-.47.255-1.365-.462-1.423L10.677 4.793 8 .975Z" clip-rule="evenodd" />
                                        </svg>
                                        Saved <?= date('M j, Y', strtotime($ann['bookmarked_at'])) ?>
                                    </div>

                                    <div class="ann-card-actions">
                                        <a href="announcement_view.php?id=<?= (int)$ann['id'] ?>" class="btn-secondary-portal">Read More</a>
                                        <button class="bookmark-btn active bmp-remove-btn" data-id="<?= (int)$ann['id'] ?>" title="Remove bookmark">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" class="bm-icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <!-- No search results -->
                <div class="no-results" id="bm-no-results" style="display:none;">
                    <p>No saved announcements match your search.</p>
                </div>

            </section>

            <!-- PAGINATION -->
            <?php if (!empty($bookmarks)) : ?>
                <section class="pagination-section">
                    <button class="page-btn" id="bm-prev-btn" disabled>&#8249; Previous</button>
                    <div class="page-numbers" id="bm-page-numbers"></div>
                    <button class="page-btn" id="bm-next-btn">Next &#8250;</button>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <script>
        window.SKONNECT = {
            bookmarkRouteUrl: '../../backend/routes/bookmarks.php'
        };
    </script>
    <script src="../../scripts/portal/bookmark_page.js"></script>
</body>

</html>