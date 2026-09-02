<?php
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Announcements Page</title>
    <link rel="stylesheet" href="../../styles/portal/announcements_page.css">
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/portal/sidebar.css">
    <link rel="stylesheet" href="../../styles/portal/topbar.css">
    <link rel="stylesheet" href="../../styles/portal/bookmark_page.css">
</head>
<body>

<div class="dashboard-layout">

    <?php include __DIR__ . '/../../components/portal/sidebar.php'; ?>

    <main class="dashboard-content">

    <!-- HELPERS -->

    <?php
    $pageTitle      = 'Announcements';
    $pageBreadcrumb = [['Home', '#'], ['Announcements', null]];
    $userName       = $_SESSION['user_name']  ?? 'Guest';
    $userRole       = 'Resident';
    include __DIR__ . '/../../components/portal/topbar.php';

    // Load announcement data (SSR)
    require_once __DIR__ . '/../../backend/models/AnnouncementModel.php';
    $annModel  = new AnnouncementModel();
    $annModel->archiveExpired();
    $featured  = $annModel->getFeatured();
    $annList   = $annModel->getActive();

    require_once __DIR__ . '/../../backend/models/BookmarkModel.php';
    $bmModel       = new BookmarkModel();
    $userId        = (int) ($_SESSION['user_id'] ?? 0);
    $bookmarkedIds = $userId ? $bmModel->getBookmarkedIds($userId) : [];
    ?>

        <!-- FEATURED ANNOUNCEMENT -->
        <section class="featured-section">
            <h2 class="section-label">Featured Announcement</h2>

            <?php if ($featured): ?>
            <article class="featured-card">
                <div class="featured-badge-wrap">
                    <span class="ann-badge featured"> ⭐ FEATURED</span>
                    <span class="ann-badge category-<?= htmlspecialchars($featured['category']) ?>">
                        <?= ucfirst(htmlspecialchars($featured['category'])) ?>
                    </span>
                </div>
                <div class="featured-body">
                    <h3 class="featured-title"><?= htmlspecialchars($featured['title']) ?></h3>
                    <p class="featured-excerpt">
                        <?= nl2br(htmlspecialchars(mb_substr(strip_tags($featured['content']), 0, 150))) ?>…
                    </p>
                    <div class="featured-meta">
                        <span class="meta-author">📌 Posted by: <?= htmlspecialchars($featured['author_name']) ?></span>
                        <time class="meta-date" datetime="<?= date('Y-m-d', strtotime($featured['published_at'])) ?>">
                            <?= date('F j, Y', strtotime($featured['published_at'])) ?>
                        </time>
                    </div>
                    <a href="announcement_view.php?id=<?= (int)$featured['id'] ?>" class="btn-primary-portal">View Full Details</a>
                </div>
            </article>
            <?php else: ?>
            <p class="no-featured-msg">No featured announcement at this time.</p>
            <?php endif; ?>
        </section>

        <!-- CONTROLS: SEARCH, FILTER, SORT, BOOKMARKS -->
        <section class="announcements-controls">
            <div class="controls-left">
                <div class="search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text" id="ann-search" placeholder="Search announcements..." class="ann-search-input">
                </div>
            </div>
            <div class="controls-right">
                <select id="ann-category" class="ann-select">
                    <option value="all">All Categories</option>
                    <option value="event">Events</option>
                    <option value="program">Programs</option>
                    <option value="meeting">Meetings</option>
                    <option value="notice">Notices</option>
                    <option value="urgent">Urgent</option>
                </select>
                <select id="ann-sort" class="ann-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>

                <!-- MY BOOKMARKS BUTTON -->
                <a href="bookmark_page.php" class="btn-bookmarks-portal" title="My Bookmarks">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd"/>
                    </svg>
                    My Bookmarks
                </a>
            </div>
        </section>

        <!-- ANNOUNCEMENTS GRID -->
        <section class="announcements-section">
            <h2 class="section-label">All Announcements</h2>

            <div class="announcements-grid" id="announcements-grid">

                <?php if (empty($annList)): ?>
                <p class="no-results-msg">No announcements available.</p>
                <?php else: ?>

                <?php foreach ($annList as $ann):
                    $isBookmarked = in_array((int)$ann['id'], $bookmarkedIds);
                ?>
                <article class="ann-card"
                         data-category="<?= htmlspecialchars($ann['category']) ?>"
                         data-title="<?= htmlspecialchars(strtolower($ann['title'])) ?>"
                         data-date="<?= htmlspecialchars($ann['published_at']) ?>">

                    <div class="ann-card-image">
                        <?php if ($ann['banner_img']): ?>
                            <img src="<?= htmlspecialchars($ann['banner_img']) ?>" alt="<?= htmlspecialchars($ann['title']) ?>">
                        <?php else: ?>
                            <div class="ann-card-placeholder-img"></div>
                        <?php endif; ?>
                    </div>

                    <div class="ann-card-body">
                        <div class="ann-card-badges">
                            <span class="ann-badge category-<?= htmlspecialchars($ann['category']) ?>">
                                <?= ucfirst(htmlspecialchars($ann['category'])) ?>
                            </span>
                            <?php if ($ann['featured']): ?>
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
                        <div class="ann-card-actions">
                            <a href="announcement_view.php?id=<?= (int)$ann['id'] ?>" class="btn-read-portal">Read More</a>
                            <button class="bookmark-btn <?= $isBookmarked ? 'active' : '' ?>"
                                    data-id="<?= (int)$ann['id'] ?>"
                                    title="<?= $isBookmarked ? 'Remove bookmark' : 'Bookmark' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                     fill="<?= $isBookmarked ? 'currentColor' : 'none' ?>"
                                     stroke="currentColor" stroke-width="2" class="bm-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <!-- NO RESULTS MESSAGE -->
            <div class="no-results" id="no-results" style="display:none;">
                <p>No announcements found matching your search.</p>
            </div>
        </section>

        <!-- PAGINATION -->
        <section class="pagination-section">
            <button class="page-btn" id="prev-btn" disabled>&#8249; Previous</button>
            <div class="page-numbers" id="page-numbers"></div>
            <button class="page-btn" id="next-btn">Next &#8250;</button>
        </section>

    </main>
</div>

<!-- Pass bookmarked IDs to JS -->
<script>
    window.SKONNECT = {
        bookmarkedIds:    <?= json_encode(array_map('intval', $bookmarkedIds)) ?>,
        bookmarkRouteUrl: '../../backend/routes/bookmarks.php'
    };
</script>
<script src="../../scripts/portal/announcements_page.js"></script>
</body>
</html>